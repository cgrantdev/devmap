<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\VendorCertificationClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Vendor-facing certification submission. The vendor visits
 * /vendor/certifications, picks a badge type, uploads supporting
 * documentation, and hits submit. Julia reviews via admin queue.
 * See docs/vendor-certifications.md.
 */
class CertificationsController extends Controller
{
    public function index(Request $request)
    {
        $brand = $this->brandForUser($request);
        abort_unless($brand, 403);

        $claims = VendorCertificationClaim::where('brand_id', $brand->id)
            ->get()
            ->keyBy('type')
            ->map(fn($c) => [
                'type' => $c->type,
                'type_label' => $c->label(),
                'status' => $c->status,
                'notes' => $c->notes,
                'admin_notes' => $c->admin_notes,
                'document_original_name' => $c->document_original_name,
                'submitted_at' => $c->created_at?->toIso8601String(),
                'verified_at' => $c->verified_at?->toIso8601String(),
            ]);

        return Inertia::render('Vendor/Certifications', [
            'brand' => ['id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug],
            'claims' => $claims,
            'availableTypes' => VendorCertificationClaim::TYPE_LABELS,
        ]);
    }

    public function store(Request $request)
    {
        $brand = $this->brandForUser($request);
        abort_unless($brand, 403);

        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', VendorCertificationClaim::TYPES),
            'document' => 'required|file|mimes:pdf,png,jpg,jpeg,webp|max:15360', // 15 MB
            'notes' => 'nullable|string|max:2000',
        ]);

        // Reuse existing row if there is one — vendor re-submitting after
        // rejection, or updating a pending doc.
        $claim = VendorCertificationClaim::firstOrNew([
            'brand_id' => $brand->id,
            'type' => $validated['type'],
        ]);

        // Store under a per-claim directory so replacements are trivial.
        // Use existing id if updating, else generate a temporary path.
        $claim->save(); // ensure id
        $dir = "certifications/{$claim->id}";
        // Clean any previous doc so we don't leak files.
        if ($claim->document_path && Storage::disk('local')->exists($claim->document_path)) {
            Storage::disk('local')->delete($claim->document_path);
        }
        $path = $request->file('document')->store($dir, 'local');

        $claim->document_path = $path;
        $claim->document_original_name = $request->file('document')->getClientOriginalName();
        $claim->notes = $validated['notes'] ?? null;
        $claim->submitted_by_user_id = $request->user()?->id;
        $claim->status = VendorCertificationClaim::STATUS_PENDING;
        $claim->admin_notes = null;
        $claim->verified_at = null;
        $claim->verified_by_user_id = null;
        $claim->save();

        // Colin PMAP Sep 16 — notify Julia in Discord growth channel
        // so she knows a doc's waiting in the admin queue. Same tone
        // and flags:4 (suppress link preview) as the coupon-boost posts.
        $this->announceSubmission($claim);

        return back()->with('flash_success', 'Submitted for review — we\'ll email you once it\'s approved.');
    }

    private function announceSubmission(VendorCertificationClaim $c): void
    {
        $token = config('services.discord.bot_token');
        $channel = config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        $brandName = $c->brand?->name ?? 'A vendor';
        $line = "{$brandName} submitted a {$c->label()} verification claim — review at <https://peptidemap.com/admin/certifications>.";

        try {
            Http::withHeaders(['Authorization' => 'Bot ' . $token, 'Content-Type' => 'application/json'])
                ->timeout(6)
                ->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                    'content' => $line,
                    'flags' => 4,
                ]);
        } catch (\Throwable $e) {
            Log::warning('cert submission discord post failed', ['err' => $e->getMessage()]);
        }
    }

    private function brandForUser(Request $request): ?Brand
    {
        $user = $request->user();
        if (!$user) return null;
        return Brand::where('user_id', $user->id)->first();
    }
}

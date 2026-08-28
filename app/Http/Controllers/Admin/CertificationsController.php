<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorCertificationClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

/**
 * Admin queue for vendor-submitted verification claims (cGMP,
 * independent testing). See docs/vendor-certifications.md.
 */
class CertificationsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $rows = VendorCertificationClaim::with(['brand:id,name,slug', 'submittedBy:id,name,email', 'verifiedBy:id,name,email'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'brand' => ['id' => $c->brand?->id, 'name' => $c->brand?->name, 'slug' => $c->brand?->slug],
                    'type' => $c->type,
                    'type_label' => $c->label(),
                    'status' => $c->status,
                    'notes' => $c->notes,
                    'admin_notes' => $c->admin_notes,
                    'document_original_name' => $c->document_original_name,
                    'has_document' => (bool) $c->document_path,
                    'submitted_by' => $c->submittedBy?->only(['name', 'email']),
                    'verified_by' => $c->verifiedBy?->only(['name', 'email']),
                    'verified_at' => $c->verified_at?->toIso8601String(),
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Certifications/Index', [
            'claims' => $rows,
            'filterStatus' => $status,
        ]);
    }

    /**
     * Stream the private document to Julia for review. Signed by session
     * auth — not a public URL.
     */
    public function download(int $id)
    {
        $c = VendorCertificationClaim::findOrFail($id);
        abort_unless($c->document_path && Storage::disk('local')->exists($c->document_path), 404);
        return Storage::disk('local')->download(
            $c->document_path,
            $c->document_original_name ?: basename($c->document_path)
        );
    }

    public function approve(Request $request, int $id)
    {
        $validated = $request->validate(['admin_notes' => 'nullable|string|max:2000']);
        $c = VendorCertificationClaim::with('brand')->findOrFail($id);
        $c->status = VendorCertificationClaim::STATUS_APPROVED;
        $c->admin_notes = $validated['admin_notes'] ?? $c->admin_notes;
        $c->verified_at = now();
        $c->verified_by_user_id = $request->user()?->id;
        $c->save();

        $this->announceApproval($c);

        return back()->with('flash_success', "Approved — {$c->brand?->name}: {$c->label()}");
    }

    public function reject(Request $request, int $id)
    {
        $validated = $request->validate(['admin_notes' => 'required|string|max:2000']);
        $c = VendorCertificationClaim::findOrFail($id);
        $c->status = VendorCertificationClaim::STATUS_REJECTED;
        $c->admin_notes = $validated['admin_notes'];
        $c->verified_at = now();
        $c->verified_by_user_id = $request->user()?->id;
        $c->save();
        return back()->with('flash_success', 'Rejected. Vendor can re-submit with new documentation.');
    }

    /**
     * Fire a Discord announcement into the growth channel when a claim
     * is approved. Same pattern as coupon-boost + weekly digest posts.
     */
    private function announceApproval(VendorCertificationClaim $c): void
    {
        $token = config('services.discord.bot_token');
        $channel = config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        $brandName = $c->brand?->name ?? 'A vendor';
        $slug = $c->brand?->slug;
        $line = "🏅 **{$brandName}** verified: **{$c->label()}**";
        if ($slug) $line .= " — https://peptidemap.com/brand/{$slug}";

        try {
            Http::withToken($token, 'Bot')
                ->timeout(6)
                ->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                    'content' => $line,
                ]);
        } catch (\Throwable $e) {
            // Not fatal — the approval already saved.
            \Log::warning('certification approval discord post failed', ['err' => $e->getMessage()]);
        }
    }
}

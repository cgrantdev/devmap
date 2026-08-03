<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CeoAgentRun;
use App\Models\CeoInitiative;
use App\Models\CeoNote;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorReview;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CEO dashboard. Route is gated by:
 *   Route::middleware(['auth', 'role:admin', 'email.verified', 'ceo.only'])
 * so this class assumes the caller is the CEO.
 */
class CeoDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CeoDashboard', [
            'snapshot' => $this->snapshot(),
            'agentRuns' => CeoAgentRun::orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'agent_name' => $r->agent_name,
                    'status' => $r->status,
                    'title' => $r->title,
                    'summary' => $r->summary,
                    'next_steps' => $r->next_steps,
                    'commit_hashes' => $r->commit_hashes ?? [],
                    'links' => $r->links ?? [],
                    'created_at' => $r->created_at?->toIso8601String(),
                    'created_at_h' => $r->created_at?->diffForHumans(),
                ]),
            'initiatives' => CeoInitiative::orderByDesc('pinned')
                ->orderBy('position')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'category' => $i->category,
                    'title' => $i->title,
                    'status' => $i->status,
                    'owner' => $i->owner,
                    'notes' => $i->notes,
                    'pinned' => $i->pinned,
                    'position' => $i->position,
                    'completed_at' => $i->completed_at?->toIso8601String(),
                ]),
            'notepad' => (CeoNote::firstOrCreate(['key' => 'notepad'], ['body' => '']))->body,
            'recentCommits' => $this->recentCommits(),
        ]);
    }

    /* -------- writes -------- */

    public function storeAgentRun(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'agent_name' => 'required|string|max:80',
            'status' => 'nullable|string|max:32',
            'title' => 'required|string|max:200',
            'summary' => 'required|string|max:20000',
            'next_steps' => 'nullable|string|max:10000',
            'commit_hashes' => 'nullable|array',
            'commit_hashes.*' => 'string|max:64',
            'links' => 'nullable|array',
        ]);
        CeoAgentRun::create($v);
        return redirect()->route('admin.ceo')->with('success', 'Agent run logged.');
    }

    public function storeInitiative(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'category' => 'required|string|max:40',
            'title' => 'required|string|max:200',
            'status' => 'nullable|string|max:32',
            'owner' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
            'pinned' => 'sometimes|boolean',
        ]);
        CeoInitiative::create($v);
        return redirect()->route('admin.ceo');
    }

    public function updateInitiative(Request $request, CeoInitiative $initiative): RedirectResponse
    {
        $v = $request->validate([
            'category' => 'sometimes|string|max:40',
            'title' => 'sometimes|string|max:200',
            'status' => 'sometimes|string|max:32',
            'owner' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:5000',
            'pinned' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);
        // Auto-stamp completion time when moving into 'done'.
        if (($v['status'] ?? null) === 'done' && $initiative->status !== 'done') {
            $v['completed_at'] = now();
        } elseif (($v['status'] ?? null) && $v['status'] !== 'done') {
            $v['completed_at'] = null;
        }
        $initiative->update($v);
        return redirect()->route('admin.ceo');
    }

    public function destroyInitiative(CeoInitiative $initiative): RedirectResponse
    {
        $initiative->delete();
        return redirect()->route('admin.ceo');
    }

    public function saveNotepad(Request $request): RedirectResponse
    {
        $v = $request->validate(['body' => 'nullable|string|max:100000']);
        CeoNote::updateOrCreate(['key' => 'notepad'], ['body' => $v['body'] ?? '']);
        return redirect()->route('admin.ceo');
    }

    /* -------- read helpers -------- */

    private function snapshot(): array
    {
        // Cheap counts — every one is an indexed COUNT(*) or a cached lookup.
        return [
            'vendors_active' => Brand::where('is_active', true)->count(),
            'vendors_total' => Brand::count(),
            'products_visible' => Product::visible()->where('price', '>', 0)->count(),
            'products_total' => Product::count(),
            'users' => User::count(),
            'users_verified' => User::whereNotNull('email_verified_at')->count(),
            'wishlists' => Schema::hasTable('wishlists') ? Wishlist::count() : 0,
            'reviews_published' => VendorReview::when(
                Schema::hasColumn('vendor_reviews', 'status'),
                fn ($q) => $q->where('status', 'published')
            )->count(),
            'reviews_pending' => Schema::hasColumn('vendor_reviews', 'status')
                ? VendorReview::where('status', 'pending')->count() : 0,
            'discord_members' => $this->discordMemberCount(),
        ];
    }

    /**
     * Fetch member count via the bot's guild — cached 5 min so the dashboard
     * doesn't pester Discord on every render. Falls back to null (rendered
     * as "—") if the bot is offline or Discord returns non-200.
     */
    private function discordMemberCount(): ?int
    {
        return Cache::remember('ceo.discord_member_count', 300, function () {
            $token = config('services.discord.bot_token');
            $guild = config('services.discord.guild_id');
            if (!$token || !$guild) return null;
            try {
                $resp = Http::timeout(6)
                    ->withHeaders(['Authorization' => 'Bot ' . $token])
                    ->get("https://discord.com/api/v10/guilds/{$guild}?with_counts=true");
                if (!$resp->successful()) return null;
                return $resp->json('approximate_member_count');
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * Last 30 commits via `git log`. Cached 5 min so this dashboard doesn't
     * spawn a subprocess on every render.
     */
    private function recentCommits(): array
    {
        return Cache::remember('ceo.recent_commits', 300, function () {
            $repoRoot = base_path();
            $format = '%H%x1f%h%x1f%s%x1f%an%x1f%ct';
            $cmd = 'git -C ' . escapeshellarg($repoRoot) . ' log -n 30 --pretty=format:'
                . escapeshellarg($format) . ' 2>/dev/null';
            $out = @shell_exec($cmd);
            if (!$out) return [];
            $lines = array_filter(array_map('trim', explode("\n", $out)));
            return array_map(function ($line) {
                [$sha, $short, $subject, $author, $ts] = array_pad(explode("\x1f", $line), 5, '');
                return [
                    'sha' => $sha,
                    'short' => $short,
                    'subject' => $subject,
                    'author' => $author,
                    'ts' => (int) $ts,
                ];
            }, $lines);
        });
    }
}

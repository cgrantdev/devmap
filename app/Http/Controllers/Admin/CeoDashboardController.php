<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\CeoAgentRun;
use App\Models\CeoNote;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRecommendation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CEO dashboard — SEO focused.
 * Gated: auth + role:admin + email.verified + ceo.only (info@peptidemap.com).
 *
 * Data model:
 *   seo_recommendations  Primary artifact. Strategist writes; implementer picks off.
 *                        category=new-agent flags "we need to build a new agent" items.
 *   ceo_agent_runs       Long-form log of each strategist/implementer session.
 *   ceo_notes            Autosave notepad singleton.
 */
class CeoDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CeoDashboard', [
            'snapshot' => $this->snapshot(),
            'growthMetrics' => app(\App\Services\GrowthMetrics::class)->snapshot(),
            // Google Search Console snapshot — clicks, impressions, avg
            // rank, top queries + pages, and the "8-20 rank" opportunity
            // list. Empty when GSC hasn't been wired yet.
            'seoMetrics' => app(\App\Services\SeoMetrics::class)->snapshot(28),
            'gscConnectedEmail' => app(\App\Services\GscClient::class)->connectedEmail(),
            'openRecs' => $this->recs('open'),
            'inProgressRecs' => $this->recs('in_progress'),
            'shippedRecs' => $this->recs('shipped', 20),
            // Show active new-agent items (open + in_progress) so ones you
            // clicked "Start building" on don't vanish into limbo.
            'agentRequests' => SeoRecommendation::where('category', 'new-agent')
                ->whereIn('status', ['open', 'in_progress'])
                ->orderByDesc('pinned')
                ->orderByRaw("FIELD(status, 'in_progress', 'open')")
                ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'title' => $r->title, 'category' => $r->category,
                    'impact' => $r->impact, 'effort' => $r->effort, 'status' => $r->status,
                    'rationale' => $r->rationale, 'expected_impact' => $r->expected_impact,
                    'commit_hashes' => $r->commit_hashes ?? [],
                ]),
            'agentRuns' => CeoAgentRun::whereIn('agent_name', ['seo-strategist', 'seo-implementer', 'claude', 'Plan', 'Explore'])
                ->orderByDesc('created_at')
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
                    'created_at' => $r->created_at?->toIso8601String(),
                    'created_at_h' => $r->created_at?->diffForHumans(),
                ]),
            'notepad' => (CeoNote::firstOrCreate(['key' => 'notepad'], ['body' => '']))->body,
            'recentCommits' => $this->recentCommits(),
            'lastStrategistRun' => CeoAgentRun::where('agent_name', 'seo-strategist')
                ->latest()->value('created_at')?->diffForHumans(),
            'lastImplementerRun' => CeoAgentRun::where('agent_name', 'seo-implementer')
                ->latest()->value('created_at')?->diffForHumans(),
        ]);
    }

    private function recs(string $status, int $limit = 100, ?string $category = null)
    {
        return SeoRecommendation::query()
            ->where('status', $status)
            ->when($category, fn ($q) => $q->where('category', $category))
            // Exclude new-agent from open/in_progress here (they render in their
            // own violet section). But INCLUDE them in shipped so a completed
            // agent build shows up in the Recently Shipped column.
            ->when(!$category && in_array($status, ['open', 'in_progress']),
                fn ($q) => $q->where('category', '!=', 'new-agent'))
            ->orderByDesc('pinned')
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('position')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'category' => $r->category,
                'impact' => $r->impact,
                'effort' => $r->effort,
                'status' => $r->status,
                'rationale' => $r->rationale,
                'expected_impact' => $r->expected_impact,
                'source' => $r->source,
                'shipped_by' => $r->shipped_by,
                'shipped_at' => $r->shipped_at?->toIso8601String(),
                'shipped_at_h' => $r->shipped_at?->diffForHumans(),
                'commit_hashes' => $r->commit_hashes ?? [],
                'pinned' => $r->pinned,
                'created_at_h' => $r->created_at?->diffForHumans(),
            ])->values();
    }

    /* -------- writes -------- */

    public function storeRecommendation(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'title' => 'required|string|max:220',
            'category' => 'required|string|max:40',
            'impact' => 'required|in:high,medium,low',
            'effort' => 'required|in:small,medium,large',
            'status' => 'sometimes|in:open,in_progress,shipped,rejected,deferred',
            'rationale' => 'nullable|string|max:10000',
            'expected_impact' => 'nullable|string|max:2000',
            'source' => 'sometimes|string|max:40',
            'pinned' => 'sometimes|boolean',
        ]);
        SeoRecommendation::create($v);
        return redirect()->route('admin.ceo');
    }

    public function updateRecommendation(Request $request, SeoRecommendation $recommendation): RedirectResponse
    {
        $v = $request->validate([
            'title' => 'sometimes|string|max:220',
            'category' => 'sometimes|string|max:40',
            'impact' => 'sometimes|in:high,medium,low',
            'effort' => 'sometimes|in:small,medium,large',
            'status' => 'sometimes|in:open,in_progress,shipped,rejected,deferred',
            'rationale' => 'nullable|string|max:10000',
            'expected_impact' => 'nullable|string|max:2000',
            'shipped_by' => 'nullable|string|max:40',
            'commit_hashes' => 'nullable|array',
            'commit_hashes.*' => 'string|max:64',
            'pinned' => 'sometimes|boolean',
        ]);
        // Stamp shipped_at when transitioning to shipped.
        if (($v['status'] ?? null) === 'shipped' && $recommendation->status !== 'shipped') {
            $v['shipped_at'] = now();
        } elseif (($v['status'] ?? null) && $v['status'] !== 'shipped') {
            $v['shipped_at'] = null;
            $v['shipped_by'] = null;
        }
        $recommendation->update($v);
        return redirect()->route('admin.ceo');
    }

    public function destroyRecommendation(SeoRecommendation $recommendation): RedirectResponse
    {
        $recommendation->delete();
        return redirect()->route('admin.ceo');
    }


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
        $now = now();
        return [
            // Content volume — the raw material SEO works with.
            'vendors' => Brand::where('is_active', true)->count(),
            'products' => Product::visible()->where('price', '>', 0)->count(),
            'blogs' => Schema::hasTable('blogs') ? Blog::count() : 0,
            'blogs_last_30d' => Schema::hasTable('blogs')
                ? Blog::where('created_at', '>=', $now->copy()->subDays(30))->count() : 0,
            // Encyclopedia surface = active ProductCategory pages (one URL each at
            // /encyclopedia/{slug}). EducationPost is the long-form content that
            // fills them — a category page still exists without one, just thin.
            'encyclopedia' => Schema::hasTable('product_categories')
                ? ProductCategory::where('is_active', true)->count() : 0,
            'encyclopedia_with_content' => Schema::hasTable('education_posts')
                ? EducationPost::where('status', 'published')->count() : 0,
            // Approximation of indexed surface — sum of the tables the sitemap draws from.
            'indexable_urls' => $this->indexableUrlEstimate(),

            // SEO agent flow signals.
            'recs_open' => SeoRecommendation::where('status', 'open')->count(),
            'recs_in_progress' => SeoRecommendation::where('status', 'in_progress')->count(),
            'recs_shipped_7d' => SeoRecommendation::where('status', 'shipped')
                ->where('shipped_at', '>=', $now->copy()->subDays(7))->count(),
            'recs_shipped_30d' => SeoRecommendation::where('status', 'shipped')
                ->where('shipped_at', '>=', $now->copy()->subDays(30))->count(),

            // Community — leading indicator of return traffic / brand searches.
            'users' => User::count(),
            'discord_members' => $this->discordMemberCount(),
        ];
    }

    private function indexableUrlEstimate(): int
    {
        // Rough sum of the routes the sitemap emits per-row:
        //   /brand/{slug}                            → active brands
        //   /product/{v}/{p}/{id}                    → visible priced products
        //   /peptide/{slug}                          → categories (not counted here)
        //   /encyclopedia/{slug}                     → guides
        //   /blog/{slug}                             → blogs
        // Plus ~15 static pages. Good enough as a directional metric.
        return Brand::where('is_active', true)->count()
            + Product::visible()->where('price', '>', 0)->count()
            + (Schema::hasTable('product_categories') ? ProductCategory::where('is_active', true)->count() : 0)
            + (Schema::hasTable('blogs') ? Blog::count() : 0)
            + 15;
    }

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
                return $resp->successful() ? $resp->json('approximate_member_count') : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    private function recentCommits(): array
    {
        return Cache::remember('ceo.recent_commits', 300, function () {
            $format = '%H%x1f%h%x1f%s%x1f%an%x1f%ct';
            $out = @shell_exec('git -C ' . escapeshellarg(base_path())
                . ' log -n 20 --pretty=format:' . escapeshellarg($format) . ' 2>/dev/null');
            if (!$out) return [];
            return array_map(function ($line) {
                [$sha, $short, $subject, $author, $ts] = array_pad(explode("\x1f", $line), 5, '');
                return [
                    'sha' => $sha, 'short' => $short, 'subject' => $subject,
                    'author' => $author, 'ts' => (int) $ts,
                ];
            }, array_filter(array_map('trim', explode("\n", $out))));
        });
    }
}

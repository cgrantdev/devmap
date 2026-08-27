<?php

namespace App\Console\Commands;

use App\Services\GrowthMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Weekly Discord digest — posts a growth-metrics summary to the configured
 * Discord channel via the bot's REST API. Runs from Laravel's scheduler
 * every Monday 9am UTC; can also be invoked manually to test:
 *
 *   php artisan growth:digest              # posts to the configured channel
 *   php artisan growth:digest --dry-run    # prints to console only
 *   php artisan growth:digest --channel=CHANNEL_ID   # override target
 */
class SendGrowthDigest extends Command
{
    protected $signature = 'growth:digest
                            {--dry-run : Print the digest without posting to Discord}
                            {--channel= : Override the destination channel id}';

    protected $description = 'Post a weekly growth digest to Discord.';

    public function handle(GrowthMetrics $metrics): int
    {
        $snap = $metrics->snapshot();
        $body = $this->buildMessage($snap);

        if ($this->option('dry-run')) {
            $this->line($body);
            return self::SUCCESS;
        }

        $token = config('services.discord.bot_token');
        $channel = $this->option('channel') ?: config('services.discord.growth_channel_id');
        if (!$token || !$channel) {
            $this->error('DISCORD_BOT_TOKEN or growth_channel_id missing in config/env.');
            return self::FAILURE;
        }

        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bot ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                'content' => $body,
            ]);
        } catch (\Throwable $e) {
            Log::error('growth digest post failed', ['err' => $e->getMessage()]);
            $this->error('Post failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$resp->successful()) {
            Log::warning('growth digest Discord HTTP ' . $resp->status(), ['body' => $resp->body()]);
            $this->error("Discord returned HTTP {$resp->status()}: " . $resp->body());
            return self::FAILURE;
        }

        $this->info("Posted growth digest to channel {$channel}.");
        return self::SUCCESS;
    }

    /**
     * Compose the SEO-from-GSC section of the digest. Returns "" when GSC
     * isn't configured — silently omitted so we don't clutter the post.
     */
    private function seoSection(array $seo): string
    {
        if (empty($seo['configured'])) return '';
        $t = $seo['totals'] ?? [];
        $tp = $seo['totals_prev'] ?? [];
        $ctr = number_format(($t['ctr'] ?? 0) * 100, 2);
        $topQ = collect($seo['top_queries'] ?? [])->take(5)
            ->map(fn ($r) => "• `{$r['query']}` — #{$r['position']} · {$r['clicks']} clk")->implode("\n") ?: '_no query data yet_';
        $opps = collect($seo['opportunities'] ?? [])->take(5)
            ->map(fn ($r) => "• `{$r['query']}` — #{$r['position']} · {$r['impressions']} imp")->implode("\n") ?: '_no rank-8-20 queries yet_';
        $prevSuffix = !empty($tp['clicks']) ? " (prev {$tp['clicks']})" : '';
        return "\n\n**Search (Google Search Console)**\nClicks: **{$t['clicks']}**{$prevSuffix}  ·  Impressions: **{$t['impressions']}**  ·  CTR: {$ctr}%  ·  Avg rank: **{$t['position']}**\n\n**Top queries**\n{$topQ}\n\n**Rank 8-20 opportunities**\n{$opps}\n";
    }

    private function buildMessage(array $s): string
    {
        $wow = $s['week_over_week'] ?? [];
        $vp = $s['vendor_pipeline'] ?? [];
        // GSC snapshot piggybacks on the same digest so Colin sees rank +
        // impressions alongside affiliate-click movement.
        $seo = app(\App\Services\SeoMetrics::class)->snapshot(7);

        $arrow = fn ($d) => $d === null ? '' : ($d > 0 ? " 📈 +{$d}%" : ($d < 0 ? " 📉 {$d}%" : ' →'));

        $topCompounds = collect($s['top_compounds'] ?? [])->take(5)
            ->map(fn ($r) => "• **{$r['name']}** — {$r['clicks']} clicks")->implode("\n") ?: '_no click data yet_';
        $topVendors = collect($s['top_vendors'] ?? [])->take(5)
            ->map(fn ($r) => "• **{$r['name']}** — {$r['clicks']} clicks")->implode("\n") ?: '_no click data yet_';
        $topPages = collect($s['top_pages'] ?? [])->take(5)
            ->map(fn ($r) => "• `{$r['page']}` — {$r['clicks']}")->implode("\n") ?: '_no internal-src data yet_';

        $windowStart = now()->subDays(7)->format('M j');
        $windowEnd = now()->format('M j');

        return <<<MSG
📊 **Peptidemap · Week of {$windowStart}–{$windowEnd}**

**Traffic**
Sessions: **{$wow['sessions_this']}**{$arrow($wow['sessions_delta'] ?? null)}  ·  prev: {$wow['sessions_prev']}
Pageviews: **{$wow['views_this']}**{$arrow($wow['views_delta'] ?? null)}
Affiliate clicks: **{$wow['clicks_this']}**{$arrow($wow['clicks_delta'] ?? null)}  ·  prev: {$wow['clicks_prev']}

**Top compounds this week**
{$topCompounds}

**Top vendors this week**
{$topVendors}

**Top internal pages driving clicks**
{$topPages}

**Vendors**
Approved: {$vp['approved']}  ·  Pending: {$vp['pending']}  ·  New this week: {$vp['new_this_week']}
{$this->seoSection($seo)}
<https://peptidemap.com/admin/ceo>
MSG;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\VendorSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Flips scheduled coupon boosts to active when their start time hits.
 *
 * Julia's flow (Colin Sep 14): she picks a start_at + end_at in the
 * admin. If start_at is in the future, applyCouponBoost stores the
 * schedule but does NOT touch coupon_discount_percent yet so the
 * vendor card keeps showing the standard rate. This command runs
 * every 5 minutes, finds vendors whose start_at just passed, swaps
 * their discount to the boosted %, clears the start_at marker so
 * the boost is now "active", and posts to Discord.
 *
 * Pairs with RevertExpiredCouponBoosts for the end of the boost.
 */
class ActivateScheduledCouponBoosts extends Command
{
    protected $signature = 'coupons:activate-scheduled-boosts {--dry-run}';
    protected $description = 'Promote scheduled coupon boosts to active when their start time hits.';

    public function handle(): int
    {
        $now = now();
        $due = VendorSetting::whereNotNull('coupon_boost_percent')
            ->whereNotNull('coupon_boost_starts_at')
            ->where('coupon_boost_starts_at', '<=', $now)
            ->where('coupon_boost_expires_at', '>', $now)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled boosts to activate.');
            return self::SUCCESS;
        }

        foreach ($due as $vs) {
            $brand = $vs->brand;
            $newPct = (float) $vs->coupon_boost_percent;
            $this->line("  {$brand?->name}: activating scheduled {$newPct}% boost");

            if ($this->option('dry-run')) continue;

            $updates = [
                'coupon_discount_percent' => $newPct,
                'coupon_boost_starts_at' => null,
            ];
            // Swap the code too if the boost carries one. previous_code
            // was already snapshotted at applyCouponBoost time.
            if (!empty($vs->coupon_boost_code)) {
                $updates['coupon_code'] = $vs->coupon_boost_code;
            }
            $vs->forceFill($updates)->save();

            $this->postDiscordBoostStart($brand?->name ?? 'A vendor', $brand?->slug, $newPct, $vs->coupon_boost_expires_at);
        }

        $this->info("Activated {$due->count()} scheduled boost(s).");
        return self::SUCCESS;
    }

    private function postDiscordBoostStart(string $brandName, ?string $slug, float $newPct, \DateTimeInterface $expiresAt): void
    {
        $token = config('services.discord.bot_token');
        $channel = config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        $link = $slug ? "https://peptidemap.com/brand/{$slug}" : 'https://peptidemap.com/deals';
        $until = \Carbon\Carbon::parse($expiresAt)->format('M j g:i A T');

        try {
            Http::withHeaders(['Authorization' => 'Bot ' . $token, 'Content-Type' => 'application/json'])
                ->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                    'content' => "🔥 **{$brandName}** is running a limited-time **{$newPct}% off** promo until {$until}. → {$link}",
                ]);
        } catch (\Throwable $e) {
            Log::warning('scheduled coupon boost activation Discord post failed', ['err' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\VendorSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reverts expired coupon boosts back to the vendor's standard percentage.
 *
 * Julia applies a boost via the admin (e.g. Amino Club → 35% for 72h).
 * When she does, VendorSetting.coupon_discount_previous_percent is
 * snapshotted and coupon_discount_percent is bumped. When
 * coupon_boost_expires_at passes, this command flips the percentage
 * back and clears the boost metadata — so she never has to manually
 * roll back a promotion.
 *
 * Runs every 5 minutes via routes/console.php.
 */
class RevertExpiredCouponBoosts extends Command
{
    protected $signature = 'coupons:revert-expired-boosts {--dry-run}';
    protected $description = 'Revert coupon-discount % on vendors whose temporary boost has expired.';

    public function handle(): int
    {
        $now = now();
        $expired = VendorSetting::whereNotNull('coupon_boost_expires_at')
            ->whereNotNull('coupon_discount_previous_percent')
            ->where('coupon_boost_expires_at', '<=', $now)
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired coupon boosts.');
            return self::SUCCESS;
        }

        foreach ($expired as $vs) {
            $wasPercent = $vs->coupon_discount_percent;
            $revertTo = $vs->coupon_discount_previous_percent;
            $brand = $vs->brand;
            $line = "  {$brand?->name}: reverting {$wasPercent}% → {$revertTo}%";
            $this->line($line);

            if ($this->option('dry-run')) continue;

            $vs->forceFill([
                'coupon_discount_percent' => $revertTo,
                'coupon_discount_previous_percent' => null,
                'coupon_boost_expires_at' => null,
            ])->save();

            $this->postDiscordExpiry($brand?->name ?? 'Unknown', $wasPercent, $revertTo);
        }

        $this->info("Reverted {$expired->count()} expired boost(s).");
        return self::SUCCESS;
    }

    private function postDiscordExpiry(string $brandName, $wasPct, $nowPct): void
    {
        $token = config('services.discord.bot_token');
        $channel = config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        try {
            Http::withHeaders(['Authorization' => 'Bot ' . $token, 'Content-Type' => 'application/json'])
                ->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                    'content' => "⏱ **{$brandName}** coupon boost expired — reverted from **{$wasPct}%** back to **{$nowPct}%**.",
                ]);
        } catch (\Throwable $e) {
            Log::warning('coupon boost expiry Discord post failed', ['err' => $e->getMessage()]);
        }
    }
}

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

            $revertCode = $vs->coupon_code_previous;
            $updates = [
                'coupon_discount_percent' => $revertTo,
                'coupon_discount_previous_percent' => null,
                'coupon_boost_expires_at' => null,
                'coupon_boost_starts_at' => null,
                'coupon_boost_percent' => null,
                'coupon_boost_code' => null,
                'coupon_code_previous' => null,
            ];
            // Only restore the code if we swapped it (previous was
            // snapshotted). Leave alone otherwise so vendors whose
            // boost didn't touch the code aren't unexpectedly reset.
            if ($revertCode !== null) {
                $updates['coupon_code'] = $revertCode;
            }
            $vs->forceFill($updates)->save();

            // Colin Sep 23 — "promo ended" post removed. Nobody in
            // the deals channel cares that a promo ended; the boost
            // pill just disappears from the site and that's enough.
            // Kept the artisan log line so we can audit reversions.
        }

        $this->info("Reverted {$expired->count()} expired boost(s).");
        return self::SUCCESS;
    }
}

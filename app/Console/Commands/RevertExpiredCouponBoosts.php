<?php

namespace App\Console\Commands;

use App\Models\VendorSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reverts expired coupon boosts back to the vendor's own coupon.
 *
 * Julia applies a boost via the admin (e.g. Amino Club → 35% for 72h).
 * VendorSetting::applyCouponBoost saves the vendor's own % and code, and
 * when coupon_boost_expires_at passes this command puts them back and
 * clears the boost, so she never has to roll a promotion back by hand.
 *
 * Every boost has an end time, so that is all this selects on. It used
 * to also require a saved previous %, which a vendor with no discount of
 * its own never has, so those boosts never reverted (Certified Pep's
 * WEEKEND35, Nov 2026). The model keeps codes or percentages someone set
 * by hand on boosts that ended more than a day ago.
 *
 * Runs every 5 minutes via routes/console.php.
 */
class RevertExpiredCouponBoosts extends Command
{
    protected $signature = 'coupons:revert-expired-boosts {--dry-run}';
    protected $description = 'Put the vendor\'s own coupon back on vendors whose temporary boost has ended.';

    public function handle(): int
    {
        $expired = VendorSetting::whereNotNull('coupon_boost_expires_at')
            ->where('coupon_boost_expires_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired coupon boosts.');
            return self::SUCCESS;
        }

        foreach ($expired as $vs) {
            $before = $this->coupon($vs->coupon_discount_percent, $vs->coupon_code);

            if ($this->option('dry-run')) {
                $this->line("  {$vs->brand?->name}: ended {$vs->coupon_boost_expires_at->diffForHumans()}, live {$before}");
                continue;
            }

            $vs->revertExpiredCouponBoost();
            $vs->save();

            $after = $this->coupon($vs->coupon_discount_percent, $vs->coupon_code);
            $this->line("  {$vs->brand?->name}: {$before} → {$after}");
            Log::info('coupons:revert-expired-boosts reverted', [
                'brand_id' => $vs->brand_id,
                'from' => $before,
                'to' => $after,
            ]);

            // Colin Sep 23 — "promo ended" post removed. Nobody in
            // the deals channel cares that a promo ended; the boost
            // pill just disappears from the site and that's enough.
        }

        $this->info("Reverted {$expired->count()} expired boost(s).");
        return self::SUCCESS;
    }

    private function coupon($percent, $code): string
    {
        $parts = [];
        if ($percent !== null) {
            $parts[] = ((float) $percent).'%';
        }
        if ($code !== null && trim((string) $code) !== '') {
            $parts[] = $code;
        }

        return $parts ? implode(' ', $parts) : 'no coupon';
    }
}

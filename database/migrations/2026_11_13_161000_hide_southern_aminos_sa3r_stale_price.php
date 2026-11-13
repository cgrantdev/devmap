<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hides listing 3849, Southern Aminos "SA-3R" 10mg at $4.95.
 *
 * The price is stale: the vendor's page now 302s to a sign-in landing
 * page, and every other 10mg retatrutide listing costs several times
 * more. It led /product/retatrutide on price sort and showed as
 * "from $4.95" in search suggestions.
 *
 * Only touched while it is still that row at that price and visible.
 * down() shows it again if this migration hid it.
 */
return new class extends Migration
{
    private const PRODUCT_ID = 3849;

    private const BACKUP_KEY = 'hidden_stale_listing_3849';

    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('brands')) {
            return;
        }

        $row = DB::table('products')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where('products.id', self::PRODUCT_ID)
            ->first([
                'products.id',
                'products.price',
                'products.discount_price',
                'products.hidden',
                'brands.slug as brand_slug',
            ]);

        if (! $row || $row->brand_slug !== 'southern-aminos-llc' || (bool) $row->hidden) {
            return;
        }

        $effective = (float) ($row->discount_price ?: $row->price);
        if ($effective <= 0 || $effective >= 10) {
            return;
        }

        DB::table('products')->where('id', self::PRODUCT_ID)->update([
            'hidden' => true,
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                [
                    'value' => json_encode([
                        'product_id' => self::PRODUCT_ID,
                        'price' => $row->price,
                        'reason' => 'Stale $4.95 price; vendor URL redirects to a sign-in page.',
                        'hidden_at' => now()->toIso8601String(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        try {
            \App\Support\FrontendCache::bust();
        } catch (\Throwable $e) {
            // Cached pages expire on their own TTL.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('products')) {
            return;
        }

        if (! DB::table('settings')->where('key', self::BACKUP_KEY)->exists()) {
            return;
        }

        DB::table('products')->where('id', self::PRODUCT_ID)->update([
            'hidden' => false,
            'updated_at' => now(),
        ]);
        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};

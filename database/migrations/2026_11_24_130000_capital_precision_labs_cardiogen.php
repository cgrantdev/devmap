<?php

use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Files Capital Precision Labs' Cardiogen under /compare/cardiogen.
 *
 * 2026_11_24_120000 filed this vendor's Khavinson bioregulators but missed
 * Cardiogen: the vendor page's unstable sort hid it from the audit those
 * rules came from. Only a row with no compound is filed. down() puts it
 * back.
 */
return new class extends Migration
{
    private const BRAND_SLUG = 'capital-precision-labs';

    private const BACKUP_KEY = 'capital_precision_labs_cardiogen_backup';

    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('products') || ! Schema::hasTable('product_categories')) {
            return;
        }

        $brandId = DB::table('brands')->where('slug', self::BRAND_SLUG)->value('id');
        $categoryId = ProductCategory::findForCompareSlug('cardiogen')?->id;
        if (! $brandId || ! $categoryId) {
            return;
        }

        $ids = DB::table('products')
            ->where('brand_id', $brandId)
            ->whereNull('product_category_id')
            ->where('name', 'like', 'Cardiogen%')
            ->pluck('id')
            ->all();
        if ($ids === []) {
            return;
        }

        DB::table('products')->whereIn('id', $ids)->update(['product_category_id' => $categoryId, 'updated_at' => now()]);

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                ['value' => json_encode(['product_ids' => $ids]), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $backup = json_decode((string) DB::table('settings')->where('key', self::BACKUP_KEY)->value('value'), true);
        if (! empty($backup['product_ids'])) {
            DB::table('products')->whereIn('id', $backup['product_ids'])->update(['product_category_id' => null, 'updated_at' => now()]);
        }
        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};

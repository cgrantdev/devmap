<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert products.size_mg from decimal(10,2) to VARCHAR(50) so we can
 * store blend ratios like "5mg/5mg" and "50mg/10mg/10mg/10mg" alongside
 * regular numeric sizes ("10mg", "100mcg", etc.). Raw ALTER TABLE used
 * to avoid the doctrine/dbal dependency that ->change() requires.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'size_mg')) {
            DB::statement('ALTER TABLE products MODIFY size_mg VARCHAR(50) NULL');

            // Normalize existing decimal-as-string values: "10.00" → "10mg",
            // "9.50" → "9.5mg", etc. Bare nulls/empties stay as is.
            DB::table('products')
                ->whereNotNull('size_mg')
                ->where('size_mg', '!=', '')
                ->orderBy('id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        $val = (string) $row->size_mg;
                        // If it already has letters (mg / mcg / blend), leave it.
                        if (preg_match('/[a-zA-Z\/]/', $val)) continue;
                        if (!is_numeric($val)) continue;
                        // Strip trailing zeros: 10.00 → 10, 9.50 → 9.5
                        $cleaned = rtrim(rtrim($val, '0'), '.');
                        if ($cleaned === '') continue;
                        DB::table('products')->where('id', $row->id)->update(['size_mg' => $cleaned . 'mg']);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'size_mg')) {
            // Strip the "mg" suffix and any non-numeric values before reverting
            DB::table('products')
                ->whereNotNull('size_mg')
                ->orderBy('id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        $val = (string) $row->size_mg;
                        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)mg$/i', $val, $m)) {
                            DB::table('products')->where('id', $row->id)->update(['size_mg' => $m[1]]);
                        } elseif (!is_numeric($val)) {
                            // Blend strings can't fit decimal — null them
                            DB::table('products')->where('id', $row->id)->update(['size_mg' => null]);
                        }
                    }
                });

            DB::statement('ALTER TABLE products MODIFY size_mg DECIMAL(10,2) NULL');
        }
    }
};

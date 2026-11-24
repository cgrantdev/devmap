<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Runs vistara:hide-legacy-duplicates once (Colin, 25 Nov 2026: "do #1").
 *
 * Vistara Labs has about 66 hand-entered rows beside the rows vistara:sync
 * keeps from their price library, often sized per spray ("BPC-157 Nasal
 * Spray 350mcg" next to "10mg"), which gave absurd per-mg prices; two sat
 * under Semaglutide. The command copies a missing image or compound onto
 * the synced row, then hides the legacy row when a synced row covers the
 * same product page. Nothing is deleted.
 *
 * The command's output, with every hidden id, is kept in settings and the
 * log; down() shows those rows again.
 */
return new class extends Migration
{
    private const RESULT_KEY = 'vistara_legacy_duplicates_2026_11_25';

    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('products')) {
            return;
        }
        if (! DB::table('brands')->where('slug', 'vistara-labs')->exists()) {
            return;
        }

        Artisan::call('vistara:hide-legacy-duplicates');
        $output = Artisan::output();
        Log::info('vistara:hide-legacy-duplicates (migration 2026_11_25_120000)', ['output' => $output]);

        preg_match_all('~^HIDE\s+#(\d+)~m', $output, $matches);
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::RESULT_KEY],
                [
                    'value' => json_encode([
                        'hidden_ids' => array_map('intval', $matches[1] ?? []),
                        'output' => mb_substr($output, 0, 60000),
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $saved = json_decode((string) DB::table('settings')->where('key', self::RESULT_KEY)->value('value'), true);
        if (! empty($saved['hidden_ids'])) {
            DB::table('products')->whereIn('id', $saved['hidden_ids'])->update(['hidden' => false, 'updated_at' => now()]);
        }
        DB::table('settings')->where('key', self::RESULT_KEY)->delete();
    }
};

<?php

use App\Models\ScrapingConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capital Precision Labs listed 3 products out of the 242 their sitemap
 * lists.
 *
 * Their store is a custom Next.js site with no product API. The admin
 * "Discover Products" button read their homepage once, which links 8
 * products, named them from the URL ("Cp1 Sg 10mg") and hid the 5 whose
 * names matched no compound. The daily "Page Scraper" only refreshes
 * products we already list, and the scheduler sent it to the Python CSS
 * scraper, which needs selectors this config never had.
 *
 * Every product page carries Product JSON-LD ("Semaglutide 10mg", A$33
 * for a single vial), so the vendor moves to the JSON-LD importer reading
 * their sitemap: once a day, half a second between pages. Its first run
 * renames and categorizes the 8 discovered rows (IngestionService::
 * placeholderRepairs) and adds the rest. Other configs for the vendor are
 * switched off so the same pages are not fetched twice.
 *
 * down() restores the config as it was and turns the others back on.
 */
return new class extends Migration
{
    private const BRAND_SLUG = 'capital-precision-labs';

    private const STORE = 'https://capitalprecisionlabs.com';

    private const BACKUP_KEY = 'capital_precision_labs_scraping_backup';

    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('scraping_configs')) {
            return;
        }

        $brand = DB::table('brands')->where('slug', self::BRAND_SLUG)->first(['id', 'name']);
        if (! $brand) {
            return;
        }

        $configs = ScrapingConfig::where('vendor_id', $brand->id)->orderBy('id')->get();
        $config = $configs->first();
        $others = $configs->slice(1)->filter(fn (ScrapingConfig $c) => (bool) $c->enabled);

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                [
                    'value' => json_encode([
                        'config_id' => $config?->id,
                        'original' => $config ? array_intersect_key($config->getRawOriginal(), array_flip([
                            'type', 'products_url', 'store_url', 'selectors', 'auth_credentials',
                            'frequency', 'auto_promote', 'product_category_id', 'enabled', 'next_run_at',
                        ])) : null,
                        'disabled_config_ids' => $others->pluck('id')->values()->all(),
                    ]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $config ??= new ScrapingConfig(['vendor_id' => $brand->id]);
        try {
            $credentials = is_array($config->auth_credentials) ? $config->auth_credentials : [];
        } catch (\Throwable) {
            // Unreadable (encrypted under another key): a JSON-LD import
            // needs no credentials, and a failed migration would hold up
            // every later one on deploy.
            $credentials = [];
        }

        $config->fill([
            'vendor_name' => $brand->name,
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => self::STORE.'/sitemap.xml',
            'store_url' => self::STORE,
            'selectors' => ['max_products' => 400],
            'auth_credentials' => array_merge($credentials, ['currency_code' => 'AUD']),
            // A pinned category would file every product under one compound.
            'product_category_id' => null,
            'enabled' => true,
            'frequency' => 'daily',
            'auto_promote' => true,
            'last_error' => null,
            // Due now: the scheduler picks it up a minute after deploy.
            'next_run_at' => now(),
        ])->save();

        if ($others->isNotEmpty()) {
            ScrapingConfig::whereKey($others->pluck('id'))->update(['enabled' => false]);
        }

        if ($configs->isEmpty() && Schema::hasTable('settings')) {
            $backup = json_decode((string) DB::table('settings')->where('key', self::BACKUP_KEY)->value('value'), true) ?: [];
            DB::table('settings')->where('key', self::BACKUP_KEY)->update([
                'value' => json_encode(array_merge($backup, ['created_config_id' => $config->id])),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('scraping_configs')) {
            return;
        }

        $backup = json_decode((string) DB::table('settings')->where('key', self::BACKUP_KEY)->value('value'), true);
        if (! is_array($backup)) {
            return;
        }

        if (! empty($backup['config_id']) && is_array($backup['original'] ?? null)) {
            DB::table('scraping_configs')->where('id', $backup['config_id'])->update($backup['original']);
        } elseif (! empty($backup['created_config_id'])) {
            DB::table('scraping_configs')->where('id', $backup['created_config_id'])->delete();
        }
        if (! empty($backup['disabled_config_ids'])) {
            DB::table('scraping_configs')->whereIn('id', $backup['disabled_config_ids'])->update(['enabled' => true]);
        }

        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};

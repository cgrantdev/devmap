<?php

use App\Models\ScrapingConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves six vendors that declined API keys onto an importer that reads
 * their whole catalog (25 Nov 2026). Each listed 0 to 26 products.
 *
 *   RC Fairtrade B.V.      207 products in the sitemap, schema.org Product per page (EUR)
 *   V8 Peptides             37 products, one schema.org offer per size (USD)
 *   East Coast 'Tides       35 products under /shop/ (CAD)
 *   Southern Labs           24 products; www. host fails TLS, bare domain works (NZD)
 *   Sterling Peptides USA   25 Wix products under /product-page/ (USD)
 *   NextSelf Labs           WooCommerce public Store API, every variation (USD)
 *
 * Each vendor's first config becomes the importer below, due now (the
 * scheduler runs it a minute after deploy), daily after that; its other
 * configs are switched off so pages are not fetched twice. Southern Labs'
 * shop URLs move from www. to the bare domain, which is the host whose
 * certificate is valid.
 *
 * down() restores every config and URL as it was.
 */
return new class extends Migration
{
    private const BACKUP_KEY = 'vendor_catalog_imports_2026_11_25_backup';

    private const CONFIG_COLUMNS = [
        'type', 'products_url', 'store_url', 'selectors', 'auth_credentials',
        'frequency', 'auto_promote', 'enabled', 'next_run_at', 'last_error',
    ];

    private const VENDORS = [
        'rc-fairtrade-bv' => [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => 'https://rcpeptides.com/sitemap.xml',
            'store_url' => 'https://rcpeptides.com',
            'selectors' => ['max_products' => 400],
            'currency' => 'EUR',
        ],
        'v8-peptides' => [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => 'https://v8peptides.com/sitemap.xml',
            'store_url' => 'https://v8peptides.com',
            'selectors' => ['max_products' => 200],
            'currency' => 'USD',
        ],
        'east-coast-tides' => [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => 'https://eastcoasttides.ca/sitemap.xml',
            'store_url' => 'https://eastcoasttides.ca',
            'selectors' => ['url_pattern' => '~/shop/[^/]+/?$~i', 'max_products' => 200],
            'currency' => 'CAD',
        ],
        'southern-labs' => [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => 'https://southernlabs.co.nz/sitemap.xml',
            'store_url' => 'https://southernlabs.co.nz',
            'selectors' => ['max_products' => 200],
            'currency' => 'NZD',
        ],
        'sterling-peptides-usa' => [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            // Their robots.txt points at yourdomain.com; the Wix product sitemap is here.
            'products_url' => 'https://www.sterlingpeptidesusa.com/store-products-sitemap.xml',
            'store_url' => 'https://www.sterlingpeptidesusa.com',
            'selectors' => ['url_pattern' => '~/product-page/[^/]+/?$~i', 'max_products' => 200],
            'currency' => 'USD',
        ],
        'nextself-labs' => [
            'type' => ScrapingConfig::TYPE_WOO_STORE,
            'products_url' => 'https://nextselflabs.com',
            'store_url' => 'https://nextselflabs.com',
            'selectors' => ['max_pages' => 20],
            'currency' => null,
        ],
    ];

    private const SHOP_URL_FIXES = [
        'southern-labs' => ['from' => 'https://www.southernlabs.co.nz', 'to' => 'https://southernlabs.co.nz'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('scraping_configs')) {
            return;
        }

        $backup = ['configs' => [], 'shop_urls' => []];

        foreach (self::VENDORS as $slug => $spec) {
            $brand = DB::table('brands')->where('slug', $slug)->first(['id', 'name']);
            if (! $brand) {
                continue;
            }

            $configs = ScrapingConfig::where('vendor_id', $brand->id)->orderBy('id')->get();
            $config = $configs->first();
            $others = $configs->slice(1)->filter(fn (ScrapingConfig $c) => (bool) $c->enabled);
            $backup['configs'][$slug] = [
                'config_id' => $config?->id,
                'original' => $config ? array_intersect_key($config->getRawOriginal(), array_flip(self::CONFIG_COLUMNS)) : null,
                'disabled_config_ids' => $others->pluck('id')->values()->all(),
            ];

            $config ??= new ScrapingConfig(['vendor_id' => $brand->id]);
            try {
                $credentials = is_array($config->auth_credentials) ? $config->auth_credentials : [];
            } catch (\Throwable) {
                // Unreadable under this key; these importers need none, and a
                // failed migration would hold up every later one on deploy.
                $credentials = [];
            }
            if ($spec['currency'] !== null) {
                $credentials['currency_code'] = $spec['currency'];
            }

            $config->fill([
                'vendor_name' => $brand->name,
                'type' => $spec['type'],
                'products_url' => $spec['products_url'],
                'store_url' => $spec['store_url'],
                'selectors' => $spec['selectors'],
                'auth_credentials' => $credentials !== [] ? $credentials : null,
                'enabled' => true,
                'frequency' => 'daily',
                'auto_promote' => true,
                'last_error' => null,
                'next_run_at' => now(),
            ])->save();

            if ($configs->isEmpty()) {
                $backup['configs'][$slug]['created_config_id'] = $config->id;
            }
            if ($others->isNotEmpty()) {
                ScrapingConfig::whereKey($others->pluck('id'))->update(['enabled' => false]);
            }
        }

        if (Schema::hasTable('vendor_settings')) {
            foreach (self::SHOP_URL_FIXES as $slug => $fix) {
                $brandId = DB::table('brands')->where('slug', $slug)->value('id');
                $row = $brandId ? DB::table('vendor_settings')->where('brand_id', $brandId)->first(['id', 'shop_url', 'website', 'referral_url']) : null;
                if (! $row) {
                    continue;
                }
                $changes = [];
                foreach (['shop_url', 'website', 'referral_url'] as $column) {
                    $value = (string) ($row->{$column} ?? '');
                    if ($value !== '' && stripos($value, $fix['from']) === 0) {
                        $changes[$column] = $fix['to'].substr($value, strlen($fix['from']));
                    }
                }
                if ($changes !== []) {
                    $backup['shop_urls'][$row->id] = array_intersect_key((array) $row, $changes);
                    DB::table('vendor_settings')->where('id', $row->id)->update($changes + ['updated_at' => now()]);
                }
            }
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                ['value' => json_encode($backup), 'updated_at' => now(), 'created_at' => now()]
            );
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

        foreach ($backup['configs'] ?? [] as $entry) {
            if (! empty($entry['config_id']) && is_array($entry['original'] ?? null)) {
                DB::table('scraping_configs')->where('id', $entry['config_id'])->update($entry['original']);
            } elseif (! empty($entry['created_config_id'])) {
                DB::table('scraping_configs')->where('id', $entry['created_config_id'])->delete();
            }
            if (! empty($entry['disabled_config_ids'])) {
                DB::table('scraping_configs')->whereIn('id', $entry['disabled_config_ids'])->update(['enabled' => true]);
            }
        }

        foreach ($backup['shop_urls'] ?? [] as $id => $values) {
            DB::table('vendor_settings')->where('id', (int) $id)->update($values + ['updated_at' => now()]);
        }

        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};

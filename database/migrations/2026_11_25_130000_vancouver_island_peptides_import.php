<?php

use App\Models\ScrapingConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vancouver Island Peptides listed 0 products although they built a JSON
 * feed. Two of our paths could not take it: a feed URL saved in the
 * vendor's "Product Feed" field goes to an XML-only parser
 * (feeds:sync-vendors), and the JSON feed importer dropped every row
 * because their product pages are .html files (fixed in 53ada21).
 *
 * This picks the path that can work, in order:
 *   1. an enabled json_feed config: run it again now;
 *   2. a JSON feed URL in vendor_settings.feed_url: the first config
 *      becomes a json_feed importer for it;
 *   3. otherwise their product pages: every page in the sitemap carries a
 *      schema.org Product per variant (CAD, packs of 10 vials).
 * Other configs are switched off. down() restores the configs.
 */
return new class extends Migration
{
    private const BRAND_SLUG = 'vancouver-island-peptides';

    private const BACKUP_KEY = 'vancouver_island_peptides_import_backup';

    private const CONFIG_COLUMNS = [
        'type', 'products_url', 'store_url', 'selectors', 'auth_credentials',
        'frequency', 'auto_promote', 'enabled', 'next_run_at', 'last_error',
    ];

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
        $backup = [
            'originals' => $configs->mapWithKeys(fn (ScrapingConfig $c) => [
                $c->id => array_intersect_key($c->getRawOriginal(), array_flip(self::CONFIG_COLUMNS)),
            ])->all(),
            'created_config_id' => null,
            'path' => null,
        ];

        $feed = $configs->first(fn (ScrapingConfig $c) => $c->type === ScrapingConfig::TYPE_JSON_FEED
            && $c->enabled
            && trim((string) $c->products_url) !== '');

        if ($feed) {
            $keep = $feed;
            $keep->forceFill(['next_run_at' => now(), 'last_error' => null])->save();
            $backup['path'] = 'json_feed config re-run';
        } else {
            $feedUrl = Schema::hasTable('vendor_settings')
                ? trim((string) DB::table('vendor_settings')->where('brand_id', $brand->id)->value('feed_url'))
                : '';
            $isJson = $feedUrl !== '' && preg_match('~\.json(?:$|\?)|/api/|json~i', $feedUrl) === 1;

            $keep = $configs->first() ?? new ScrapingConfig(['vendor_id' => $brand->id]);
            try {
                $credentials = is_array($keep->auth_credentials) ? $keep->auth_credentials : [];
            } catch (\Throwable) {
                $credentials = [];
            }
            $credentials['currency_code'] = 'CAD';

            $keep->fill($isJson
                ? [
                    'type' => ScrapingConfig::TYPE_JSON_FEED,
                    'products_url' => $feedUrl,
                    'selectors' => null,
                ]
                : [
                    'type' => ScrapingConfig::TYPE_JSON_LD,
                    'products_url' => 'https://vancouverislandpeptides.com/sitemap.xml',
                    'selectors' => ['url_pattern' => '~\.html$~i', 'max_products' => 300],
                ]);
            $keep->fill([
                'vendor_name' => $brand->name,
                'store_url' => 'https://vancouverislandpeptides.com',
                'auth_credentials' => $credentials,
                'enabled' => true,
                'frequency' => 'daily',
                'auto_promote' => true,
                'last_error' => null,
                'next_run_at' => now(),
            ])->save();

            if ($configs->isEmpty()) {
                $backup['created_config_id'] = $keep->id;
            }
            $backup['path'] = $isJson ? 'json_feed from vendor_settings.feed_url' : 'json_ld from product pages';
        }

        ScrapingConfig::where('vendor_id', $brand->id)->whereKeyNot($keep->id)->where('enabled', true)->update(['enabled' => false]);

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

        foreach ($backup['originals'] ?? [] as $id => $original) {
            DB::table('scraping_configs')->where('id', (int) $id)->update($original);
        }
        if (! empty($backup['created_config_id'])) {
            DB::table('scraping_configs')->where('id', $backup['created_config_id'])->delete();
        }

        DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
    }
};

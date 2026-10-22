<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Centralizes ingestion: upserts incoming normalized product data into the
 * scraped_products staging table, and promotes staging rows into live products
 * either automatically (for trusted configs) or via admin review.
 *
 * Adapters (CSS scraper, WooCommerce API, XML feed) should normalize their
 * data into the shape accepted by upsertStaged() and call into this service.
 * They should never write to the products table directly.
 */
class IngestionService
{
    /**
     * Upsert a single normalized product into the staging table.
     *
     * $data keys (all optional unless noted):
     *   external_id (string)  - vendor-side unique id (required for stable upsert)
     *   source_url  (string)  - product page URL (fallback dedupe key)
     *   name        (string)  - product name (required)
     *   description (string)
     *   price       (numeric)
     *   discount_price (numeric)
     *   image_url   (string)
     *   stock_status(string)
     *   dosage      (string)
     *   raw_data    (array)
     */
    public function upsertStaged(ScrapingConfig $config, array $data): ?ScrapedProduct
    {
        if (empty($data['name'])) {
            Log::warning('IngestionService: skipping staged product with empty name', [
                'config_id' => $config->id,
            ]);
            return null;
        }

        // Reject rows whose name is clearly an HTML <title> tag rather
        // than a real product name — Julia flagged four such rows from
        // CertaPeptides (Sep 12): the scraper hit /shop/{compound}
        // archive pages, missed the product H1, and cached the
        // browser-tab title (e.g. "Shop Research Peptides | CertaPeptides"
        // or "Semax | CertaPeptides") with an empty price. Two signals
        // that catch the pollution without touching real products:
        //   (a) name begins with "Shop " — WooCommerce shop archive
        //   (b) name ends with " | <VendorName>" AND price is missing —
        //       WordPress default page-title format with no scrape data
        if ($this->looksLikeHtmlPageTitle($data['name'], $config, $data['price'] ?? null)) {
            Log::warning('IngestionService: rejecting HTML-title-shaped row', [
                'config_id' => $config->id,
                'name' => $data['name'],
                'source_url' => $data['source_url'] ?? null,
            ]);
            return null;
        }

        // Reject known non-product line items — checkout add-ons that
        // WooCommerce feeds surface alongside real products. Julia
        // flagged 100 "Ship-Safely Shipping Protection — X.XXmg" rows
        // from Oneday Compounds (Sep 12): the Ship-Safely plugin
        // creates a shipping-insurance variant for every $0.30 price
        // band and each one gets its own product_id in the feed.
        // Match on name substrings that never appear on a real peptide.
        if ($this->looksLikeCheckoutAddon($data['name'])) {
            Log::warning('IngestionService: rejecting checkout-addon row', [
                'config_id' => $config->id,
                'name' => $data['name'],
            ]);
            return null;
        }

        $matchKeys = [
            'scraping_config_id' => $config->id,
        ];

        if (!empty($data['external_id'])) {
            $matchKeys['external_id'] = $data['external_id'];
        } elseif (!empty($data['source_url'])) {
            $matchKeys['source_url'] = $data['source_url'];
        } else {
            $matchKeys['name'] = $data['name'];
        }

        $existing = ScrapedProduct::where($matchKeys)->first();

        $attributes = [
            'scraping_config_id' => $config->id,
            'brand_id' => $config->vendor_id, // vendor_id is treated as brand_id
            'source_type' => $data['source_type'] ?? $config->type ?? ScrapedProduct::SOURCE_CSS_SCRAPE,
            'external_id' => $data['external_id'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $this->normalizePrice($data['price'] ?? null),
            'discount_price' => $this->normalizePrice($data['discount_price'] ?? null),
            'image_url' => $data['image_url'] ?? null,
            'stock_status' => $data['stock_status'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'raw_data' => $data['raw_data'] ?? null,
            'last_scraped_at' => now(),
        ];

        if ($existing) {
            // Detect price change so analytics / promotion can react
            if ($existing->price !== null && $attributes['price'] !== null
                && (string) $existing->price !== (string) $attributes['price']) {
                $attributes['previous_price'] = $existing->price;
                $attributes['price_changed_at'] = now();
            }

            // Don't overwrite admin overrides
            if (!$existing->manual_override) {
                $existing->fill($attributes)->save();
            }

            $staged = $existing;
        } else {
            $attributes['status'] = ScrapedProduct::STATUS_PENDING;
            $staged = ScrapedProduct::create($attributes);
        }

        // Auto-promote trusted configs
        if ($config->auto_promote) {
            $this->promote($staged);
        }

        return $staged;
    }

    /**
     * URL path patterns we accept as real product pages. Reject anything
     * else (homepages, /shop landings without a slug, /about, /blog,
     * /collections roots) — those were the source of the 404 pollution
     * rows Julia flagged Sep 1 (og:image = vendor logo, no price, name
     * from og:title like "BPC-157 Capsules - Shop").
     */
    // Use ~ as the delimiter — the pattern's char class includes # (fragment
    // separator), which collides with # as the delimiter and produces
    // 'Unknown modifier' errors at runtime.
    private const PRODUCT_URL_PATTERN = '~/(product|products|shop|store|item|p)/[^/?#]+~i';

    public function looksLikeProductUrl(?string $url): bool
    {
        if (!$url) return false;
        return (bool) preg_match(self::PRODUCT_URL_PATTERN, $url);
    }

    /**
     * True when `name` is more plausibly an HTML <title> than a product
     * name. Matches two documented pollution shapes:
     *   - Starts with "Shop " (WooCommerce shop archive default title)
     *   - Ends with " | VendorName" AND the row carries no price — the
     *     WordPress `Post Title | Site Name` fallback picked up when the
     *     scraper couldn't find the product H1. Requiring null price
     *     avoids rejecting legitimate branded SKUs that use " | Vendor".
     */
    /**
     * True when `name` is a WooCommerce checkout add-on (shipping
     * insurance, gift wrap, tips) rather than a real product. These
     * plugins create one product_id per price tier so a peptide feed
     * of ~50 products can grow into ~150 with 100 add-on line items.
     */
    private function looksLikeCheckoutAddon(string $name): bool
    {
        static $patterns = [
            'ship-safely',           // Ship-Safely Shipping Protection plugin
            'shipping protection',   // generic shipping-insurance add-ons
            'shipping insurance',
            'route protection',      // Route.com shipping insurance
            'seel protection',       // Seel shipping/returns coverage
        ];
        $lower = strtolower($name);
        foreach ($patterns as $needle) {
            if (str_contains($lower, $needle)) return true;
        }
        return false;
    }

    private function looksLikeHtmlPageTitle(string $name, ScrapingConfig $config, mixed $price): bool
    {
        if (preg_match('/^Shop\s+[A-Z]/', $name)) {
            return true;
        }
        $hasPrice = $price !== null && $price !== '' && (float) $price > 0;
        if ($hasPrice) return false;

        $vendorName = $config->vendor_name ?? '';
        if ($vendorName !== '' && preg_match('/\s\|\s' . preg_quote($vendorName, '/') . '\s*$/i', $name)) {
            return true;
        }
        return false;
    }

    /**
     * Promote a staged product into the live products table.
     * If the staged row already has a product_id, updates the existing product.
     * Otherwise creates a new Product and links it.
     *
     * Returns null when the staged row fails a sanity check — it gets
     * auto-rejected instead so it can't pollute the live products table —
     * or when the URL matches more than one live row for the vendor. That
     * second case leaves the staged row pending. Callers must handle null
     * (StagedProductsController + bulkPromote).
     */
    public function promote(ScrapedProduct $staged): ?Product
    {
        // --- Sanity checks — reject pollution before it lands live -----
        // Colin Sep 1: "it needs a price for sure … and ya, it should
        // probably be /products/ /product/ /shop/ that sorta stuff."
        $hasPrice = ($staged->price !== null && (float) $staged->price > 0)
            || ($staged->discount_price !== null && (float) $staged->discount_price > 0);

        if (!$hasPrice) {
            $this->reject($staged, 'No price — scrape pollution guard');
            return null;
        }

        $brandId = $staged->brand_id ?? $staged->scrapingConfig?->vendor_id;
        $resolved = $this->resolveExistingLiveProduct($staged, $brandId);
        if ($resolved['ambiguous']) {
            Log::warning('promote: ambiguous product url match', [
                'staged_id' => $staged->id,
                'brand_id' => $brandId,
                'url' => $staged->source_url,
                'candidate_ids' => $resolved['candidate_ids'],
            ]);

            return null;
        }

        $existing = $resolved['product'];
        $matchType = $resolved['match_type'];

        // Only accept URLs that look like a product detail page. Homepage,
        // /shop landing, /about, /blog, etc. all fail this. Skip the check
        // when the source_url is null (some feeds don't carry it — but that
        // usually means the row came from an authenticated API where the
        // shape is trustworthy, e.g. WooCommerce REST), and when the row
        // uniquely matches a live product for this vendor.
        $patternFails = $staged->source_url && !$this->looksLikeProductUrl($staged->source_url);
        if (!$existing && $patternFails) {
            $this->reject($staged, 'Source URL does not look like a product page: ' . $staged->source_url);
            return null;
        }

        if ($existing && $patternFails) {
            Log::info('promote: url-pattern bypass', [
                'staged_id' => $staged->id,
                'product_id' => $existing->id,
                'match_type' => $matchType,
                'url' => $staged->source_url,
            ]);
        }

        return DB::transaction(function () use ($staged, $brandId, $existing, $patternFails) {
            // Resolve product_category_id (used only for NEW products — see below):
            //   1. Use the scraping config's category if it pinned one (legacy single-category vendors)
            //   2. Otherwise auto-match the product name against the category_aliases table
            $categoryId = $staged->scrapingConfig?->product_category_id
                ?? $this->matchCategoryByName($staged->name);

            // Find an existing Product to update instead of creating a
            // duplicate. Priority:
            //   1. staged->product_id, only when that product is this vendor
            //   2. Same brand + non-blank external_id
            //   3. Same brand + unique normalized product URL
            //   4. Same brand + exact name match (fallback for feeds that
            //      don't carry stable URLs, e.g. some JSON feeds)
            //
            // Colin Sep 7: 'why is our scrapes creating NEW products?' —
            // previously this method fell straight through to Product::create
            // whenever staged->product_id was null, which happens on every
            // fresh scraping_config or when a config is recreated. Result:
            // 140 duplicate live rows across Instant Peptides + Peptselect
            // by the time we caught it.
            // Name matching does not bypass the URL-pattern guard: a bad
            // URL never reaches this point unless a stronger match exists.
            $product = $existing;
            if (!$product && $brandId && !empty($staged->name)) {
                $byName = Product::where('brand_id', $brandId)
                    ->where('name', $staged->name)
                    ->orderBy('id')
                    ->limit(2)
                    ->get();
                if ($byName->count() > 1) {
                    Log::warning('promote: ambiguous product name match', [
                        'staged_id' => $staged->id,
                        'brand_id' => $brandId,
                        'candidate_ids' => $byName->pluck('id')->all(),
                    ]);

                    return null;
                }
                $product = $byName->first();
            }

            if ($product) {
                // Existing product — preserve everything a VA might have
                // curated (name, category, type, size, slug) and only refresh
                // upstream-owned fields (price, image, stock, description).
                //
                // Otherwise an auto-sync would clobber "DSIP" back to
                // "DSIP (Delta Sleep Inducing Peptide 5mg)" and undo the work
                // of every triage pass.
                if ($product->auto_update || $staged->manual_override) {
                    $updates = [
                        'description' => $staged->description,
                        'price' => $staged->price,
                        'discount_price' => $staged->discount_price,
                        'image_url' => $staged->image_url,
                        'auto_scraped' => true,
                        'last_scraped_at' => $staged->last_scraped_at ?? now(),
                    ];
                    // Catalog matches and failing URLs keep the stored
                    // product_url. A name-match refresh may store the staged
                    // URL only after tracking params and the fragment are gone.
                    if (!$existing && !$patternFails) {
                        $storedUrl = $this->urlWithoutTracking($staged->source_url);
                        if ($storedUrl !== null) {
                            $updates['product_url'] = $storedUrl;
                        }
                    }
                    $product->update($updates);
                }
                // Backfill the link so subsequent promotes hit tier (1)
                // directly and skip the URL/name lookups.
                if (!$staged->product_id) {
                    $staged->product_id = $product->id;
                }
            } else {
                // Truly new product — nothing matched.
                $product = Product::create([
                    'name' => $staged->name,
                    'slug' => $this->uniqueSlug($staged->name),
                    'description' => $staged->description,
                    'brand_id' => $brandId,
                    'product_category_id' => $categoryId,
                    'price' => $staged->price,
                    'discount_price' => $staged->discount_price,
                    'image_url' => $staged->image_url,
                    'product_url' => $this->urlWithoutTracking($staged->source_url),
                    'auto_scraped' => true,
                    'last_scraped_at' => $staged->last_scraped_at ?? now(),
                ]);
                $staged->product_id = $product->id;
            }

            $staged->status = ScrapedProduct::STATUS_APPROVED;
            $staged->save();

            return $product;
        });
    }

    /**
     * @return array{product: ?Product, match_type: ?string, ambiguous: bool, candidate_ids: list<int>}
     */
    protected function resolveExistingLiveProduct(ScrapedProduct $staged, ?int $brandId): array
    {
        $none = ['product' => null, 'match_type' => null, 'ambiguous' => false, 'candidate_ids' => []];

        if ($staged->product_id) {
            $linked = Product::find($staged->product_id);
            if ($linked && $brandId && (int) $linked->brand_id === (int) $brandId) {
                return [
                    'product' => $linked,
                    'match_type' => 'product_id',
                    'ambiguous' => false,
                    'candidate_ids' => [],
                ];
            }
            if ($linked) {
                Log::warning('promote: linked product brand mismatch', [
                    'staged_id' => $staged->id,
                    'product_id' => $linked->id,
                    'linked_brand_id' => $linked->brand_id,
                    'staged_brand_id' => $brandId,
                ]);
            }
        }

        $externalId = trim((string) ($staged->external_id ?? ''));
        if ($brandId && $externalId !== '') {
            $byExternalId = Product::where('brand_id', $brandId)
                ->where('external_id', $externalId)
                ->first();
            if ($byExternalId) {
                return [
                    'product' => $byExternalId,
                    'match_type' => 'external_id',
                    'ambiguous' => false,
                    'candidate_ids' => [],
                ];
            }
        }

        if (!$brandId || empty($staged->source_url)) {
            return $none;
        }

        return $this->matchLiveProductByUrl($staged->source_url, $brandId);
    }

    /**
     * Same-vendor URL match. The identity keeps variant query keys and drops
     * tracking keys and the fragment. A match is used only when it is unique
     * and no sibling row shares the host and path but differs by query.
     * Variant permalinks (?attribute_pa_size=) are unique when every sibling
     * also has its own variant query. A query-less parent next to a variant,
     * or several rows with the same URL, is ambiguous.
     *
     * @return array{product: ?Product, match_type: ?string, ambiguous: bool, candidate_ids: list<int>}
     */
    protected function matchLiveProductByUrl(string $url, int $brandId): array
    {
        $none = ['product' => null, 'match_type' => null, 'ambiguous' => false, 'candidate_ids' => []];
        $identity = $this->urlIdentity($url);
        if ($identity === null) {
            return $none;
        }

        $candidates = $this->candidateProductsForUrl($brandId, $identity['host'], $identity['path']);
        $exact = [];
        $samePath = [];
        foreach ($candidates as $candidate) {
            $other = $this->urlIdentity($candidate->product_url);
            if ($other === null || $other['path_key'] !== $identity['path_key']) {
                continue;
            }
            $samePath[] = $candidate;
            if ($other['query_key'] === $identity['query_key']) {
                $exact[] = $candidate;
            }
        }

        $ids = array_map(fn (Product $product) => (int) $product->id, $samePath);

        if (count($exact) === 1 && count($samePath) === 1) {
            return [
                'product' => $exact[0],
                'match_type' => 'url',
                'ambiguous' => false,
                'candidate_ids' => $ids,
            ];
        }

        if (count($exact) === 1 && $identity['query_key'] !== '') {
            $parentSibling = false;
            foreach ($samePath as $row) {
                if ((int) $row->id === (int) $exact[0]->id) {
                    continue;
                }
                $other = $this->urlIdentity($row->product_url);
                if ($other === null || $other['query_key'] === '') {
                    $parentSibling = true;
                    break;
                }
            }
            if (!$parentSibling) {
                return [
                    'product' => $exact[0],
                    'match_type' => 'url',
                    'ambiguous' => false,
                    'candidate_ids' => $ids,
                ];
            }
        }

        if ($samePath !== []) {
            return [
                'product' => null,
                'match_type' => null,
                'ambiguous' => true,
                'candidate_ids' => $ids,
            ];
        }

        return $none;
    }

    /**
     * Products for this vendor whose URL might be the same path.
     * brand_id is indexed; the host and path fragments keep this off a full catalog load.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    protected function candidateProductsForUrl(int $brandId, string $host, string $path)
    {
        $query = Product::query()
            ->where('brand_id', $brandId)
            ->whereNotNull('product_url')
            ->where('product_url', '!=', '')
            ->whereRaw("product_url LIKE ? ESCAPE '\\'", ['%'.$this->likeLiteral($host).'%']);

        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        $pathNeedle = $segments === [] ? '' : end($segments);
        if ($pathNeedle !== '') {
            $query->whereRaw("product_url LIKE ? ESCAPE '\\'", ['%'.$this->likeLiteral($pathNeedle).'%']);
        }

        return $query->orderBy('id')->get();
    }

    protected function likeLiteral(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @return array{host: string, path: string, path_key: string, query_key: string}|null
     */
    protected function urlIdentity(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $path = preg_replace('#/+#', '/', $parts['path'] ?? '') ?? '';
        $path = rtrim($path, '/');

        return [
            'host' => $host,
            'path' => $path,
            'path_key' => $host.$path,
            'query_key' => $this->canonicalQueryKey($parts['query'] ?? ''),
        ];
    }

    /**
     * Query identity with variant keys kept and tracking keys removed.
     * attribute_*, variation_id, and variant stay. utm_*, gclid, fbclid, and ref go.
     */
    protected function canonicalQueryKey(string $query): string
    {
        if ($query === '') {
            return '';
        }

        parse_str($query, $params);
        $kept = [];
        foreach ($params as $key => $value) {
            if (!is_string($key) || $this->isTrackingQueryKey($key)) {
                continue;
            }
            $kept[$key] = $value;
        }
        if ($kept === []) {
            return '';
        }
        ksort($kept);

        return http_build_query($kept);
    }

    protected function isTrackingQueryKey(string $key): bool
    {
        $key = strtolower($key);

        return str_starts_with($key, 'utm_') || in_array($key, ['gclid', 'fbclid', 'ref'], true);
    }

    /**
     * URL safe to persist: no fragment, no tracking query keys.
     * Path, host, and variant query keys are left as given.
     */
    protected function urlWithoutTracking(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = $this->canonicalQueryKey($parts['query'] ?? '');
        $stored = $scheme.'://'.$parts['host'].$port.$path;
        if ($query !== '') {
            $stored .= '?'.$query;
        }

        return $stored;
    }

    public function reject(ScrapedProduct $staged, ?string $reason = null): void
    {
        $staged->status = ScrapedProduct::STATUS_REJECTED;
        $staged->save();

        Log::info('Staged product rejected', [
            'scraped_product_id' => $staged->id,
            'reason' => $reason,
        ]);
    }

    protected function normalizePrice($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }
        // Strip currency symbols, thousands separators
        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        return $clean !== '' ? number_format((float) $clean, 2, '.', '') : null;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'product-' . Str::random(6);
        }
        if (strlen($base) > 180) {
            $base = substr($base, 0, 180);
        }

        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
            if ($i > 500) {
                $slug = $base . '-' . Str::random(6);
                break;
            }
        }
        return $slug;
    }

    /**
     * Resolve a product_category_id by matching the product name against
     * the category_aliases table. Aliases are tried longest-first so
     * "BPC-157" wins over a generic "BPC" alias if both exist.
     *
     * Returns null when nothing matches (the product still imports, it
     * just won't appear on compound/encyclopedia/compare pages).
     */
    protected function matchCategoryByName(?string $name): ?int
    {
        if (empty($name)) return null;

        // Cache aliases per-request to avoid hammering the DB during bulk syncs
        static $aliases = null;
        if ($aliases === null) {
            // Longest alias wins. MySQL CHAR_LENGTH counts characters.
            // SQLite has no CHAR_LENGTH; its LENGTH() also counts characters.
            // Do not use MySQL LENGTH() here — that counts bytes.
            $lengthExpr = DB::connection()->getDriverName() === 'sqlite'
                ? 'LENGTH(keyword)'
                : 'CHAR_LENGTH(keyword)';

            $aliases = DB::table('category_aliases')
                ->orderByRaw($lengthExpr.' DESC')
                ->get(['keyword', 'product_category_id']);
        }

        $haystack = mb_strtolower($name);
        foreach ($aliases as $alias) {
            $needle = mb_strtolower($alias->keyword);
            if ($needle === '') continue;
            if (str_contains($haystack, $needle)) {
                return (int) $alias->product_category_id;
            }
        }

        return null;
    }
}

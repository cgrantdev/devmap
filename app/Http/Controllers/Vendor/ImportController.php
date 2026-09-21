<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleXMLElement;
use App\Models\Product;

class ImportController extends Controller
{
    private function extractPrice($priceString)
    {
        // Remove currency symbols and extract numeric value
        $price = preg_replace('/[^0-9.]/', '', $priceString);
        return $price ?: '0.00';
    }

    /**
     * Generate a globally-unique product slug. products.slug is unique
     * SITE-WIDE (not per brand), so two vendors both selling
     * "AOD-9604 5mg" would collide (Rudy at Coastal Peptides Sep 22
     * hit this on his second product). Strategy:
     *   1. Try the raw slug from the name
     *   2. On collision, append the brand's slug — keeps URLs meaningful
     *   3. On further collision, append a numeric suffix
     */
    private function uniqueProductSlug(int $brandId, string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $base = substr($base, 0, 180);

        if (!Product::where('slug', $base)->exists()) {
            return $base;
        }

        $brand = \App\Models\Brand::find($brandId);
        $brandSlug = $brand?->slug ? substr(Str::slug($brand->slug), 0, 60) : 'v' . $brandId;
        $withBrand = substr($base, 0, 180 - strlen($brandSlug) - 1) . '-' . $brandSlug;

        if (!Product::where('slug', $withBrand)->exists()) {
            return $withBrand;
        }

        $i = 2;
        while (Product::where('slug', $withBrand . '-' . $i)->exists()) {
            if (++$i > 999) return $withBrand . '-' . uniqid();
        }
        return $withBrand . '-' . $i;
    }

    /**
     * Downloadable canonical XML template. Colin/Julia Sep 21 — Coastal
     * Peptides (and others before) hit "imported 0 products" because
     * their feed didn't match the expected shape. Serving a real
     * template file gives Julia something concrete to send.
     */
    public function template()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!--
  Peptidemap product-feed template.
  Root element MUST be <products>. Each product MUST include:
    name, price, url
  Optional but recommended:
    image, sku, size (mg or ml), stock (in_stock | out_of_stock),
    description
  Google Merchant (<rss>/<g:item>) and Shopify sitemap-style feeds
  are also accepted — the importer normalizes them internally.
-->
<products>
  <product>
    <name>BPC-157 5mg</name>
    <sku>BPC-5</sku>
    <size>5mg</size>
    <price>39.99</price>
    <image>https://your-site.com/images/bpc-157-5mg.jpg</image>
    <url>https://your-site.com/products/bpc-157-5mg</url>
    <stock>in_stock</stock>
    <description>Research-grade BPC-157 lyophilized peptide.</description>
  </product>
  <product>
    <name>TB-500 5mg</name>
    <sku>TB-5</sku>
    <size>5mg</size>
    <price>49.99</price>
    <image>https://your-site.com/images/tb-500-5mg.jpg</image>
    <url>https://your-site.com/products/tb-500-5mg</url>
    <stock>in_stock</stock>
  </product>
</products>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="peptidemap-feed-template.xml"',
        ]);
    }

    /**
     * Parses an XML feed into a normalized array of product rows,
     * regardless of the root shape. Supports:
     *   <products><product>...</product></products>       (native)
     *   <items><item>...</item></items>                    (generic)
     *   <rss><channel><item>...</g:*>...</item></channel>  (Google Merchant)
     *   <feed><entry>...</entry></feed>                    (Atom)
     * Returns [rows, diagnostic]. diagnostic describes what was seen
     * so a 0-product import gives Julia something to act on.
     */
    private function parseFeed(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = new SimpleXMLElement($xmlContent, LIBXML_NOCDATA);

        $rows = [];
        $rootName = strtolower($xml->getName());

        // Track which shape matched so we can log/diagnose.
        $shape = null;

        // Native <products><product>
        if (isset($xml->product) && $xml->product->count() > 0) {
            $shape = 'native';
            foreach ($xml->product as $p) {
                $rows[] = [
                    'name' => (string) ($p->name ?? $p->title ?? ''),
                    'price' => (string) ($p->price ?? ''),
                    'image_url' => (string) ($p->image ?? $p->image_url ?? ''),
                    'product_url' => (string) ($p->url ?? $p->link ?? ''),
                    'sku' => (string) ($p->sku ?? ''),
                    'size' => (string) ($p->size ?? $p->dosage ?? ''),
                    'stock_status' => (string) ($p->stock ?? $p->stock_status ?? ''),
                    'description' => (string) ($p->description ?? ''),
                ];
            }
        } elseif (isset($xml->item) && $xml->item->count() > 0) {
            $shape = 'items';
            foreach ($xml->item as $p) {
                $rows[] = [
                    'name' => (string) ($p->name ?? $p->title ?? ''),
                    'price' => (string) ($p->price ?? ''),
                    'image_url' => (string) ($p->image ?? $p->image_url ?? ''),
                    'product_url' => (string) ($p->url ?? $p->link ?? ''),
                    'sku' => (string) ($p->sku ?? ''),
                    'size' => (string) ($p->size ?? ''),
                    'stock_status' => (string) ($p->stock ?? ''),
                    'description' => (string) ($p->description ?? ''),
                ];
            }
        } elseif (isset($xml->channel->item) && $xml->channel->item->count() > 0) {
            // Google Merchant / RSS 2.0 with <g:*> namespaced fields.
            $shape = 'google-merchant';
            foreach ($xml->channel->item as $p) {
                $g = $p->children('g', true);
                $rows[] = [
                    'name' => (string) ($g->title ?? $p->title ?? ''),
                    'price' => (string) ($g->price ?? ''),
                    'image_url' => (string) ($g->image_link ?? ''),
                    'product_url' => (string) ($g->link ?? $p->link ?? ''),
                    'sku' => (string) ($g->id ?? ''),
                    'size' => (string) ($g->product_detail ?? ''),
                    'stock_status' => (string) ($g->availability ?? ''),
                    'description' => (string) ($g->description ?? $p->description ?? ''),
                ];
            }
        } elseif (isset($xml->entry) && $xml->entry->count() > 0) {
            $shape = 'atom';
            foreach ($xml->entry as $p) {
                $rows[] = [
                    'name' => (string) ($p->title ?? ''),
                    'price' => (string) ($p->price ?? ''),
                    'image_url' => '',
                    'product_url' => (string) ($p->link['href'] ?? $p->link ?? ''),
                    'sku' => (string) ($p->id ?? ''),
                    'size' => '',
                    'stock_status' => '',
                    'description' => (string) ($p->summary ?? $p->content ?? ''),
                ];
            }
        }

        // Filter out rows missing the two required fields — name + price.
        $rows = array_values(array_filter($rows, fn ($r) => $r['name'] !== '' && $r['price'] !== ''));

        $diagnostic = $shape
            ? "Detected shape: {$shape} (root <{$rootName}>)"
            : "Could not detect a product feed shape. Root element was <{$rootName}> — expected <products> with <product> children. Download the template at /vendor/import/template.xml.";

        return [$rows, $diagnostic];
    }

    public function index()
    {
        // Products belong to brands, not directly to users. Both
        // User::products() and User::vendorSetting() are broken hasMany/hasOne
        // relations (they assume products.user_id / vendor_settings.user_id
        // which don't exist). Correct chain: brands.user_id → products.brand_id
        // (matches how DashboardController::index resolves the vendor's brand).
        $user = Auth::user();
        $brand = \App\Models\Brand::where('user_id', $user->id)->first();
        $products = $brand
            ? Product::where('brand_id', $brand->id)->latest()->get()
            : collect();

        return Inertia::render('Vendor/Import', [
            'products' => $products
        ]);
    }

    public function importFromFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xml|max:10240', // 10MB max
        ]);

        $user = Auth::user();
        $brand = \App\Models\Brand::where('user_id', $user->id)->first();
        if (!$brand) {
            return redirect()->back()->with('error', 'No brand associated with your account yet — contact support.');
        }
        $file = $request->file('file');

        try {
            $xmlContent = file_get_contents($file->getPathname());
            [$rows, $diagnostic] = $this->parseFeed($xmlContent);
            return $this->ingestRows($brand->id, $rows, $diagnostic);
        } catch (\Exception $e) {
            Log::warning('vendor xml file import failed', ['brand_id' => $brand->id, 'err' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error parsing XML file: ' . $e->getMessage());
        }
    }

    /**
     * Shared writer used by both file + URL importers. Returns the
     * redirect with an accurate success/warn message so vendors know
     * exactly what happened when 0 products come through.
     */
    private function ingestRows(int $brandId, array $rows, string $diagnostic)
    {
        if (empty($rows)) {
            Log::info('vendor xml import: zero rows', ['brand_id' => $brandId, 'diagnostic' => $diagnostic]);
            return redirect()->back()->with('error', 'Imported 0 products. ' . $diagnostic);
        }

        // Colin/Rudy Sep 22 — importer now upserts on (brand_id,
        // external_id) so re-syncs update existing rows instead of
        // slamming into the (brand_id, external_id) composite unique.
        // Falls back to (brand_id, product_url) for feeds that don't
        // carry an id, then finally inserts as new.
        $importedCount = 0;
        $updatedCount = 0;
        foreach ($rows as $r) {
            $productUrl = $r['product_url'] ?? '';
            $extId = $r['sku'] ?? '';

            $existing = null;
            if ($extId !== '') {
                $existing = Product::where('brand_id', $brandId)
                    ->where('external_id', $extId)
                    ->first();
            }
            if (!$existing && $productUrl) {
                $existing = Product::where('brand_id', $brandId)
                    ->where('product_url', $productUrl)
                    ->first();
            }

            $attrs = [
                'name' => $r['name'],
                'price' => $this->extractPrice($r['price']),
                'image_url' => $r['image_url'] ?? null,
                'product_url' => $productUrl ?: null,
                'size_mg' => $r['size'] ?? null,
                'stock_status' => $r['stock_status'] ?? null,
                'description' => $r['description'] ?? null,
                'external_id' => $extId ?: null,
            ];

            if ($existing) {
                // Preserve slug — it's already unique and cached in
                // Google's index; changing it on every sync would
                // churn URLs and break inbound links.
                $existing->fill($attrs)->save();
                $updatedCount++;
            } else {
                $attrs['brand_id'] = $brandId;
                // Site-wide unique slug: name + numeric suffix, then
                // append brand slug for collisions with other vendors'
                // products (Rudy Sep 22: "aod-9604-5mg" duplicate).
                $attrs['slug'] = $this->uniqueProductSlug($brandId, $r['name']);
                Product::create($attrs);
                $importedCount++;
            }
        }
        $skippedCount = 0;

        $message = "Imported {$importedCount} new, updated {$updatedCount} existing. {$diagnostic}";
        return redirect()->back()->with('success', $message);
    }

    public function importFromUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $user = Auth::user();
        $brand = \App\Models\Brand::where('user_id', $user->id)->first();
        if (!$brand) {
            return redirect()->back()->with('error', 'No brand associated with your account yet — contact support.');
        }

        try {
            $response = Http::timeout(30)->get($request->url);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Failed to fetch XML from URL — server returned ' . $response->status() . '.');
            }

            [$rows, $diagnostic] = $this->parseFeed($response->body());
            return $this->ingestRows($brand->id, $rows, $diagnostic);
        } catch (\Exception $e) {
            Log::warning('vendor xml url import failed', ['brand_id' => $brand->id, 'err' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error importing from URL: ' . $e->getMessage());
        }
    }

    public function deleteProduct(Product $product)
    {
        $user = Auth::user();
        
        // Check if the product belongs to the user's brand
        // Get user's brand (vendors are associated with brands)
        $brand = $user->brands()->first();
        if (!$brand || $product->brand_id !== $brand->id) {
            return redirect()->back()->with('error', 'Product not found.');
        }
        
        // Delete the product
        $product->delete();
        
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
} 
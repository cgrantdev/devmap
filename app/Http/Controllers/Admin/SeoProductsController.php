<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Narrow slice of /admin/products that only exposes product-page SEO
 * fields (title, meta description, OG) + description. Lets the SEO
 * agency fix product SEO without seeing price, stock, affiliate URLs,
 * scraper config, admin notes, or any ingestion plumbing.
 *
 * Writes are scoped to seo_* columns + description. Everything else on
 * the row is untouched on save.
 */
class SeoProductsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $brandId = (int) $request->get('brand_id', 0);
        $missing = $request->get('missing') === '1';

        $products = Product::query()
            ->with('brand:id,name,slug')
            ->when($q, fn ($b) => $b->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")))
            ->when($brandId > 0, fn ($b) => $b->where('brand_id', $brandId))
            ->when($missing, fn ($b) => $b->where(fn ($w) => $w
                ->whereNull('seo_page_title')->orWhere('seo_page_title', '')
                ->orWhereNull('seo_description')->orWhere('seo_description', '')))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand ? ['id' => $p->brand->id, 'name' => $p->brand->name, 'slug' => $p->brand->slug] : null,
                'seo_page_title' => $p->seo_page_title,
                'seo_description' => $p->seo_description,
                'has_title' => !empty($p->seo_page_title),
                'has_meta' => !empty($p->seo_description),
            ]);

        return Inertia::render('Admin/SeoProducts', [
            'products' => $products,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['q' => $q, 'brand_id' => $brandId, 'missing' => $missing],
        ]);
    }

    public function edit(Product $product)
    {
        $product->loadMissing('brand:id,name,slug');
        return Inertia::render('Admin/SeoProductEdit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
                'description' => $product->description,
                'seo_page_title' => $product->seo_page_title ?? '',
                'seo_description' => $product->seo_description ?? '',
                'seo_og_title' => $product->seo_og_title ?? '',
                'seo_og_description' => $product->seo_og_description ?? '',
                'public_url' => $product->brand ? "/vendor/{$product->brand->slug}/{$product->slug}" : null,
            ],
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:10000',
            'seo_page_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:300',
            'seo_og_title' => 'nullable|string|max:120',
            'seo_og_description' => 'nullable|string|max:300',
        ]);

        // Field-level lock — only touch SEO columns + description. Price,
        // stock, affiliate URL, scraper config, admin lock flags are all
        // untouched on save.
        $product->description = $data['description'] ?? null;
        $product->seo_page_title = $data['seo_page_title'] ?? null;
        $product->seo_description = $data['seo_description'] ?? null;
        $product->seo_og_title = $data['seo_og_title'] ?? null;
        $product->seo_og_description = $data['seo_og_description'] ?? null;
        $product->save();

        return back()->with('success', 'Product SEO updated.');
    }
}

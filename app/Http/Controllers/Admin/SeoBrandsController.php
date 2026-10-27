<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Narrow slice of /admin/vendors that only exposes brand-page SEO
 * fields (title, meta description, OG) + the brand's long-form
 * description. Lets the SEO agency fix brand-page SEO without
 * touching financial, scraping, affiliate, or vendor-approval data.
 *
 * Covers Advantage Collective audit Part 3: "22 of 61 brand titles
 * get cut off in Google" and "3 brand descriptions say live prices
 * for 0 peptides".
 */
class SeoBrandsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $brands = Brand::query()
            ->with('vendorSetting:brand_id,seo_page_title,seo_description,seo_og_title,seo_og_description')
            ->when($q, fn ($b) => $b
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")))
            ->orderBy('name')
            ->paginate(50)->withQueryString();

        return Inertia::render('Admin/SeoBrands', [
            'brands' => $brands,
            'q' => $q,
        ]);
    }

    public function edit(Brand $brand)
    {
        $brand->loadMissing('vendorSetting');
        return Inertia::render('Admin/SeoBrandEdit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'description' => $brand->description,
                'seo_page_title' => $brand->vendorSetting?->seo_page_title ?? '',
                'seo_description' => $brand->vendorSetting?->seo_description ?? '',
                'seo_og_title' => $brand->vendorSetting?->seo_og_title ?? '',
                'seo_og_description' => $brand->vendorSetting?->seo_og_description ?? '',
                'storefront_url' => "/vendor/{$brand->slug}",
            ],
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:5000',
            'seo_page_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:300',
            'seo_og_title' => 'nullable|string|max:120',
            'seo_og_description' => 'nullable|string|max:300',
        ]);

        $brand->description = $data['description'] ?? null;
        $brand->save();

        if ($brand->vendorSetting) {
            $brand->vendorSetting->fill([
                'seo_page_title' => $data['seo_page_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'seo_og_title' => $data['seo_og_title'] ?? null,
                'seo_og_description' => $data['seo_og_description'] ?? null,
            ])->save();
        }

        return back()->with('success', 'Brand SEO updated.');
    }
}

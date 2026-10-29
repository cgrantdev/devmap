<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Narrow slice of /admin/vendors that only exposes brand-page SEO
 * fields + the handful of on-page content fields the SEO agency needs
 * to set a brand up right (logo, body description, Why Choose bullets,
 * title/meta/OG).
 *
 * Everything else on the vendor record — approval status, ships-to,
 * affiliate URL, coupon code, pricing, feed/scraper config, API keys,
 * contact details, promo state, admin notes — is NOT reachable from
 * here. Field-level lock on save: we only touch the whitelisted
 * columns, nothing else on the row changes.
 */
class SeoBrandsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $brands = Brand::query()
            ->where('is_active', true) // agency only sees live vendors
            ->with('vendorSetting:brand_id,seo_page_title,seo_description,seo_og_title,seo_og_description,logo,description')
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
        $vs = $brand->vendorSetting;
        return Inertia::render('Admin/SeoBrandEdit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $vs?->logo ? '/storage/' . ltrim($vs->logo, '/') : null,
                'description' => $vs?->description ?? '',
                'why_choose_bullets' => $vs?->why_choose_bullets ?? [],
                'seo_page_title' => $vs?->seo_page_title ?? '',
                'seo_description' => $vs?->seo_description ?? '',
                'seo_og_title' => $vs?->seo_og_title ?? '',
                'seo_og_description' => $vs?->seo_og_description ?? '',
                'storefront_url' => "/vendor/{$brand->slug}",
            ],
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:5000',
            'why_choose_bullets' => 'nullable|array|max:8',
            'why_choose_bullets.*' => 'nullable|string|max:200',
            'seo_page_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:300',
            'seo_og_title' => 'nullable|string|max:120',
            'seo_og_description' => 'nullable|string|max:300',
            'logo' => 'nullable|image|mimes:jpeg,png,webp,svg|max:2048',
        ]);

        $vs = $brand->vendorSetting;
        if (!$vs) {
            return back()->with('error', 'This vendor has no settings record yet.');
        }

        // Field-level lock — only touch the whitelisted columns. Approval
        // status, ships-to, affiliate URL, coupon, feed, scraper, pricing,
        // promos, contact info are untouched on save.
        $vs->description = $data['description'] ?? null;
        $vs->why_choose_bullets = array_values(array_filter($data['why_choose_bullets'] ?? [], fn ($b) => trim((string) $b) !== ''));
        $vs->seo_page_title = $data['seo_page_title'] ?? null;
        $vs->seo_description = $data['seo_description'] ?? null;
        $vs->seo_og_title = $data['seo_og_title'] ?? null;
        $vs->seo_og_description = $data['seo_og_description'] ?? null;

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $ext = strtolower($logoFile->getClientOriginalExtension());
            $mime = $logoFile->getMimeType();
            if ($ext === 'svg' || $mime === 'image/svg+xml') {
                $filename = Str::random(40) . '.svg';
                $logoFile->storeAs('vendor_logos', $filename, 'public');
                $vs->logo = 'vendor_logos/' . $filename;
            } else {
                $filename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                $vs->logo = 'vendor_logos/' . $filename;
            }
        }

        $vs->save();

        return back()->with('success', 'Brand SEO updated.');
    }
}

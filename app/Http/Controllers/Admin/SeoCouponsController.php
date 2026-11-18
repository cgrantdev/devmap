<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\PageContent;
use App\Models\Product;
use App\Support\AgencyFields;
use App\Support\CouponPageSeo;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * SEO-agency editor for /coupon/{slug} pages (Jake, Nov 17 2026: "there's
 * no editor for these on our login at all"). Title, meta description, H1,
 * intro, body and FAQs per vendor, stored in page_contents. Blank fields
 * keep the page's templated defaults.
 *
 * Only vendors with a live coupon page are listed: active, with a coupon
 * code. The coupon code itself is not editable here.
 */
class SeoCouponsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $withCopy = array_flip(PageContent::keysWithCopy(PageContent::COUPON));

        $rows = Brand::query()
            ->where('is_active', true)
            ->whereHas('vendorSetting', fn ($vs) => $vs->whereNotNull('coupon_code')->where('coupon_code', '!=', ''))
            ->with('vendorSetting:id,brand_id,coupon_code,coupon_discount_percent')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'url' => "/coupon/{$b->slug}",
                'code' => strtoupper((string) $b->vendorSetting?->coupon_code),
                'percent_off' => CouponPageSeo::percentOff($b->vendorSetting?->coupon_discount_percent),
                'has_copy' => isset($withCopy[(string) $b->id]),
            ])
            ->values();

        return Inertia::render('Admin/SeoCoupons', [
            'rows' => $rows,
            'q' => $q,
            'ready' => PageContent::ready(),
        ]);
    }

    public function edit(Brand $brand)
    {
        $vs = $brand->vendorSetting;
        $code = strtoupper((string) $vs?->coupon_code);
        $percentOff = CouponPageSeo::percentOff($vs?->coupon_discount_percent);
        $productCount = Product::visible()->where('status', 'active')->where('brand_id', $brand->id)->count();
        $copy = PageContent::forPage(PageContent::COUPON, $brand->id);

        return Inertia::render('Admin/SeoCouponEdit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => "/coupon/{$brand->slug}",
                'code' => $code,
                // Without a code (or an inactive vendor) the page 404s;
                // copy saved now shows once it has one again.
                'live' => $brand->is_active && $code !== '',
            ],
            'defaults' => [
                'title' => CouponPageSeo::defaultTitle($brand->name, $code, $percentOff),
                'meta_description' => CouponPageSeo::defaultDescription($brand->name, $code, $percentOff, $productCount),
                'h1' => CouponPageSeo::defaultH1($brand->name),
            ],
            'content' => [
                'title' => $copy?->title ?? '',
                'meta_description' => $copy?->meta_description ?? '',
                'h1' => $copy?->h1 ?? '',
                'intro_html' => $copy?->intro_html ?? '',
                'body_html' => $copy?->body_html ?? '',
                'faqs' => $copy ? $copy->faqPairs() : [],
            ],
            'ready' => PageContent::ready(),
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        if (! PageContent::ready()) {
            return back()->with('error', 'Coupon page SEO turns on after the pending database update.');
        }

        $data = $request->validate(AgencyFields::rules(withSnippet: true), AgencyFields::messages());
        AgencyFields::save(PageContent::COUPON, (string) $brand->id, $data, withSnippet: true);

        return back()->with('success', 'Coupon page saved.');
    }
}

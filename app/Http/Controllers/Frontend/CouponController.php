<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Inertia\Inertia;

/**
 * Coupon-code landing pages — /coupon/{brand-slug}.
 *
 * Colin Sep 16 — GSC showed "glow aminos coupon code" at pos 9.3
 * with 74 imp/mo, "glow aminos discount code" at pos 9.9, and
 * similar patterns for every partner brand. Google was giving us
 * borderline page-1 for coupon-code intent but our /brand/{slug}
 * storefront isn't optimized for that query — the H1 doesn't say
 * "coupon" anywhere. A dedicated /coupon/{slug} page with
 * "{Brand} Coupon Code" as the H1 + code prominent + click-through
 * to the affiliate URL closes the intent gap. Same brand data,
 * one narrow purpose.
 */
class CouponController extends Controller
{
    public function show(string $slug)
    {
        $brand = Brand::where('slug', $slug)
            ->where('is_active', true)
            ->with(['vendorSetting'])
            ->firstOrFail();

        $vs = $brand->vendorSetting;
        // No coupon? 404 out — a coupon landing page with no code is
        // worse than not existing; Google would rank it for the query
        // then bounce visitors when they realize there's no discount.
        abort_unless($vs && !empty($vs->coupon_code), 404);

        $productCount = Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->count();

        $lowest = Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere(function ($qq) { $qq->whereNull('discount_price')->where('price', '>', 0); });
            })
            ->selectRaw('LEAST(COALESCE(discount_price, price), price) as effective')
            ->orderBy('effective')
            ->limit(1)
            ->value('effective');

        $couponCode = strtoupper($vs->coupon_code);
        $percentOff = $vs->coupon_discount_percent
            ? (int) round((float) $vs->coupon_discount_percent)
            : null;

        $affiliateUrl = $vs->referral_url ?: ($vs->shop_url ?: $vs->website);

        // SEO — coupon-intent queries want the code + brand in the title
        // and description, per the GSC "glow aminos coupon code" cluster.
        $seoTitle = $percentOff
            ? "{$brand->name} Coupon Code {$couponCode} — {$percentOff}% Off"
            : "{$brand->name} Coupon Code {$couponCode}";
        $seoDescription = $percentOff
            ? "Save {$percentOff}% at {$brand->name} with coupon code {$couponCode}. "
              . "Verified discount across {$productCount} products — click to reveal + copy."
            : "Get the {$brand->name} coupon code {$couponCode} — verified and up-to-date. "
              . "Applies across {$productCount} products.";

        // Schema.org DiscountOffer + Organization — signals to Google
        // this page is a legitimate coupon listing, not affiliate spam.
        $schema = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Offer',
                'name' => "{$brand->name} coupon: {$couponCode}",
                'description' => $seoDescription,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => url("/coupon/{$slug}"),
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $brand->name,
                    'url' => $affiliateUrl,
                ],
                'category' => 'CouponCode',
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Coupons', 'item' => url('/coupon')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $brand->name, 'item' => url("/coupon/{$slug}")],
                ],
            ],
        ];

        $seo = [
            'key' => 'coupon',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'url' => url("/coupon/{$slug}"),
            'canonical' => url("/coupon/{$slug}"),
            'schema' => $schema,
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Coupon', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $vs->logo ? asset('storage/' . $vs->logo) : null,
                'website' => $vs->website,
                'shop_url' => $vs->shop_url,
            ],
            'coupon' => [
                'code' => $couponCode,
                'percent_off' => $percentOff,
                'affiliate_url' => $affiliateUrl,
                'lowest_price' => $lowest ? (float) $lowest : null,
                'product_count' => $productCount,
            ],
            'seo' => $seo,
        ]);
    }
}

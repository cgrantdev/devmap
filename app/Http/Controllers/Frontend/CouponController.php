<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\PageContent;
use App\Models\Product;
use App\Support\CouponPageSeo;
use App\Support\FaqPage;
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
            ->with(['vendorSetting' => fn ($q) => $q->forPublic()->with('location')])
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
            // Same figure as LEAST(COALESCE(discount_price, price), price).
            // CASE is used so the page runs on SQLite as well as MySQL.
            ->selectRaw('CASE WHEN discount_price IS NOT NULL AND discount_price > 0 AND discount_price < price THEN discount_price ELSE price END as effective')
            ->orderBy('effective')
            ->limit(1)
            ->value('effective');

        $couponCode = strtoupper($vs->coupon_code);
        $percentOff = CouponPageSeo::percentOff($vs->coupon_discount_percent);

        $affiliateUrl = $vs->referral_url ?: ($vs->shop_url ?: $vs->website);

        // SEO — coupon-intent queries want the code + brand in the title
        // and description, per the GSC "glow aminos coupon code" cluster.
        $seoTitle = CouponPageSeo::defaultTitle($brand->name, $couponCode, $percentOff);
        $seoDescription = CouponPageSeo::defaultDescription($brand->name, $couponCode, $percentOff, $productCount);

        // Agency copy (/admin/seo/coupons, Nov 2026): title, meta, H1,
        // intro, body and FAQs. Each part replaces its default.
        $copy = PageContent::forPage(PageContent::COUPON, $brand->id);
        $copyFaqs = $copy ? $copy->faqPairs() : [];
        if ($copy?->title) {
            $seoTitle = $copy->title;
        }
        if ($copy?->meta_description) {
            $seoDescription = $copy->meta_description;
        }

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
        // FAQPage only from the agency FAQs the page shows. The default
        // Q&A blocks never had one.
        if ($faqSchema = FaqPage::schema($copyFaqs, url("/coupon/{$slug}").'#faq')) {
            $schema[] = $faqSchema;
        }

        $seo = [
            'key' => 'coupon',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'url' => url("/coupon/{$slug}"),
            'canonical' => url("/coupon/{$slug}"),
            'og_image' => \App\Support\OgImageUrl::coupon($slug),
            'image' => \App\Support\OgImageUrl::coupon($slug),
            'schema' => $schema,
        ];
        if ($copy?->h1) {
            $seo['h1'] = $copy->h1;
        }
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Coupon', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $vs->logo ? asset('storage/' . $vs->logo) : null,
                'website' => $vs->website,
                'shop_url' => $vs->shop_url,
                // Colin 10/21 SEO push — surface the brand's unique
                // description on the coupon page body. Previously every
                // coupon page was templated identically down to the FAQ,
                // so Google saw ~30 near-duplicate URLs. Prefer vendor-
                // authored description, fall back to AI-generated.
                'description' => $vs->description ?: $vs->generated_description,
                'location' => $vs->location?->name,
            ],
            'coupon' => [
                'code' => $couponCode,
                'percent_off' => $percentOff,
                'affiliate_url' => $affiliateUrl,
                'lowest_price' => $lowest ? (float) $lowest : null,
                'product_count' => $productCount,
            ],
            'copy' => $copy ? [
                'h1' => $copy->h1,
                'intro_html' => $copy->intro_html,
                'body_html' => $copy->body_html,
                'faqs' => $copyFaqs,
            ] : null,
            'seo' => $seo,
        ]);
    }
}

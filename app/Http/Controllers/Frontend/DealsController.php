<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class DealsController extends Controller
{
    /**
     * Show the deals page
     */
    public function index(Request $request)
    {
        $sortBy = (string) $request->get('sort', 'best_discount');
        if (!in_array($sortBy, ['best_discount', 'top_rated', 'a_z'], true)) {
            $sortBy = 'best_discount';
        }

        $locationFilter = trim((string) $request->get('location', ''));
        $filterBrandByLocation = fn ($q) => $locationFilter
            ? $q->whereHas('vendorSetting.location', fn ($l) => $l->where('name', $locationFilter))
            : $q;

        // Admin-curated deals first; they win over the same vendor's
        // standing coupon code below.
        $dealRows = Deal::where('active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('used_count < usage_limit');
            })
            ->when($locationFilter, fn ($q) => $q->whereHas(
                'brand.vendorSetting.location',
                fn ($l) => $l->where('name', $locationFilter)
            ))
            ->with([
                'brand' => fn ($q) => $q->forPublic(),
                'brand.vendorSetting' => fn ($q) => $q->forPublic(),
            ])
            ->get()
            ->map(function ($deal) {
                $brand = $deal->brand;
                if (!$brand || !$brand->is_active || trim((string) $deal->code) === '') {
                    return null;
                }

                return $this->dealRow($brand, (string) $deal->code, $deal->discount !== null ? (float) $deal->discount : null);
            })
            ->filter()
            ->unique('id')
            ->keyBy('id');

        // Every other active, approved vendor with a live coupon code. This
        // page used to stop at eight, and invented a 15% (or 10%) discount
        // when a vendor had not set one; unknown discounts now show none.
        $vendorRows = Brand::where('is_active', true)
            ->whereHas('vendorSetting', function ($q) {
                $q->where('approval_status', 'approved')
                    ->whereNotNull('coupon_code')
                    ->where('coupon_code', '!=', '');
            })
            ->tap($filterBrandByLocation)
            ->whereNotIn('id', $dealRows->keys()->all())
            ->with(['vendorSetting' => fn ($q) => $q->forPublic(true)])
            ->get()
            ->map(function ($brand) {
                $vs = $brand->vendorSetting;
                $boosted = $vs && $vs->couponBoostActive();

                return $this->dealRow(
                    $brand,
                    (string) $vs->coupon_code,
                    $vs->coupon_discount_percent !== null ? (float) $vs->coupon_discount_percent : null,
                    $boosted,
                    $boosted ? $vs->coupon_boost_expires_at?->toIso8601String() : null
                );
            });

        // Boosted (limited-time) codes first, then the chosen order. Codes
        // without a known discount go after those with one.
        $deals = $dealRows->values()->toBase()->merge($vendorRows->toBase())
            ->sortBy(function ($deal) use ($sortBy) {
                $boostRank = !empty($deal['is_boosted']) ? 0 : 1;
                switch ($sortBy) {
                    case 'top_rated':
                        return [$boostRank, -(float) $deal['rating'], strtolower($deal['name'])];
                    case 'a_z':
                        return [$boostRank, strtolower($deal['name'])];
                    case 'best_discount':
                    default:
                        return [$boostRank, $deal['discount'] === null ? 1 : 0, -($deal['discount'] ?? 0), strtolower($deal['name'])];
                }
            }, SORT_REGULAR)
            ->values();

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Peptide Deals & Discount Codes | Peptidemap',
            description: 'Discount codes and sale prices supplied by vendors. Confirm the price on the vendor site. Catalog data only: RUO, and Peptidemap sells nothing.',
            url: url('/deals'),
            image: \App\Support\OgImageUrl::page('deals'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Deals', [
            'deals' => $deals,
            'sortBy' => $sortBy,
            'dealsCount' => $deals->count(),
        ]);
    }

    /**
     * One card on /deals: the vendor, its code and the discount the code
     * gives (null when the vendor has not said).
     *
     * @return array<string, mixed>
     */
    private function dealRow(Brand $brand, string $code, ?float $discount, bool $boosted = false, ?string $boostEndsAt = null): array
    {
        $vs = $brand->vendorSetting;
        $description = trim(strip_tags((string) ($vs?->description ?? '')));

        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug ?? Str::slug($brand->name),
            'initials' => $this->getInitials($brand->name),
            'logo' => $vs?->publicLogoUrl(),
            'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
            'reviews' => (int) ($brand->rating_count ?? 0),
            'discount' => $discount !== null && $discount > 0 && $discount < 100 ? (int) round($discount) : null,
            'code' => strtoupper(trim($code)),
            'is_boosted' => $boosted,
            'boost_ends_at' => $boostEndsAt,
            'description' => $description !== '' ? Str::limit($description, 160) : null,
        ];
    }

    /**
     * Get initials from brand name
     */
    private function getInitials($name)
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

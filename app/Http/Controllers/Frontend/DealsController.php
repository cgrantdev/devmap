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
        $sortBy = $request->get('sort', 'best_discount'); // best_discount, top_rated, a_z

        // Get active deals with vendor info
        $dealsQuery = Deal::where('active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('used_count < usage_limit');
            })
            ->with(['brand.vendorSetting']);

        $deals = $dealsQuery->get()
            ->map(function ($deal) {
                $brand = $deal->brand;
                if (!$brand || !$brand->is_active) {
                    return null;
                }

                $logoUrl = null;
                if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                    $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug ?? Str::slug($brand->name),
                    'initials' => $this->getInitials($brand->name),
                    'logo' => $logoUrl,
                    'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                    'discount' => $deal->discount,
                    'code' => $deal->code,
                    'description' => $brand->vendorSetting->description ?? 'Premium peptides and nootropics with exceptional quality control and customer service.',
                ];
            })
            ->filter()
            ->values()
            ->toBase();

        // If we don't have enough deals, supplement with top brands that have coupon codes
        if ($deals->count() < 8) {
            $brandsWithCoupons = Brand::where('is_active', true)
                ->where(function ($q) {
                    $q->whereHas('vendorSetting', function ($subQ) {
                        $subQ->where('approval_status', 'approved')
                            ->whereNotNull('coupon_code')
                            ->where('coupon_code', '!=', '');
                    })
                    ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility
                })
                ->with(['vendorSetting'])
                ->whereNotIn('id', $deals->pluck('id'))
                ->orderByDesc('rating_average')
                ->take(8 - $deals->count())
                ->get()
                ->map(function ($brand) {
                    $logoUrl = null;
                    if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                        $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                    }

                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug ?? Str::slug($brand->name),
                        'initials' => $this->getInitials($brand->name),
                        'logo' => $logoUrl,
                        'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                        'reviews' => (int) ($brand->rating_count ?? 0),
                        'discount' => 15, // Default discount for coupon codes
                        'code' => $brand->vendorSetting->coupon_code ?? 'PMAP',
                        'description' => $brand->vendorSetting->description ?? 'Premium peptides and nootropics with exceptional quality control and customer service.',
                    ];
                })
                ->values()
                ->toBase();

            $deals = $deals->merge($brandsWithCoupons)->take(8)->values()->toBase();
        }

        // If still no deals, use top brands as fallback with default discount - only approved vendors
        if ($deals->count() === 0) {
            $deals = Brand::where('is_active', true)
                ->where(function ($q) {
                    $q->whereHas('vendorSetting', function ($subQ) {
                        $subQ->where('approval_status', 'approved');
                    })
                    ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility
                })
                ->with(['vendorSetting'])
                ->orderByDesc('rating_average')
                ->take(8)
                ->get()
                ->map(function ($brand) {
                    $logoUrl = null;
                    if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                        $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                    }

                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug ?? Str::slug($brand->name),
                        'initials' => $this->getInitials($brand->name),
                        'logo' => $logoUrl,
                        'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                        'reviews' => (int) ($brand->rating_count ?? 0),
                        'discount' => 10, // Default discount
                        'code' => 'PMAP',
                        'description' => $brand->vendorSetting->description ?? 'Premium peptides and nootropics with exceptional quality control and customer service.',
                    ];
                })
                ->values()
                ->toBase();
        }

        // Apply sorting
        $deals = $deals->sortBy(function ($deal) use ($sortBy) {
            switch ($sortBy) {
                case 'top_rated':
                    return -$deal['rating']; // Negative for descending
                case 'a_z':
                    return $deal['name'];
                case 'best_discount':
                default:
                    return -$deal['discount']; // Negative for descending
            }
        })->values();

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Peptide Deals & Discount Codes | PeptideSync',
            description: 'Find the best deals and discount codes for research peptides. Save money on top-rated peptide vendors with verified coupon codes.',
            url: url('/deals'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Deals', [
            'deals' => $deals,
            'sortBy' => $sortBy,
            'dealsCount' => $deals->count(),
        ]);
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

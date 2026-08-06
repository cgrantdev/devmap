<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Dedicated /bacteriostatic-water landing page.
 *
 * BAC water is a huge search-volume query for research-peptide buyers
 * (it's what every peptide is reconstituted with). The generic
 * /compare/{slug} template didn't earn "cheapest bac water" ranking
 * because URL, H1, and content didn't say the exact phrase. This
 * controller powers a purpose-built landing page: exact-match URL,
 * per-mL comparison, size filter, educational content, FAQPage schema.
 */
class BacteriostaticWaterController extends Controller
{
    // Category slug stored in DB. Case-insensitive match in MySQL.
    private const CATEGORY_SLUG = 'bacteriostatic-water';

    public function show(Request $request)
    {
        $category = ProductCategory::where('slug', self::CATEGORY_SLUG)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            abort(404, 'Bacteriostatic Water category not configured.');
        }

        $rows = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->where('price', '>', 0)
            ->with('brand.vendorSetting')
            ->get()
            ->map(function ($p) {
                // size_mg is empty for most BAC water products — extract mL
                // from the product name (patterns like "10ml", "10 mL", "30ML").
                $volumeMl = $this->extractMl($p->size_mg) ?? $this->extractMl($p->name);

                $retail = (float) ($p->discount_price && $p->discount_price < $p->price
                    ? $p->discount_price : $p->price);
                $pct = $p->brand?->vendorSetting?->coupon_discount_percent;
                $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                    ? round($retail * (1 - ((float) $pct / 100)), 2) : null;
                $finalPrice = $pmapPrice ?? $retail;
                // Per-mL comparison is the metric that matters for BAC water —
                // a $16 30mL beats a $10 10mL on a per-mL basis.
                $perMlPrice = $volumeMl ? round($finalPrice / $volumeMl, 3) : null;

                // Normalized display label. On this dedicated page every row
                // IS bacteriostatic water — using each vendor's raw name
                // leaks their internal wording ("BAC 10ml", "Sterile Water",
                // "Reconstitution Solution") and makes the table look
                // inconsistent. Format uniformly around the volume we
                // extracted; keep original name as a tooltip fallback so
                // vendor-specific info (Hospira, "sterile", brand variants)
                // isn't fully lost.
                $displayName = $volumeMl
                    ? "Bacteriostatic Water · {$volumeMl} mL"
                    : ($p->display_name ?: $p->name);

                return [
                    'id' => $p->id,
                    'name' => $displayName,
                    'raw_name' => $p->name,
                    'slug' => $p->slug,
                    'brand_name' => $p->brand?->name,
                    'brand_slug' => $p->brand?->slug,
                    'brand_logo' => $p->brand?->vendorSetting?->logo
                        ? asset('storage/' . $p->brand->vendorSetting->logo) : null,
                    'brand_coupon_code' => $p->brand?->vendorSetting?->coupon_code,
                    'brand_discount_percent' => $pct !== null ? (float) $pct : null,
                    'retail_price' => $retail,
                    'final_price' => $finalPrice,
                    'pmap_price' => $pmapPrice,
                    'volume_ml' => $volumeMl,
                    'per_ml_price' => $perMlPrice,
                    'go_url' => "/go/{$p->id}",
                    'product_url' => "/product/" . ($p->brand?->slug ?? 'brand')
                        . '/' . ($p->slug ?? $p->id) . '/' . $p->id,
                ];
            })
            ->sortBy(fn ($r) => $r['final_price'])
            ->values();

        $withVolume = $rows->filter(fn ($r) => $r['volume_ml']);
        $vendorCount = $rows->pluck('brand_name')->unique()->count();
        $cheapest = $rows->first()['final_price'] ?? null;
        $priciest = $rows->last()['final_price'] ?? null;
        $bestPerMl = $withVolume->sortBy('per_ml_price')->first();

        $availableSizes = $withVolume->pluck('volume_ml')->unique()->sort()->values()->all();

        // SEO — exact phrase in title + description.
        $vCount = $rows->count();
        $seoTitle = 'Cheapest Bacteriostatic Water — Compare '
            . $vendorCount . ' Vendors';
        $seoDescription = 'Compare bacteriostatic water prices across '
            . $vendorCount . ' verified vendors on Peptidemap. '
            . ($cheapest ? 'From $' . number_format($cheapest, 2) . '. ' : '')
            . '3mL, 5mL, 10mL, 30mL sizes. Per-mL price sorted cheapest first, '
            . 'coupon codes included.';

        $faqs = $this->faqs();

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ];

        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Bacteriostatic Water vendor comparison',
            'numberOfItems' => $rows->count(),
            'itemListElement' => $rows->take(25)->values()->map(fn ($r, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $r['name'],
                    'brand' => ['@type' => 'Brand', 'name' => $r['brand_name']],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format($r['final_price'], 2, '.', ''),
                        'priceCurrency' => 'USD',
                        'availability' => 'https://schema.org/InStock',
                        'url' => url($r['product_url']),
                        'seller' => ['@type' => 'Organization', 'name' => $r['brand_name']],
                    ],
                ],
            ])->all(),
        ];

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Bacteriostatic Water',
                    'item' => url('/bacteriostatic-water')],
            ],
        ];

        $seo = [
            'key' => 'bacteriostatic-water',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => route('og.compound', ['slug' => 'bacteriostatic-water'])
                . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'image' => route('og.compound', ['slug' => 'bacteriostatic-water'])
                . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'url' => url('/bacteriostatic-water'),
            'h1' => 'Cheapest Bacteriostatic Water',
            'schema' => [$itemList, $faqSchema, $breadcrumb],
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/BacteriostaticWater', [
            'rows' => $rows,
            'stats' => [
                'vendor_count' => $vendorCount,
                'product_count' => $vCount,
                'cheapest_price' => $cheapest,
                'priciest_price' => $priciest,
                'best_per_ml' => $bestPerMl,
                'available_sizes' => $availableSizes,
            ],
            'faqs' => $faqs,
            'seo' => $seo,
        ]);
    }

    /**
     * Pull an mL volume out of an arbitrary string.
     * Matches "10ml", "10 mL", "30ML", "5-ml" etc. Rejects mg matches so
     * "10mg" doesn't get read as 10 mL.
     */
    private function extractMl(?string $text): ?int
    {
        if (!$text) return null;
        // Prefer explicit "N mL" or "N-ml" before scanning further.
        if (preg_match('/(\d+(?:\.\d+)?)\s*[-]?\s*m\s*l\b/i', $text, $m)) {
            $v = (float) $m[1];
            if ($v > 0 && $v <= 500) return (int) round($v);
        }
        return null;
    }

    /**
     * BAC-water FAQs. Hand-written to earn a FAQPage rich-result on SERP
     * and answer real user intent. Kept short (Google truncates >300 char
     * answers in the accordion). Editorial voice — no dosing advice.
     */
    private function faqs(): array
    {
        return [
            [
                'q' => 'What is bacteriostatic water?',
                'a' => 'Bacteriostatic water is sterile water containing 0.9% benzyl alcohol, which prevents bacterial growth after the vial is punctured. Researchers use it to reconstitute lyophilized peptides, so a single vial can be drawn from multiple times without contamination.',
            ],
            [
                'q' => 'How much bacteriostatic water do I need?',
                'a' => 'Typical peptide vials require 1–3 mL of BAC water each. Most researchers buy 10 mL vials and use one BAC vial for several peptides over 3–4 weeks. A 30 mL vial covers a longer research cycle at a better per-mL price.',
            ],
            [
                'q' => "What's the difference between bacteriostatic water and sterile water?",
                'a' => 'Sterile water has nothing added; it goes bad quickly once opened. Bacteriostatic water contains 0.9% benzyl alcohol as a preservative, letting a punctured vial be reused for up to 28 days. Only bacteriostatic water is appropriate for reconstituting research peptides used across multiple sessions.',
            ],
            [
                'q' => "What's the cheapest bacteriostatic water per mL right now?",
                'a' => 'Prices update daily as vendor scrapes refresh. This page ranks every listing by per-mL price so the top row is always the current best deal — usually a 10 mL or 30 mL vial from an established vendor.',
            ],
            [
                'q' => 'Is bacteriostatic water available in different sizes?',
                'a' => 'Yes. Common sizes are 3 mL, 5 mL, 10 mL, and 30 mL. 10 mL is the most common single-vial size for research use; 30 mL is generally the best per-mL value if you have a running research program.',
            ],
            [
                'q' => 'Where is bacteriostatic water shipped from?',
                'a' => "Every vendor listed ships from US-based facilities unless the brand page says otherwise. Delivery is typically 3–7 business days. Check the vendor's own site for the current shipping guarantee before ordering.",
            ],
            [
                'q' => 'Can I use bacteriostatic water for anything besides peptides?',
                'a' => 'Bacteriostatic water is a research reagent. All products listed on Peptidemap are for research use only (RUO); we do not provide any human or veterinary use guidance.',
            ],
        ];
    }
}

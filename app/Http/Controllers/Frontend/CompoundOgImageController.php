<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CompareSlug;
use App\Support\CompoundDisplay;
use App\Support\EncyclopediaSlug;
use App\Support\Og\OgCards;
use App\Support\OgImageRevision;
use App\Support\ProductRowCurrency;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-compound social card: GET /og/compound/{slug}.png, shared by
 * /product/{slug}, /compare/{slug} and /encyclopedia/{slug}. Vendor
 * count, lowest price, best $/mg and the five cheapest vendors per mg.
 */
class CompoundOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/compound';

    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(string $slug): Response
    {
        if ($redirect = $this->redirectMixedCaseSlug('og.compound', $slug)) {
            return $redirect;
        }

        $category = ProductCategory::where('slug', $slug)->where('is_active', true)->first()
            ?? ProductCategory::findForPublicSlug(EncyclopediaSlug::publicSlug($slug) ?? $slug);
        if (!$category) {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }

        $mtime = max(
            $category->updated_at?->timestamp ?? 0,
            // Prices move daily; redraw once a day.
            now()->startOfDay()->timestamp,
        );

        return $this->serveCard(
            self::CACHE_DIR,
            $slug.'-'.OgImageRevision::COPY,
            $mtime,
            self::FALLBACK_PNG,
            function () use ($category) {
                $stats = $this->priceStats($category);

                return OgCards::compound([
                    'name' => CompoundDisplay::label($category->name) ?? (string) $category->name,
                    'eyebrow' => 'Peptide price comparison',
                    'vendors' => $stats['vendors'],
                    'lowest' => $stats['lowest'],
                    'best' => $stats['best'],
                    'board' => $stats['board'],
                    'path' => '/compare/'.(CompareSlug::canonical($category->slug) ?? $category->slug),
                ]);
            }
        );
    }

    /**
     * Same priced, visible rows as the compare page, in USD for ranking
     * and shown in each listing's own currency. Per-mg figures under a
     * quarter of the median are left out as likely bad scrapes.
     *
     * @return array{vendors: int, lowest: ?string, best: ?string, board: list<array{vendor: string, value: string, ratio: float}>}
     */
    private function priceStats(ProductCategory $category): array
    {
        $products = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                    ->orWhere(fn ($qq) => $qq->whereNull('discount_price')->where('price', '>', 0));
            })
            ->with([
                'brand' => fn ($q) => $q->forCard(),
                'brand.vendorSetting' => fn ($q) => $q->forCard(),
                'brand.vendorSetting.location:id,name',
            ])
            ->get();

        $rows = ProductRowCurrency::collapseDuplicates($products->map(fn ($p) => ProductRowCurrency::cardRow($p))->all());
        $vendors = count(array_unique(array_map(fn ($r) => $r['brand_id'] ?? null, $rows)));

        $priced = array_values(array_filter($rows, fn ($r) => (float) ($r['usd_final_price'] ?? 0) > 0));
        $lowest = null;
        if ($priced) {
            usort($priced, fn ($a, $b) => (float) $a['usd_final_price'] <=> (float) $b['usd_final_price']);
            $lowest = $this->usd($priced[0], 'usd_final_price');
        }

        $perMg = array_values(array_filter($rows, fn ($r) => (float) ($r['usd_per_mg'] ?? 0) > 0));
        if (count($perMg) >= 4) {
            $values = array_map(fn ($r) => (float) $r['usd_per_mg'], $perMg);
            sort($values);
            $mid = intdiv(count($values), 2);
            $median = count($values) % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
            $perMg = array_values(array_filter($perMg, fn ($r) => (float) $r['usd_per_mg'] >= $median * 0.25));
        }
        usort($perMg, fn ($a, $b) => (float) $a['usd_per_mg'] <=> (float) $b['usd_per_mg']);

        $board = [];
        $seen = [];
        $bestUsd = $perMg ? (float) $perMg[0]['usd_per_mg'] : 0;
        foreach ($perMg as $r) {
            $vendor = $r['brand']['name'] ?? null;
            if (!$vendor || isset($seen[$vendor])) {
                continue;
            }
            $seen[$vendor] = true;
            $board[] = [
                'vendor' => (string) $vendor,
                'value' => $this->usd($r, 'usd_per_mg').'/mg',
                'ratio' => $bestUsd > 0 ? $bestUsd / (float) $r['usd_per_mg'] : 0.0,
            ];
            if (count($board) === 5) {
                break;
            }
        }

        return [
            'vendors' => $vendors,
            'lowest' => $lowest,
            'best' => $board ? $board[0]['value'] : null,
            'board' => $board,
        ];
    }

    /**
     * Dollars, so a multi-vendor card compares like with like (rows are
     * ranked in USD; a lei or pound figure beside dollars misreads).
     *
     * @param  array<string, mixed>  $row
     */
    private function usd(array $row, string $field): string
    {
        return '$'.number_format((float) ($row[$field] ?? 0), 2);
    }
}

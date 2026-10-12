<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\EncyclopediaFraming;
use App\Support\EncyclopediaSlug;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-compound OG image: GET /og/compound/{slug}.png
 * Powers social previews for /encyclopedia/{slug}. "Compound" = ProductCategory row.
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

        $category = ProductCategory::with('educationPost')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
        if (!$category) {
            $category = ProductCategory::findForPublicSlug(EncyclopediaSlug::publicSlug($slug) ?? $slug);
            if ($category) {
                $category->load('educationPost');
            }
        }
        if (!$category) return $this->fallbackOg(self::FALLBACK_PNG);

        $ep = $category->educationPost;
        $profile = EncyclopediaFraming::match($category->slug, $category->name);
        $mtime = max(
            $category->updated_at?->timestamp ?? 0,
            $ep?->updated_at?->timestamp ?? 0,
        );

        return $this->serveOgImage(
            self::CACHE_DIR,
            $slug.'-'.\App\Support\OgImageRevision::COPY,
            $mtime,
            self::FALLBACK_PNG,
            fn () => View::make('og.compound', [
                'category'    => $category,
                'fullName'    => $ep?->peptide_full_name ?: $profile?->subtitle(),
                'tagline'     => $this->tagline($ep, $category, $profile),
                'eyebrow'     => $profile?->ogEyebrow() ?? 'Peptide Compound Guide',
                'vendorCount' => $this->vendorCount($category),
            ])->render()
        );
    }

    private function tagline($educationPost, ProductCategory $category, $profile = null): ?string
    {
        $raw = $educationPost?->description ?? $category->description;
        if (!$raw && $profile) {
            $raw = $profile->cardDescription();
        }
        if (!$raw) return null;
        return Str::limit(strip_tags($raw), 180);
    }

    /**
     * Same priced-row rule as the compare page, so the card cannot
     * count a vendor the page leaves out.
     */
    private function vendorCount(ProductCategory $category): int
    {
        return Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere(function ($qq) {
                      $qq->whereNull('discount_price')->where('price', '>', 0);
                  });
            })
            ->distinct('brand_id')
            ->count('brand_id');
    }
}

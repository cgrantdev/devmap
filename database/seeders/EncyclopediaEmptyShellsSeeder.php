<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use Illuminate\Database\Seeder;

/**
 * Fills existing empty encyclopedia shells from the CMS draft pack.
 *
 * Matches the live category slug exactly. Does not create categories.
 * Skips a shell whose overview is already filled (20+ characters of text).
 * Does not write the retatrutide encyclopedia page. Safe to run twice.
 */
class EncyclopediaEmptyShellsSeeder extends Seeder
{
    /** @var list<string> */
    public const SLUGS = [
        'kpv',
        'adamax',
        'BPC-157-TB-500',
        'CJC-1295-Ipamorelin',
        'Dermorphin',
        'dihexa',
        'Elamipretide',
        'epitalon',
        'ghrp-2',
        'ghrp-6',
        'glow',
        'glutathione',
        'klow-blend-ghk-cu-bpc-157-tb-500-kpv',
        'll-37',
        'Mazdutide',
        'Melanotan-I',
        'Melanotan-II',
        'Orexin-A',
        'Semax',
        'snap-8',
        'Survodutide',
        'thymosin-alpha-1',
    ];

    /** @var array<string, string> */
    private const IMAGES = [
        'kpv' => 'kpv-featured.png',
        'adamax' => 'adamax-featured.png',
        'BPC-157-TB-500' => 'bpc-157-tb-500-featured.png',
        'CJC-1295-Ipamorelin' => 'cjc-1295-ipamorelin-featured.png',
        'Dermorphin' => 'Dermorphin-featured.png',
        'dihexa' => 'dihexa-featured.png',
        'Elamipretide' => 'elamipretide-featured.png',
        'epitalon' => 'epitalon-featured.png',
        'ghrp-2' => 'ghrp-2-featured.png',
        'ghrp-6' => 'ghrp-6-featured.png',
        'glow' => 'glow-featured.png',
        'glutathione' => 'glutathione-featured.png',
        'klow-blend-ghk-cu-bpc-157-tb-500-kpv' => 'klow-featured.png',
        'll-37' => 'll-37-featured.png',
        'Mazdutide' => 'Mazdutide-featured.png',
        'Melanotan-I' => 'melanotan-i-featured.png',
        'Melanotan-II' => 'melanotan-ii-featured.png',
        'Orexin-A' => 'Orexin-A-featured.png',
        'Semax' => 'semax-featured.png',
        'snap-8' => 'snap-8-featured.png',
        'Survodutide' => 'Survodutide-featured.png',
        'thymosin-alpha-1' => 'thymosin-alpha-1-featured.png',
    ];

    /** @var array<string, list<string>> */
    public array $report = [
        'filled' => [],
        'created_posts' => [],
        'skipped_already_filled' => [],
        'skipped_case_mismatch' => [],
        'skipped_slug_taken' => [],
        'missing_category' => [],
        'missing_draft' => [],
    ];

    public function run(): void
    {
        $parser = new EncyclopediaShellParser();

        foreach (self::SLUGS as $slug) {
            if (strtolower($slug) === 'retatrutide') {
                continue;
            }
            $this->fillSlug($slug, $parser);
        }

        $this->command?->info(sprintf(
            'Encyclopedia shells: filled %d, already filled %d, case mismatch %d, missing category %d, slug taken %d, missing draft %d.',
            count($this->report['filled']),
            count($this->report['skipped_already_filled']),
            count($this->report['skipped_case_mismatch']),
            count($this->report['missing_category']),
            count($this->report['skipped_slug_taken']),
            count($this->report['missing_draft'])
        ));
    }

    private function fillSlug(string $slug, EncyclopediaShellParser $parser): void
    {
        $bodyPath = $this->dataPath($slug, 'body.md');
        $metaPath = $this->dataPath($slug, 'meta.md');
        if (!is_file($bodyPath) || !is_file($metaPath)) {
            $this->report['missing_draft'][] = $slug;

            return;
        }

        $match = $this->matchCategory($slug);
        if ($match['status'] === 'missing') {
            $this->report['missing_category'][] = $slug;

            return;
        }
        if ($match['status'] !== 'exact' || !$match['category'] instanceof ProductCategory) {
            $this->report['skipped_case_mismatch'][] = $slug.' (stored: '.($match['category']->slug ?? 'none').')';

            return;
        }

        $category = $match['category'];
        $post = EducationPost::query()->where('product_category_id', $category->id)->first();
        if ($post && $this->overviewIsFilled($post->overview)) {
            $this->report['skipped_already_filled'][] = $slug;

            return;
        }

        $created = false;
        if (!$post) {
            if (EducationPost::query()->where('slug', $slug)->exists()) {
                $this->report['skipped_slug_taken'][] = $slug;

                return;
            }
            $post = new EducationPost([
                'product_category_id' => $category->id,
                'slug' => $slug,
                'published_at' => now(),
            ]);
            $created = true;
        }

        $attributes = $parser->parse(
            $slug,
            (string) file_get_contents($bodyPath),
            (string) file_get_contents($metaPath)
        );
        $attributes = array_intersect_key($attributes, array_flip($post->getFillable()));

        $post->fill($attributes);
        $post->product_category_id = $category->id;
        $post->slug = $slug;
        $post->status = 'published';
        $post->show_in_encyclopedia = true;
        $post->published_at = $post->published_at ?? now();

        $image = $this->imageWebPath($slug);
        if ($image) {
            $post->seo_og_image = $image;
        }

        $keywords = $post->tags;
        if (!$post->education_tag && is_array($keywords) && isset($keywords[0]) && is_string($keywords[0]) && $keywords[0] !== '') {
            $post->education_tag = mb_substr($keywords[0], 0, 255);
        }

        $post->save();
        $this->replaceStubDescription($category, $post->description);

        $this->report['filled'][] = $slug;
        if ($created) {
            $this->report['created_posts'][] = $slug;
        }
    }

    /**
     * @return array{status: string, category: ?ProductCategory}
     */
    private function matchCategory(string $slug): array
    {
        $rows = ProductCategory::query()->where('slug', $slug)->get();
        $exact = $rows->first(fn (ProductCategory $category) => $category->slug === $slug);
        if ($exact) {
            return ['status' => 'exact', 'category' => $exact];
        }
        if ($rows->isNotEmpty()) {
            return ['status' => 'case', 'category' => $rows->first()];
        }

        $folded = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', [mb_strtolower($slug)])
            ->get();
        $exactFold = $folded->first(fn (ProductCategory $category) => $category->slug === $slug);
        if ($exactFold) {
            return ['status' => 'exact', 'category' => $exactFold];
        }
        if ($folded->isNotEmpty()) {
            return ['status' => 'case', 'category' => $folded->first()];
        }

        return ['status' => 'missing', 'category' => null];
    }

    private function overviewIsFilled(?string $overview): bool
    {
        return mb_strlen(trim(strip_tags((string) $overview))) >= 20;
    }

    private function replaceStubDescription(ProductCategory $category, ?string $short): void
    {
        $short = trim((string) $short);
        if ($short === '') {
            return;
        }
        $description = strtolower(trim((string) $category->description));
        $replace = $description === ''
            || str_contains($description, 'peptide encyclopedia')
            || str_contains($description, ' peptides.');
        if (!$replace) {
            return;
        }
        $category->description = $short;
        $category->save();
    }

    private function imageWebPath(string $slug): ?string
    {
        $file = self::IMAGES[$slug] ?? null;
        if (!$file) {
            return null;
        }
        $relative = '/images/encyclopedia/'.$file;
        if (!is_file(public_path(ltrim($relative, '/')))) {
            return null;
        }

        return $relative;
    }

    private function dataPath(string $slug, string $file): string
    {
        return database_path('seeders/data/encyclopedia-shells/'.$slug.'/'.$file);
    }
}

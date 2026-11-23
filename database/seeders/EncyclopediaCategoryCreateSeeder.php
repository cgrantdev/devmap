<?php

namespace Database\Seeders;

use App\Models\CategoryAlias;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Creates encyclopedia categories that do not exist yet, then fills the
 * education post from the shell draft.
 *
 * EncyclopediaEmptyShellsSeeder only fills a category that is already
 * there. This seeder is the create path for new slugs. It is data-driven:
 * add a slug to SLUGS and a DETAILS row, and drop body.md plus meta.md in
 * database/seeders/data/encyclopedia-shells/<slug>/.
 *
 * Match order for each slug:
 *  1. CategoryAlias keywords listed for the entry (exact, case-insensitive).
 *  2. EncyclopediaCategoryMatch (exactly one LOWER(slug) row).
 * More than one distinct category across those checks is a collision:
 * skip and log. Exactly one match is reused unchanged (no rename, and a
 * non-stub description is left alone) and the post is filled only when
 * its overview is empty. is_active on an existing row is never written.
 * preserve_active also leaves a stub description alone. No match creates
 * the category. create_inactive inserts that new row with is_active false;
 * every other create stays active. No product, vendor, or price rows are
 * written. Each slug's writes run in one DB::transaction and use
 * saveQuietly(). Skip lines go out through a defined()-guarded helper.
 * Safe to run twice.
 */
class EncyclopediaCategoryCreateSeeder extends Seeder
{
    /** @var list<string> */
    public const SLUGS = [
        'pemvidutide',
        'hexarelin',
        'cagrisema',
        'cbl-514',
    ];

    /**
     * @var array<string, array{
     *     name: string,
     *     aliases: list<string>,
     *     image: string,
     *     cas: string,
     *     copy_research_area_from: string,
     *     blank_chemistry: bool,
     *     create_inactive?: bool,
     *     preserve_active?: bool,
     *     blank_sequence?: bool,
     *     read_time?: string
     * }>
     */
    private const DETAILS = [
        'pemvidutide' => [
            'name' => 'Pemvidutide',
            'aliases' => ['pemvidutide', 'ALT-801'],
            'image' => 'pemvidutide-featured.png',
            'cas' => '2538014-94-5',
            'copy_research_area_from' => 'Survodutide',
            // Public formula and molecular-weight sources disagree, so both stay empty.
            'blank_chemistry' => true,
        ],
        // Live product pages already use slug hexarelin. That row may be
        // inactive on purpose. Reuse it without touching is_active. A
        // database with no match gets an inactive category so the public
        // page stays 404 until someone activates it.
        'hexarelin' => [
            'name' => 'Hexarelin',
            'aliases' => ['hexarelin', 'examorelin'],
            'image' => 'hexarelin-featured.png',
            'cas' => '140703-51-1',
            'copy_research_area_from' => '',
            'blank_chemistry' => false,
            'create_inactive' => true,
            'preserve_active' => true,
        ],
        // New content-only page. Created inactive so /encyclopedia/cagrisema stays
        // 404 until someone activates it. No aliases: a "CagriSema" alias could
        // resolve to active category 592 (Semaglutide / Cagrilintide Blend) or 437
        // (Cagrilintide) through CategoryAlias, and the seeder would reuse that row.
        'cagrisema' => [
            'name' => 'CagriSema',
            'aliases' => [],
            'image' => 'cagrisema-featured.png',
            'cas' => '',
            'copy_research_area_from' => '',
            // Fixed-dose combination of two molecules: no single formula, MW or CAS.
            'blank_chemistry' => true,
            'create_inactive' => true,
            'preserve_active' => true,
        ],
        // Content-only page. Created inactive so /encyclopedia/cbl-514 stays
        // 404 until someone activates it. No aliases, no featured image, no
        // chemistry, no sequence, and no product or vendor rows.
        'cbl-514' => [
            'name' => 'CBL-514',
            'aliases' => [],
            'image' => '',
            'cas' => '',
            'copy_research_area_from' => '',
            'blank_chemistry' => true,
            'blank_sequence' => true,
            'create_inactive' => true,
            'preserve_active' => true,
            'read_time' => '11',
        ],
    ];

    /** @var array<string, list<string>> */
    public array $report = [
        'created_categories' => [],
        'reused_categories' => [],
        'filled' => [],
        'created_posts' => [],
        'skipped_already_filled' => [],
        'skipped_slug_collision' => [],
        'skipped_slug_taken' => [],
        'missing_draft' => [],
    ];

    public function run(): void
    {
        $parser = new EncyclopediaShellParser;

        foreach (self::SLUGS as $slug) {
            $this->seedSlug($slug, $parser);
        }

        $this->info(sprintf(
            'Encyclopedia category create: created %d, reused %d, filled %d, already filled %d, collision %d, slug taken %d, missing draft %d.',
            count($this->report['created_categories']),
            count($this->report['reused_categories']),
            count($this->report['filled']),
            count($this->report['skipped_already_filled']),
            count($this->report['skipped_slug_collision']),
            count($this->report['skipped_slug_taken']),
            count($this->report['missing_draft'])
        ));
    }

    private function seedSlug(string $slug, EncyclopediaShellParser $parser): void
    {
        $entry = self::DETAILS[$slug] ?? null;
        if (! is_array($entry)) {
            $this->report['missing_draft'][] = $slug;

            return;
        }

        $resolved = $this->resolveCategory($slug, $entry);
        if ($resolved['status'] === 'collision') {
            $this->report['skipped_slug_collision'][] = $slug;

            return;
        }

        $category = $resolved['category'];
        $post = null;
        if ($category instanceof ProductCategory) {
            $this->report['reused_categories'][] = $slug;
            $post = EducationPost::query()->where('product_category_id', $category->id)->first();
            if ($post && $this->overviewIsFilled($post->overview)) {
                $this->noteAlreadyFilled($slug, $post, $entry);

                return;
            }
        }

        $bodyPath = $this->dataPath($slug, 'body.md');
        $metaPath = $this->dataPath($slug, 'meta.md');
        if (! is_file($bodyPath) || ! is_file($metaPath)) {
            $this->report['missing_draft'][] = $slug;
            $this->warn($entry['name'].' encyclopedia create: skipped, missing draft.');

            return;
        }

        $attributes = $parser->parse(
            $slug,
            (string) file_get_contents($bodyPath),
            (string) file_get_contents($metaPath)
        );

        DB::transaction(function () use ($slug, $entry, $attributes, &$category, &$post) {
            $createdCategory = false;
            if (! $category instanceof ProductCategory) {
                $category = $this->createCategory($slug, $entry, $attributes);
                $createdCategory = true;
                $this->report['created_categories'][] = $slug;
            }

            $storedSlug = (string) $category->slug;
            $post = $post ?? EducationPost::query()->where('product_category_id', $category->id)->first();
            if ($post && $this->overviewIsFilled($post->overview)) {
                $this->repairExisting($post, $slug, $entry);
                $this->report['skipped_already_filled'][] = $slug;

                return;
            }

            $createdPost = false;
            if (! $post) {
                if ($this->educationSlugTaken($storedSlug)) {
                    $this->report['skipped_slug_taken'][] = $slug;
                    $this->warn($entry['name'].' encyclopedia create: skipped, education slug taken ('.$storedSlug.').');

                    return;
                }
                $post = new EducationPost([
                    'product_category_id' => $category->id,
                    'slug' => $storedSlug,
                    'published_at' => now(),
                ]);
                $createdPost = true;
            } elseif ($post->slug !== $storedSlug && $this->educationSlugTaken($storedSlug, $post->id)) {
                $this->report['skipped_slug_taken'][] = $slug;
                $this->warn($entry['name'].' encyclopedia create: skipped, education slug taken ('.$storedSlug.').');

                return;
            }

            $fillable = array_intersect_key($attributes, array_flip($post->getFillable()));
            $post->fill($fillable);
            $post->product_category_id = $category->id;
            $post->slug = $storedSlug;
            $post->status = 'published';
            $post->show_in_encyclopedia = true;
            $post->published_at = $post->published_at ?? now();
            if (($entry['cas'] ?? '') !== '') {
                $post->cas_registry_number = $entry['cas'];
            }
            if (($entry['blank_chemistry'] ?? false) === true) {
                $post->molecular_formula = null;
                $post->molecular_weight = null;
                if (($entry['cas'] ?? '') === '') {
                    $post->cas_registry_number = null;
                }
            }
            if (($entry['blank_sequence'] ?? false) === true) {
                $post->amino_acid_sequence = null;
            }
            if (array_key_exists('read_time', $entry) && Schema::hasColumn('education_posts', 'read_time')) {
                $post->read_time = $entry['read_time'];
            }

            $image = $this->imageWebPath($entry['image']);
            if ($image) {
                $post->seo_og_image = $image;
            }

            $keywords = $post->tags;
            if (! $post->education_tag && is_array($keywords) && isset($keywords[0]) && is_string($keywords[0]) && $keywords[0] !== '') {
                $post->education_tag = mb_substr($keywords[0], 0, 255);
            }

            $post->saveQuietly();
            if (! $createdCategory && ($entry['preserve_active'] ?? false) !== true) {
                $this->replaceStubDescription($category, $post->description);
            }

            $this->report['filled'][] = $slug;
            if ($createdPost) {
                $this->report['created_posts'][] = $slug;
            }
        });
    }

    /**
     * @param  array{name: string, aliases: list<string>, image: string, cas: string, copy_research_area_from: string, blank_chemistry: bool}  $entry
     * @return array{status: string, category: ?ProductCategory}
     */
    private function resolveCategory(string $slug, array $entry): array
    {
        $match = EncyclopediaCategoryMatch::find($slug, $this->command, $entry['name'].' encyclopedia create');
        if ($match->skippedSlugCollision) {
            return ['status' => 'collision', 'category' => null];
        }

        $ids = $this->aliasCategoryIds($entry['aliases']);
        if ($match->category) {
            $ids[] = (int) $match->category->id;
        }
        $ids = array_values(array_unique($ids));

        if (count($ids) > 1) {
            $stored = ProductCategory::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->pluck('slug')
                ->implode(', ');
            $this->warn($entry['name'].' encyclopedia create: skipped slug collision ('.$stored.').');

            return ['status' => 'collision', 'category' => null];
        }

        if (count($ids) === 1) {
            $category = ProductCategory::query()->find($ids[0]);
            if (! $category) {
                return ['status' => 'collision', 'category' => null];
            }

            return ['status' => 'match', 'category' => $category];
        }

        return ['status' => 'missing', 'category' => null];
    }

    /**
     * @param  list<string>  $aliases
     * @return list<int>
     */
    private function aliasCategoryIds(array $aliases): array
    {
        if ($aliases === [] || ! Schema::hasTable('category_aliases')) {
            return [];
        }

        $query = CategoryAlias::query();
        $query->where(function ($inner) use ($aliases) {
            foreach ($aliases as $alias) {
                $inner->orWhereRaw('LOWER(keyword) = ?', [mb_strtolower($alias)]);
            }
        });

        return $query->pluck('product_category_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{name: string, aliases: list<string>, image: string, cas: string, copy_research_area_from: string, blank_chemistry: bool, create_inactive?: bool, preserve_active?: bool}  $entry
     * @param  array<string, mixed>  $attributes
     */
    private function createCategory(string $slug, array $entry, array $attributes): ProductCategory
    {
        $category = new ProductCategory;
        $category->name = $entry['name'];
        $category->slug = $slug;
        $category->is_active = ($entry['create_inactive'] ?? false) === true ? false : true;
        $category->description = is_string($attributes['description'] ?? null) ? $attributes['description'] : null;
        $category->meta_title = is_string($attributes['seo_page_title'] ?? null) ? $attributes['seo_page_title'] : null;
        $category->meta_description = is_string($attributes['seo_description'] ?? null) ? $attributes['seo_description'] : null;
        if (($entry['copy_research_area_from'] ?? '') !== '') {
            $this->copySiblingClassification($category, $entry['copy_research_area_from']);
        }
        $category->saveQuietly();

        return $category;
    }

    /**
     * Copy research_area, and a type column when the schema has one,
     * from the sibling category. A missing or ambiguous sibling leaves
     * both empty. Product rows are never created, so product types are
     * not copied.
     */
    private function copySiblingClassification(ProductCategory $category, string $fromSlug): void
    {
        $rows = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', [mb_strtolower($fromSlug)])
            ->orderBy('id')
            ->get();
        if ($rows->count() !== 1) {
            return;
        }

        $source = $rows->first();
        if (Schema::hasColumn('product_categories', 'research_area')) {
            $category->research_area = $source->research_area;
        }
        if (Schema::hasColumn('product_categories', 'type')) {
            $category->type = $source->type;
        }
    }

    private function educationSlugTaken(string $storedSlug, ?int $exceptId = null): bool
    {
        $query = EducationPost::query()->whereRaw('LOWER(slug) = ?', [mb_strtolower($storedSlug)]);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
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
        if (! $replace) {
            return;
        }
        $category->description = $short;
        $category->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function noteAlreadyFilled(string $slug, EducationPost $post, array $entry): void
    {
        DB::transaction(function () use ($post, $entry, $slug) {
            $this->repairExisting($post, $slug, $entry);
        });
        $this->report['skipped_already_filled'][] = $slug;
    }

    /**
     * A fresh migrate can insert this post from an earlier seeder run.
     * That run is before the read_time column, and the 14 Nov FAQ migration
     * then archives faqs for slugs that are not on the render allowlist.
     * Fill those two gaps once. A second run finds them set and writes nothing.
     * Other slugs are not repaired.
     *
     * @param  array<string, mixed>  $entry
     */
    private function repairExisting(EducationPost $post, string $slug, array $entry): void
    {
        $dirty = false;
        if (array_key_exists('read_time', $entry) && Schema::hasColumn('education_posts', 'read_time')) {
            $current = $post->read_time;
            if ($current === null || $current === '') {
                $post->read_time = $entry['read_time'];
                $dirty = true;
            }
        }
        if ($slug === 'cbl-514' && ! $this->faqList($post->faqs)) {
            $bodyPath = $this->dataPath($slug, 'body.md');
            $metaPath = $this->dataPath($slug, 'meta.md');
            if (is_file($bodyPath) && is_file($metaPath)) {
                $parsed = (new EncyclopediaShellParser)->parse(
                    $slug,
                    (string) file_get_contents($bodyPath),
                    (string) file_get_contents($metaPath)
                );
                $post->faqs = $parsed['faqs'];
                $dirty = true;
            }
        }
        if ($dirty) {
            $post->saveQuietly();
        }
    }

    private function faqList(mixed $faqs): bool
    {
        return is_array($faqs) && $faqs !== [];
    }

    private function info(string $line): void
    {
        Log::info($line);
        $this->command?->info($line);
        $this->emit('stdout', $line);
    }

    private function warn(string $line): void
    {
        Log::warning($line);
        $this->command?->warn($line);
        $this->emit('stderr', $line);
    }

    private function emit(string $stream, string $line): void
    {
        $handle = match ($stream) {
            'stderr' => defined('STDERR') ? STDERR : null,
            default => defined('STDOUT') ? STDOUT : null,
        };
        if (is_resource($handle)) {
            @fwrite($handle, $line.PHP_EOL);
        }
    }

    private function imageWebPath(string $file): ?string
    {
        $relative = '/images/encyclopedia/'.$file;
        if (! is_file(public_path(ltrim($relative, '/')))) {
            return null;
        }

        return $relative;
    }

    private function dataPath(string $slug, string $file): string
    {
        return database_path('seeders/data/encyclopedia-shells/'.$slug.'/'.$file);
    }
}

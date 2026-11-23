<?php

namespace Database\Seeders;

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Support\GuardedFieldWriter;
use App\Support\SimpleMarkdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;

/**
 * 23 Nov 2026 state-enforcement update of two guides:
 * peptide-legality-fda-ruo-compounding (new dated state-actions and
 * Australia sections, federal status lines to 23 Nov) and
 * compounded-vs-ruo-peptides (Kentucky alert in the state-law note,
 * ASHP/ACCP and Public Citizen in stakeholder statements, FAQ date).
 *
 * Guard is the sha256 of the publisher render on main 6fd0905a, which is
 * also the live content read on 23 Nov 2026 08:54 ICT (the 16 Nov
 * migrations had run). old_read_time is that commit's catalog value. A
 * matching row is logged "updated" and passed to syncOnly, which writes
 * content, read time and seo_schema from the current catalog. A row that
 * already equals the current render is logged "already current" and is
 * not written. Drift, a missing row, duplicate slugs, a slug that does
 * not match the catalog slug exactly, or an admin edit to another
 * catalog-owned column logs a guard-mismatch WARNING and is skipped. A
 * second run writes nothing.
 */
class EducationalStateEnforcementNovember23Seeder extends Seeder
{
    public const MIGRATION = '2026_11_23_200000';

    /**
     * Slug order is the order syncOnly receives the rows that pass.
     *
     * @var array<string, array{table: string, read_column: string, old_sha256: string, old_read_time: string}>
     */
    public const PAGES = [
        'peptide-legality-fda-ruo-compounding' => [
            'table' => 'educational_guides',
            'read_column' => 'reading_time',
            'old_sha256' => '1d91a32f41d9013683dcadff57d933e5cb6ccf9d88ebc4460ff78beed5d1a278',
            'old_read_time' => '25 Min Read',
        ],
        'compounded-vs-ruo-peptides' => [
            'table' => 'educational_guides',
            'read_column' => 'reading_time',
            'old_sha256' => '9df75226a4fcfc4c0eca8ab45f32b6d61dc08f12fc1f51aff27d3851c4942a77',
            'old_read_time' => '13 Min Read',
        ],
    ];

    /** @var array<string, string> */
    public array $results = [];

    /** @var list<string> */
    public array $lines = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $catalog = null;

    public function run(): void
    {
        $this->results = [];
        $this->lines = [];
        $passed = [];

        foreach (self::PAGES as $slug => $spec) {
            $key = $this->key($slug);
            $model = $spec['table'] === 'educational_guides' ? EducationalGuide::class : Blog::class;
            $rows = $model::query()
                ->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($slug))])
                ->orderBy('id')
                ->get();

            if ($rows->count() !== 1) {
                $this->warn($key, $rows->count() === 0 ? 'missing row' : 'duplicate slugs');

                continue;
            }

            /** @var Model $row */
            $row = $rows->first();
            if ((string) $row->getAttribute('slug') !== $slug) {
                $this->warn($key, 'slug form');

                continue;
            }

            $content = (string) $row->getAttribute('content');
            if (hash('sha256', $content) === $spec['old_sha256']) {
                $drift = $this->columnDrift($row, $slug);
                if ($drift !== null) {
                    $this->warn($key, 'column drift: '.$drift);

                    continue;
                }
                $this->info($key, GuardedFieldWriter::UPDATED);
                $passed[] = $slug;

                continue;
            }

            if ($content === $this->render($slug)) {
                $this->info($key, GuardedFieldWriter::ALREADY);

                continue;
            }

            $this->warn($key, 'content drift');
        }

        EducationalContentPublisher::syncOnly($passed);
    }

    public function render(string $slug): string
    {
        foreach ($this->catalog() as $page) {
            if (($page['slug'] ?? null) !== $slug) {
                continue;
            }
            $markdown = (string) file_get_contents(resource_path('content/educational/'.$page['file']));

            return SimpleMarkdown::toHtml(EducationalContentPublisher::prepare($markdown));
        }

        throw new \RuntimeException(self::MIGRATION.': catalog has no page for '.$slug);
    }

    /**
     * syncOnly rewrites every catalog-owned column of a passing row, not
     * just content and read time. A row whose content still matches the
     * 6fd0905a render but whose other catalog-owned columns were edited
     * (title, SEO, status, featured flag, guide cover or date) is skipped,
     * so an admin edit is never overwritten. Returns the first drifted
     * column, or null.
     */
    private function columnDrift(Model $row, string $slug): ?string
    {
        $page = null;
        foreach ($this->catalog() as $candidate) {
            if (($candidate['slug'] ?? null) === $slug) {
                $page = $candidate;
                break;
            }
        }
        if ($page === null) {
            return 'catalog';
        }

        $isGuide = self::PAGES[$slug]['table'] === 'educational_guides';
        $map = [
            'title' => 'h1',
            'description' => 'lede',
            'seo_page_title' => 'seo_title',
            'seo_description' => 'seo_description',
            'seo_og_title' => 'og_title',
            'seo_og_description' => 'og_description',
        ];
        $map += $isGuide
            ? ['tag' => 'tag']
            : ['blog_type' => 'blog_type', 'key_points' => 'key_points', 'tags' => 'tags'];

        foreach ($map as $column => $catalogKey) {
            if (json_encode($row->getAttribute($column)) !== json_encode($page[$catalogKey] ?? null)) {
                return $column;
            }
        }
        if ((string) $row->getAttribute('status') !== 'published') {
            return 'status';
        }
        if ($isGuide) {
            if ((bool) $row->getAttribute('is_featured')) {
                return 'is_featured';
            }
            $published = $row->getAttribute('published_at');
            if ($published === null || $published->toDateString() !== ($page['published_at'] ?? null)) {
                return 'published_at';
            }
            if (Schema::hasColumn('educational_guides', 'cover') && filled($page['image_file'] ?? null)
                && (string) $row->getAttribute('cover') !== '/images/educational/'.basename((string) $page['image_file'])) {
                return 'cover';
            }
        }

        return null;
    }

    private function key(string $slug): string
    {
        $prefix = self::PAGES[$slug]['table'] === 'educational_guides' ? 'guide:' : 'blog:';

        return $prefix.$slug.'.content';
    }

    private function info(string $key, string $status): void
    {
        $line = self::MIGRATION.' '.$key.': '.$status;
        $this->results[$key] = $status;
        $this->lines[] = $line;
        Log::info($line);
        $this->command?->info($line);
        $this->emit('stdout', $line);
    }

    private function warn(string $key, string $reason): void
    {
        $line = self::MIGRATION.' '.$key.': '.GuardedFieldWriter::MISMATCH.' ('.$reason.')';
        $this->results[$key] = GuardedFieldWriter::MISMATCH;
        $this->lines[] = $line;
        Log::warning($line, ['reason' => $reason]);
        $this->command?->error($line);
        $this->emit('stderr', $line);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        if ($this->catalog === null) {
            $catalog = (new ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null);
            $this->catalog = is_array($catalog) ? $catalog : [];
        }

        return $this->catalog;
    }

    private function emit(string $stream, string $line): void
    {
        $handle = match ($stream) {
            'stderr' => defined('STDERR') ? STDERR : null,
            default => defined('STDOUT') ? STDOUT : null,
        };
        if (is_resource($handle)) {
            fwrite($handle, $line.PHP_EOL);
        }
    }
}

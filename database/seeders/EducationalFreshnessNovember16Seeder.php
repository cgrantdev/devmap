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
use ReflectionMethod;

/**
 * 16 Nov 2026 refresh of two guides and six blogs.
 *
 * Guard is the sha256 of the publisher render on main 1a9fb77
 * (SimpleMarkdown::toHtml of EducationalContentPublisher::prepare).
 * old_read_time is that commit's catalog value. A matching row is
 * logged "updated" and passed to syncOnly, which writes content,
 * read time and seo_schema from the current catalog. A row that
 * already equals the current render is logged "already current"
 * and is not written. Drift, a missing row, duplicate slugs, or a
 * slug that does not match the catalog slug exactly logs a
 * guard-mismatch WARNING and is skipped. A second run writes nothing.
 */
class EducationalFreshnessNovember16Seeder extends Seeder
{
    public const MIGRATION = '2026_11_16_200000';

    /**
     * Slug order is the order syncOnly receives the rows that pass.
     *
     * @var array<string, array{table: string, read_column: string, old_sha256: string, old_read_time: string}>
     */
    public const PAGES = [
        'compounded-vs-ruo-peptides' => [
            'table' => 'educational_guides',
            'read_column' => 'reading_time',
            'old_sha256' => '15bcc8f976481363d69bfe9cf1560984f59826670f3bed7d26f04634fbf766c5',
            'old_read_time' => '11 Min Read',
        ],
        'peptide-legality-fda-ruo-compounding' => [
            'table' => 'educational_guides',
            'read_column' => 'reading_time',
            'old_sha256' => '0e5c7aca67bcc276d5e769181c7e4a70c2221596aa1b933bbd82589d81dff8dd',
            'old_read_time' => '24 Min Read',
        ],
        'bpc-157-vs-tb-500-evidence' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => 'e84a1a98e34b63a5fea60db57e1be2a09eb95a87d6bc20a519f84627ae78f3c2',
            'old_read_time' => '14 Min Read',
        ],
        'retatrutide-side-effects-trial-data' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => '99c230f1ff6501bbc6458277df8d8b9d23349fc8dab0d08d7c877be7dc42c89d',
            'old_read_time' => '20 Min Read',
        ],
        'retatrutide-vs-semaglutide' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => '125e30ea8a30c9d1aabd9a78b9e98eac7b4e06f4ede97e307f533c5d64fc8fbe',
            'old_read_time' => '19 Min Read',
        ],
        'eloralintide-tirzepatide-vs-retatrutide' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => 'e830d965d1491ba0ceac1d90503958028bbd7bffb211ccc1b38ae94b7cd83254',
            'old_read_time' => '6 Min Read',
        ],
        'hhs-503a-peptide-enforcement-discretion-2026' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => '67e29ff7d1e909820c540bc3b1515cef47397883594cfb3789941e36c45df024',
            'old_read_time' => '4 Min Read',
        ],
        'bpc-157-vs-ghk-cu' => [
            'table' => 'blogs',
            'read_column' => 'read_time',
            'old_sha256' => 'aa619468d080a6ad772b2efcecd2822b4349793f1435622287bd8a5aeccbc586',
            'old_read_time' => '10 Min Read',
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

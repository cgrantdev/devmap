<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use Illuminate\Database\Seeder;

/**
 * Deepens an existing elamipretide encyclopedia post with the Forzinity
 * accelerated-approval wording.
 *
 * Matches LOWER(slug) = elamipretide and exactly one category. Does not
 * create a category, does not rename ProductCategory.slug, and does not
 * create an education post. Replacement fields come from the Elamipretide
 * shell (the same source a first empty-shell fill parses). The three #12
 * FAQs are kept; addendum FAQs are added. Safe to run twice.
 *
 * drugStatus has no education_posts column and is not rendered from the
 * post. The shell field is the draft source; the same facts are stored on
 * regulatory_important_note, regulatory_subsections, and faqs.
 */
class ElamipretideForzinitySeeder extends Seeder
{
    public const SLUG = 'elamipretide';

    /** @var list<string> */
    private const KEPT_QUESTIONS = [
        'is elamipretide fda-approved?',
        'is accelerated approval "full" approval?',
        'are peptidemap vendor vials forzinity?',
    ];

    /** @var list<string> */
    private const FIELDS = [
        'molecular_formula',
        'molecular_weight',
        'cas_registry_number',
        'regulatory_important_note',
        'regulatory_subsections',
        'human_use_subsections',
        'references',
        'faqs',
    ];

    /** @var array<string, bool> */
    public array $report = [
        'updated' => false,
        'missing_category' => false,
        'missing_post' => false,
        'skipped_slug_collision' => false,
        'missing_draft' => false,
    ];

    public function run(): void
    {
        $parsed = $this->shellAttributes();
        if ($parsed === null) {
            $this->report['missing_draft'] = true;
            $this->command?->warn('Elamipretide Forzinity deepen: shell draft missing or unreadable.');

            return;
        }

        $category = $this->category();
        if (! $category) {
            return;
        }

        $post = EducationPost::query()
            ->where('product_category_id', $category->id)
            ->orderBy('id')
            ->first();
        if (! $post) {
            $this->report['missing_post'] = true;
            $this->command?->warn('Elamipretide Forzinity deepen: category exists but has no education post.');

            return;
        }

        $before = $this->snapshot($post);
        $this->apply($post, $parsed);
        if ($this->snapshot($post) === $before) {
            $this->command?->info('Elamipretide Forzinity deepen: already current.');

            return;
        }

        $post->save();
        $this->report['updated'] = true;
        $this->command?->info('Elamipretide Forzinity deepen: updated '.$category->slug.'.');
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function apply(EducationPost $post, array $parsed): void
    {
        $post->molecular_formula = $parsed['molecular_formula'];
        $post->molecular_weight = $parsed['molecular_weight'];
        $post->cas_registry_number = $parsed['cas_registry_number'];
        $post->regulatory_important_note = $parsed['regulatory_important_note'];
        $post->regulatory_subsections = $parsed['regulatory_subsections'];
        $post->human_use_subsections = $parsed['human_use_subsections'];
        $post->references = $parsed['references'];
        $post->faqs = $this->mergeFaqs(
            is_array($post->faqs) ? $post->faqs : [],
            is_array($parsed['faqs']) ? $parsed['faqs'] : []
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function shellAttributes(): ?array
    {
        $bodyPath = database_path('seeders/data/encyclopedia-shells/Elamipretide/body.md');
        $metaPath = database_path('seeders/data/encyclopedia-shells/Elamipretide/meta.md');
        if (! is_file($bodyPath) || ! is_file($metaPath)) {
            return null;
        }

        $parsed = (new EncyclopediaShellParser)->parse(
            'Elamipretide',
            (string) file_get_contents($bodyPath),
            (string) file_get_contents($metaPath)
        );
        $blob = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        if (! str_contains($blob, '146bf34c-76f2-48db-ac07-fb29cce2cd75')
            || ! str_contains($blob, 'mitochondrial cardiolipin binder')
            || ! str_contains((string) ($parsed['cas_registry_number'] ?? ''), '736992-21-5')
            || ! is_array($parsed['faqs'] ?? null)
            || count($parsed['faqs']) < 7
            || ! is_array($parsed['references'] ?? null)
            || count($parsed['references']) < 6) {
            return null;
        }

        return $parsed;
    }

    private function category(): ?ProductCategory
    {
        $rows = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', [self::SLUG])
            ->orderBy('id')
            ->get();

        if ($rows->count() > 1) {
            $this->report['skipped_slug_collision'] = true;
            $stored = $rows->map(fn (ProductCategory $category) => $category->slug)->implode(', ');
            $this->command?->warn('Elamipretide Forzinity deepen: skipped slug collision ('.$stored.').');

            return null;
        }

        if ($rows->isEmpty()) {
            $this->report['missing_category'] = true;

            return null;
        }

        return $rows->first();
    }

    /**
     * @param  list<mixed>  $existing
     * @param  list<mixed>  $shellFaqs
     * @return list<array<string, mixed>>
     */
    private function mergeFaqs(array $existing, array $shellFaqs): array
    {
        $shellFaqs = array_values(array_filter($shellFaqs, fn ($faq) => is_array($faq) && $this->questionOf($faq) !== ''));
        $out = [];
        $seen = [];

        foreach ($existing as $faq) {
            if (! is_array($faq)) {
                continue;
            }
            $key = $this->normalizeQuestion($this->questionOf($faq));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            if (! $this->isKeptQuestion($key)) {
                $shell = $this->shellFaq($shellFaqs, $key);
                if ($shell) {
                    $faq['question'] = $shell['question'];
                    $faq['answer'] = $shell['answer'];
                }
            }
            $out[] = $faq;
            $seen[$key] = true;
        }

        foreach ($shellFaqs as $faq) {
            $key = $this->normalizeQuestion($this->questionOf($faq));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $out[] = [
                'question' => (string) $faq['question'],
                'answer' => (string) $faq['answer'],
            ];
            $seen[$key] = true;
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $shellFaqs
     * @return array<string, mixed>|null
     */
    private function shellFaq(array $shellFaqs, string $key): ?array
    {
        foreach ($shellFaqs as $faq) {
            if ($this->normalizeQuestion($this->questionOf($faq)) === $key) {
                return $faq;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $faq
     */
    private function questionOf(array $faq): string
    {
        return trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
    }

    private function isKeptQuestion(string $key): bool
    {
        return in_array($key, self::KEPT_QUESTIONS, true);
    }

    private function normalizeQuestion(string $question): string
    {
        $question = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
            ['"', '"', "'", "'"],
            $question
        );
        $question = preg_replace('/\s+/u', ' ', trim($question)) ?? trim($question);

        return mb_strtolower($question);
    }

    private function snapshot(EducationPost $post): string
    {
        return json_encode($post->only(self::FIELDS), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}

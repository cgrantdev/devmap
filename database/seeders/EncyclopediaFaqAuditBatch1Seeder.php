<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Replace the encyclopedia FAQs for TB-500, Ipamorelin, CJC-1295,
 * AOD-9604 and Kisspeptin (22 October 2026, FAQ audit batch 1).
 *
 * Lookup is App\Support\EncyclopediaPostMatch: one product category by
 * LOWER(TRIM(slug)), then that category's education post. A legacy
 * unlinked row is logged and never written. Missing and collision
 * counts are warnings; they do not throw and do not open a transaction.
 *
 * faqs is the only column written. Each slug that actually writes runs
 * in its own transaction. saveQuietly skips the JSON unwrapping hook.
 */
class EncyclopediaFaqAuditBatch1Seeder extends Seeder
{
    public const LABEL = 'FAQ audit batch 1';

    public const TIMESTAMP = '2026_10_22_155000';

    public const SOURCE_PAYLOAD_SHA256 = 'f2f9fc66813c5b1210b64fc031db67f60d1d3ebffb5ff5c0ac5d7ceb966ae6d9';

    public const UPDATED = 'updated';

    public const AT_TARGET = 'skipped (already at target)';

    public const MISMATCH = 'skipped (old mismatch)';

    public const FAILED = 'failed';

    /** @var array<string, string> */
    private const FILES = [
        'tb-500' => 'tb-500.json',
        'ipamorelin' => 'ipamorelin.json',
        'cjc-1295' => 'cjc-1295.json',
        'aod-9604' => 'aod-9604.json',
        'kisspeptin' => 'kisspeptin.json',
    ];

    /** @var array<string, string> */
    public array $report = [];

    /** @var list<string> */
    public array $log = [];

    /** @var list<string> */
    public array $transactions = [];

    public static function directory(): string
    {
        return database_path('seeders/data/encyclopedia-field-updates/2026-10-22d');
    }

    public function run(): void
    {
        $this->log = [];
        $this->transactions = [];
        $this->report = [];
        foreach (array_keys(self::FILES) as $slug) {
            $this->report[$slug] = 'missing';
        }

        foreach (self::FILES as $slug => $file) {
            $this->updateSlug($slug, $this->payload($file));
        }
    }

    /**
     * Seam a test can override to fail one slug's save. The failure
     * rolls back that slug only.
     */
    protected function beforeSave(string $slug): void {}

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateSlug(string $slug, array $payload): void
    {
        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }
        if (is_string($match->warning)) {
            $this->log[] = $match->warning;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->report[$slug] = $match->status === 'ok' ? 'missing' : $match->status;
            $this->emit($slug.': '.$this->report[$slug]);

            return;
        }

        $post = $match->post;
        $stored = is_array($post->faqs) ? $post->faqs : [];
        [$reduced, $map, $dropped] = $this->reduce($stored);
        $newFaqs = $payload['new_faqs'] ?? null;
        $guard = $payload['old_faqs_guard'] ?? null;
        if (! is_array($newFaqs) || ! is_array($guard)) {
            throw new RuntimeException('FAQ payload is missing new_faqs or old_faqs_guard for '.$slug.'.');
        }

        if (self::normalize($reduced) === self::normalize($newFaqs)) {
            $this->report[$slug] = self::AT_TARGET;
            $this->emit($slug.': '.self::AT_TARGET);

            return;
        }

        if (self::normalize($reduced) !== self::normalize($guard)) {
            $this->report[$slug] = self::MISMATCH;
            $this->emit(
                self::LABEL.' '.$slug.': '.self::MISMATCH.' excerpt='.$this->excerpt($reduced),
                true
            );

            return;
        }

        try {
            DB::transaction(function () use ($slug, $post, $stored, $map, $dropped, $payload, $newFaqs): void {
                $this->transactions[] = $slug;
                $result = $this->replace($slug, $stored, $map, $payload, $newFaqs);
                [$check] = $this->reduce($result);
                if (self::normalize($check) !== self::normalize($newFaqs)) {
                    throw new RuntimeException(self::LABEL.' '.$slug.': normalize(reduce(result)) does not equal normalize(new_faqs).');
                }
                $post->faqs = array_values($result);
                $this->beforeSave($slug);
                $post->saveQuietly();
                $this->emit($slug.': dropped empty q/a items='.$dropped);
            });
        } catch (Throwable $e) {
            $this->report[$slug] = self::FAILED;
            $this->emit(
                self::LABEL.' '.$slug.': exception rolled back the slug ('.$e->getMessage().')',
                true
            );

            return;
        }

        $this->report[$slug] = self::UPDATED;
        $this->emit($slug.': '.self::UPDATED);
    }

    /**
     * @param  list<mixed>  $stored
     * @param  list<int>  $map
     * @param  array<string, mixed>  $payload
     * @param  list<mixed>  $newFaqs
     * @return list<mixed>
     */
    private function replace(string $slug, array $stored, array $map, array $payload, array $newFaqs): array
    {
        $indices = $payload['replace_indices'] ?? null;
        if (! is_array($indices)) {
            throw new RuntimeException('FAQ payload is missing replace_indices for '.$slug.'.');
        }

        $result = $stored;
        foreach ($indices as $reducedIndex) {
            if (! is_int($reducedIndex)) {
                throw new RuntimeException('replace index for '.$slug.' is not an integer.');
            }
            if (! array_key_exists($reducedIndex, $map)) {
                throw new RuntimeException('replace index '.$reducedIndex.' is outside the reduced FAQ list for '.$slug.'.');
            }
            $item = $newFaqs[$reducedIndex] ?? null;
            if (! is_array($item) || ! isset($item['question'], $item['answer'])) {
                throw new RuntimeException('new_faqs['.$reducedIndex.'] for '.$slug.' is missing question/answer.');
            }
            $result[$map[$reducedIndex]] = [
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
        }

        return $result;
    }

    /**
     * Same reduction faqPageSchema() uses: trim question/q and answer/a,
     * drop an item whose question or answer is empty, strip_tags the answer.
     *
     * @param  list<mixed>  $stored
     * @return array{0: list<array{question: string, answer: string}>, 1: list<int>, 2: int}
     */
    private function reduce(array $stored): array
    {
        $reduced = [];
        $map = [];
        foreach ($stored as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $question = trim((string) ($item['question'] ?? $item['q'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? $item['a'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $map[] = (int) $index;
            $reduced[] = [
                'question' => $question,
                'answer' => strip_tags($answer),
            ];
        }

        return [$reduced, $map, count($stored) - count($reduced)];
    }

    /**
     * Recursive comparison normalizer from the payload: CRLF/CR to LF,
     * NBSP to space, rtrim each line, then trim. Writes stay verbatim.
     */
    public static function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
            $value = str_replace("\u{00A0}", ' ', $value);
            $lines = array_map(rtrim(...), explode("\n", $value));

            return trim(implode("\n", $lines));
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalize($item);
            }

            return $normalized;
        }

        return $value;
    }

    private function excerpt(mixed $current): string
    {
        $normalized = self::normalize($current);
        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $text = is_string($encoded) ? $encoded : '';

        return mb_substr($text, 0, 200);
    }

    private function emit(string $line, bool $warning = false): void
    {
        $this->log[] = $line;
        if ($warning) {
            Log::warning($line);
            $this->command?->error($line);
            fwrite(STDERR, $line.PHP_EOL);

            return;
        }

        Log::info($line);
        $this->command?->info($line);
        fwrite(STDOUT, $line.PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $path = self::directory().'/'.$file;
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('FAQ audit payload is not an object: '.$file);
        }

        return $decoded;
    }
}

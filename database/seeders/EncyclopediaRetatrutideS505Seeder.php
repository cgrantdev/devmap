<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Retatrutide section 505 follow-up (22 October 2026).
 *
 * One post, one transaction. Comparison uses normalize() (and reduce()
 * for faqs, the same view as EncyclopediaController::faqPageSchema).
 * A guard mismatch skips that column. A post-edit result that does not
 * match the paired target throws, and the transaction rolls every column
 * back. regulatory_important_note only replaces the post-140000 sentence.
 */
class EncyclopediaRetatrutideS505Seeder extends Seeder
{
    public const LABEL = 'Retatrutide s505 follow-up';

    public const SLUG = 'retatrutide';

    public const PAYLOAD_SHA256 = '07bbde75fdb4529ee92b747e0960a643aaa3a26663683973cf1e4411f3769dbc';

    public const UPDATED = 'updated';

    public const ALREADY = 'already current';

    public const MISMATCH = 'skipped (old mismatch)';

    public const PRE140 = 'skipped (pre-140000)';

    public const PRE140_LOG = 'regulatory_important_note: pre-140000 value, M4 sentence absent, skipped';

    /** @var list<string> */
    private const ORDER = [
        'regulatory_subsections',
        'faqs',
        'key_points',
        'conclusion',
        'overview',
        'description',
        'regulatory_important_note',
    ];

    /** @var array<string, string> */
    public array $columnReport = [];

    /** @var list<string> */
    public array $log = [];

    /** @var list<string> */
    public array $transactions = [];

    public string $report = 'missing';

    public function run(): void
    {
        $this->columnReport = [];
        $this->log = [];
        $this->transactions = [];
        $this->report = 'missing';

        $columns = $this->payload()['columns'] ?? null;
        if (! is_array($columns)) {
            throw new RuntimeException('Retatrutide s505 payload is missing columns.');
        }

        $match = EncyclopediaPostMatch::find(self::SLUG, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }
        if (is_string($match->warning)) {
            $this->log[] = $match->warning;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->report = $match->status === 'ok' ? 'missing' : $match->status;
            $this->emit(self::SLUG.': '.$this->report);

            return;
        }

        $post = $match->post;

        $statuses = DB::transaction(function () use ($post, $columns): array {
            $this->transactions[] = self::SLUG;
            $statuses = [];
            $changed = false;

            foreach (self::ORDER as $column) {
                $spec = $columns[$column] ?? null;
                if (! is_array($spec)) {
                    throw new RuntimeException('Retatrutide s505 payload is missing '.$column.'.');
                }
                $status = $this->applyColumn($post, $column, $spec);
                $statuses[$column] = $status;
                if ($status === self::UPDATED) {
                    $changed = true;
                }
            }

            if ($changed) {
                $post->saveQuietly();
            }

            $written = array_keys(array_filter(
                $statuses,
                fn (string $status) => $status === self::UPDATED
            ));
            $skipped = array_keys(array_filter(
                $statuses,
                fn (string $status) => $status !== self::UPDATED
            ));
            $this->emit(
                self::LABEL.' '.self::SLUG.': wrote '.($written === [] ? 'nothing' : implode(', ', $written))
                .'; skipped '.($skipped === [] ? 'nothing' : implode(', ', $skipped))
            );

            return $statuses;
        });

        $this->columnReport = $statuses;
        $this->report = in_array(self::UPDATED, $statuses, true) ? self::UPDATED : self::ALREADY;
        if (! in_array(self::UPDATED, $statuses, true) && in_array(self::MISMATCH, $statuses, true)) {
            $this->report = self::MISMATCH;
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyColumn(EducationPost $post, string $column, array $spec): string
    {
        $current = $post->{$column};

        foreach ($this->targets($spec) as $target) {
            if ($this->same($column, $current, $target)) {
                $this->emit(self::SLUG.'.'.$column.': '.self::ALREADY);

                return self::ALREADY;
            }
        }

        $matched = null;
        foreach ($this->olds($spec) as $index => $old) {
            if ($this->same($column, $current, $old)) {
                $matched = $index;
                break;
            }
        }

        if ($column === 'regulatory_important_note' && $matched === null) {
            foreach ($this->pre140($spec) as $index => $pre) {
                if ($this->same($column, $current, $pre)) {
                    $sentence = (string) ($spec['old_sentence'] ?? '');
                    $applies = (bool) (($spec['pre_140000_fix_applies'][$index] ?? false));
                    $count = $this->sentenceCount(is_string($current) ? $current : '', $sentence);
                    if ($applies && $count === 1) {
                        $result = $this->replaceOnce(is_string($current) ? $current : '', $sentence, (string) $spec['new_sentence']);
                        $expected = $this->targets($spec)[$index] ?? null;
                        if ($result === null || ! $this->same($column, $result, $expected)) {
                            throw new RuntimeException($column.' post-edit assertion failed');
                        }
                        $post->{$column} = $result;
                        $this->emit(self::SLUG.'.'.$column.': '.self::UPDATED);

                        return self::UPDATED;
                    }
                    $this->emit(self::PRE140_LOG);

                    return self::PRE140;
                }
            }
        }

        if ($matched === null) {
            $this->warnMismatch($column, $current);

            return self::MISMATCH;
        }

        $result = $this->transform($column, $spec, $current);
        if ($result === null) {
            $this->warnMismatch($column, $current);

            return self::MISMATCH;
        }

        $expected = $this->targets($spec)[$matched] ?? null;
        if (! $this->same($column, $result, $expected)) {
            throw new RuntimeException($column.' post-edit assertion failed');
        }

        $post->{$column} = $result;
        $this->emit(self::SLUG.'.'.$column.': '.self::UPDATED);

        return self::UPDATED;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function transform(string $column, array $spec, mixed $current): mixed
    {
        return match ($column) {
            'regulatory_subsections' => $this->applyRegulatory($current, $spec),
            'faqs' => $this->applyFaqs($current, $spec),
            'key_points' => $this->applyKeyPoints($current, $spec),
            'conclusion', 'overview', 'description' => $this->applyEdits($current, $spec),
            'regulatory_important_note' => $this->applyNote($current, $spec),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyRegulatory(mixed $current, array $spec): ?array
    {
        if (! is_array($current) || ! array_is_list($current)) {
            return null;
        }
        $item0 = $spec['item0'] ?? null;
        $item2 = $spec['item2'] ?? null;
        $item3 = $spec['item3'] ?? null;
        if (! is_array($item0) || ! is_array($item2) || ! is_array($item3)) {
            return null;
        }
        if (! isset($current[0], $current[2], $current[3]) || ! is_array($current[0]) || ! is_array($current[2]) || ! is_array($current[3])) {
            return null;
        }

        $value = $current[0]['entries'][0]['value'] ?? null;
        $oldSentence = $item0['old_sentence'] ?? null;
        $newSentence = $item0['new_sentence'] ?? null;
        if (! is_string($value) || ! is_string($oldSentence) || ! is_string($newSentence)) {
            return null;
        }
        $replaced = $this->replaceOnce($value, $oldSentence, $newSentence);
        if ($replaced === null) {
            return null;
        }
        $current[0]['entries'][0]['value'] = $replaced;

        $title = $current[2]['title'] ?? null;
        $oldTitle = $item2['old_title'] ?? null;
        $newTitle = $item2['new_title'] ?? null;
        $append = $item2['append_entry'] ?? null;
        if (! is_string($title) || ! is_string($oldTitle) || ! is_string($newTitle) || ! is_array($append)) {
            return null;
        }
        if (self::normalize($title) !== self::normalize($oldTitle)) {
            return null;
        }
        if (! is_array($current[2]['entries'] ?? null)) {
            return null;
        }
        $current[2]['title'] = $newTitle;
        $current[2]['entries'][] = $append;

        $oldItem = $item3['old_item_3'] ?? null;
        $newItem = $item3['new_item_3'] ?? null;
        if (! is_array($oldItem) || ! is_array($newItem)) {
            return null;
        }
        if (self::normalize($current[3]) !== self::normalize($oldItem)) {
            return null;
        }
        $current[3] = $newItem;

        return $current;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<array<string, mixed>>|null
     */
    private function applyFaqs(mixed $current, array $spec): ?array
    {
        if (! is_array($current) || ! array_is_list($current)) {
            return null;
        }
        $replace = $spec['replace'] ?? null;
        if (! is_array($replace)) {
            return null;
        }

        $reducedIndex = 0;
        $applied = [];
        foreach ($current as $index => $item) {
            if (! $this->faqContributes($item)) {
                continue;
            }
            $key = (string) $reducedIndex;
            if (isset($replace[$key]) && is_array($replace[$key])) {
                $old = $replace[$key]['old'] ?? null;
                $new = $replace[$key]['new'] ?? null;
                $reduced = $this->reduceOne($item);
                if (! is_array($old) || ! is_array($new) || ! is_string($new['question'] ?? null) || ! is_string($new['answer'] ?? null)) {
                    return null;
                }
                if (self::normalize($reduced) !== self::normalize($old)) {
                    return null;
                }
                $current[$index] = [
                    'question' => $new['question'],
                    'answer' => $new['answer'],
                ];
                $applied[$key] = true;
            }
            $reducedIndex++;
        }

        foreach (array_keys($replace) as $key) {
            if (! isset($applied[(string) $key])) {
                return null;
            }
        }

        return array_values($current);
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>|null
     */
    private function applyKeyPoints(mixed $current, array $spec): ?array
    {
        if (! is_array($current) || ! array_is_list($current)) {
            return null;
        }
        $pairs = [
            7 => ['old_7', 'new_7'],
            9 => ['old_9', 'new_9'],
            10 => ['old_10', 'new_10'],
        ];
        foreach ($pairs as $index => [$oldKey, $newKey]) {
            $old = $spec[$oldKey] ?? null;
            $new = $spec[$newKey] ?? null;
            if (! is_string($old) || ! is_string($new) || ! array_key_exists($index, $current)) {
                return null;
            }
            if (self::normalize($current[$index]) !== self::normalize($old)) {
                return null;
            }
            $current[$index] = $new;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyEdits(mixed $current, array $spec): ?string
    {
        if (! is_string($current) || ! is_array($spec['edits'] ?? null)) {
            return null;
        }
        $work = $current;
        foreach ($spec['edits'] as $edit) {
            if (! is_array($edit) || ! is_string($edit['old'] ?? null) || ! is_string($edit['new'] ?? null)) {
                return null;
            }
            $next = $this->replaceOnce($work, $edit['old'], $edit['new']);
            if ($next === null) {
                return null;
            }
            $work = $next;
        }

        return $work;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyNote(mixed $current, array $spec): ?string
    {
        if (! is_string($current) || ! is_string($spec['old_sentence'] ?? null) || ! is_string($spec['new_sentence'] ?? null)) {
            return null;
        }

        return $this->replaceOnce($current, $spec['old_sentence'], $spec['new_sentence']);
    }

    private function replaceOnce(string $stored, string $old, string $new): ?string
    {
        if ($old === '') {
            return null;
        }
        if (substr_count($stored, $old) === 1) {
            return str_replace($old, $new, $stored);
        }
        $normalized = self::normalize($stored);
        if (is_string($normalized) && substr_count($normalized, $old) === 1) {
            return str_replace($old, $new, $normalized);
        }

        return null;
    }

    private function sentenceCount(string $stored, string $sentence): int
    {
        if ($sentence === '') {
            return 0;
        }
        $count = substr_count($stored, $sentence);
        if ($count > 0) {
            return $count;
        }
        $normalized = self::normalize($stored);

        return is_string($normalized) ? substr_count($normalized, $sentence) : 0;
    }

    private function same(string $column, mixed $left, mixed $right): bool
    {
        if ($column === 'faqs') {
            return self::normalize(self::reduceFaqs($left)) === self::normalize(self::reduceFaqs($right));
        }

        return self::normalize($left) === self::normalize($right);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function reduceFaqs(mixed $faqs): array
    {
        if (! is_array($faqs)) {
            return [];
        }
        $reduced = [];
        foreach ($faqs as $faq) {
            $one = self::reduceOne($faq);
            if ($one !== null) {
                $reduced[] = $one;
            }
        }

        return $reduced;
    }

    /**
     * @return array{question: string, answer: string}|null
     */
    private static function reduceOne(mixed $faq): ?array
    {
        if (! is_array($faq)) {
            return null;
        }
        $question = trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
        $answer = trim((string) ($faq['answer'] ?? $faq['a'] ?? ''));
        if ($question === '' || $answer === '') {
            return null;
        }

        return [
            'question' => $question,
            'answer' => strip_tags($answer),
        ];
    }

    private function faqContributes(mixed $faq): bool
    {
        return self::reduceOne($faq) !== null;
    }

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

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function targets(array $spec): array
    {
        return is_array($spec['target_values'] ?? null) ? array_values($spec['target_values']) : [];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function olds(array $spec): array
    {
        return is_array($spec['old_values'] ?? null) ? array_values($spec['old_values']) : [];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function pre140(array $spec): array
    {
        return is_array($spec['pre_140000_values'] ?? null) ? array_values($spec['pre_140000_values']) : [];
    }

    private function warnMismatch(string $column, mixed $current): void
    {
        $this->emit(
            self::LABEL.' '.self::SLUG.'.'.$column.': '.self::MISMATCH.' excerpt='.$this->excerpt($current),
            true
        );
    }

    private function excerpt(mixed $current): string
    {
        $normalized = $current === null ? '' : self::normalize(
            is_string($current) ? $current : $current
        );
        if (is_string($normalized)) {
            $text = $normalized;
        } else {
            $encoded = json_encode(self::normalize($current), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $text = is_string($encoded) ? $encoded : '';
        }

        return mb_substr(str_replace(["\r\n", "\r", "\n"], ' ', $text), 0, 200);
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
    private function payload(): array
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-22e/retatrutide.json');
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Retatrutide s505 payload is not an object.');
        }

        return $decoded;
    }
}

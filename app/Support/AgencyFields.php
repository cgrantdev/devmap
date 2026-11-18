<?php

namespace App\Support;

use App\Models\PageContent;

/**
 * Validation, cleaning and saving for the agency's page copy fields (H1,
 * intro, body, FAQs, and for coupon pages title and meta), shared by the
 * vendor, coupon, calculator and blog editors.
 */
class AgencyFields
{
    public const MAX_FAQS = 12;

    /** @return array<string, list<string>> */
    public static function rules(bool $withSnippet = false): array
    {
        $rules = [
            'h1' => ['nullable', 'string', 'max:255'],
            'intro_html' => ['nullable', 'string', 'max:5000'],
            'body_html' => ['nullable', 'string', 'max:200000'],
        ] + self::faqRules();

        if ($withSnippet) {
            $rules['title'] = ['nullable', 'string', 'max:255'];
            $rules['meta_description'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /** @return array<string, list<string>> */
    public static function faqRules(): array
    {
        return [
            'faqs' => ['nullable', 'array', 'max:'.self::MAX_FAQS],
            'faqs.*.question' => ['nullable', 'string', 'max:300', 'required_with:faqs.*.answer'],
            'faqs.*.answer' => ['nullable', 'string', 'max:4000', 'required_with:faqs.*.question'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'faqs.max' => 'Up to '.self::MAX_FAQS.' FAQs per page.',
            'faqs.*.question.required_with' => 'FAQ :position has an answer but no question.',
            'faqs.*.answer.required_with' => 'FAQ :position has a question but no answer.',
            'faqs.*.question.max' => 'FAQ :position: the question is over 300 characters.',
            'faqs.*.answer.max' => 'FAQ :position: the answer is over 4,000 characters.',
        ];
    }

    /** Trimmed text, or null when blank. */
    public static function text(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    /**
     * [{question, answer}] with both parts trimmed. Rows missing either
     * part are dropped. Accepts the compound editor's q/a keys too.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function faqs(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $question = self::text($row['question'] ?? $row['q'] ?? null);
            $answer = self::text($row['answer'] ?? $row['a'] ?? null);
            if ($question === null || $answer === null) {
                continue;
            }
            $out[] = ['question' => $question, 'answer' => $answer];
            if (count($out) >= self::MAX_FAQS) {
                break;
            }
        }

        return $out;
    }

    /**
     * Save one page's copy from validated input. Blank fields fall back
     * to the page's defaults; a page left with nothing loses its row.
     */
    public static function save(string $type, string $key, array $data, bool $withSnippet = false): void
    {
        $values = [
            'h1' => self::text($data['h1'] ?? null),
            'intro_html' => SafeHtml::clean($data['intro_html'] ?? null),
            'body_html' => SafeHtml::clean($data['body_html'] ?? null),
            'faqs' => self::faqs($data['faqs'] ?? []) ?: null,
        ];
        if ($withSnippet) {
            $values['title'] = self::text($data['title'] ?? null);
            $values['meta_description'] = self::text($data['meta_description'] ?? null);
        }

        $row = PageContent::query()->firstOrNew(['page_type' => $type, 'page_key' => $key]);
        $row->fill($values);

        $hasCopy = collect($row->only(['title', 'meta_description', 'h1', 'intro_html', 'body_html', 'faqs']))
            ->contains(fn ($v) => $v !== null && $v !== []);
        if (! $hasCopy) {
            if ($row->exists) {
                $row->delete();
            }

            return;
        }

        $row->save();
    }
}

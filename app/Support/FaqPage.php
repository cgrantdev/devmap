<?php

namespace App\Support;

/**
 * One FAQPage JSON-LD node built from the same {question, answer} list
 * the page renders. Callers pass that list in; this class does not
 * load or rewrite stored FAQ copy.
 */
class FaqPage
{
    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>|null
     */
    public static function schema(array $faqs, ?string $id = null): ?array
    {
        $mainEntity = [];
        foreach ($faqs as $faq) {
            $question = (string) ($faq['question'] ?? '');
            $answer = (string) ($faq['answer'] ?? '');
            if ($question === '' || $answer === '') {
                continue;
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }
        if ($mainEntity === []) {
            return null;
        }

        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
        ];
        if (is_string($id) && $id !== '') {
            $node['@id'] = $id;
        }
        $node['mainEntity'] = $mainEntity;

        return $node;
    }

    /**
     * Visible FAQ is allowlisted. Encyclopedia matching is case-insensitive.
     * Blog matching is a lowercase slug compare against the catalog list.
     */
    public static function renderAllowed(string $section, string $slug): bool
    {
        $allow = config('peptidemap.faq_render_allowlist.'.$section, []);
        if (! is_array($allow)) {
            return false;
        }
        $needle = self::normaliseSlug($section, $slug);
        if ($needle === '') {
            return false;
        }
        foreach ($allow as $item) {
            if (self::normaliseSlug($section, (string) $item) === $needle) {
                return true;
            }
        }

        return false;
    }

    private static function normaliseSlug(string $section, string $slug): string
    {
        if ($section === 'encyclopedia') {
            return EncyclopediaSlug::normalize($slug);
        }

        return strtolower(trim($slug));
    }
}

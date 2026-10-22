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
}

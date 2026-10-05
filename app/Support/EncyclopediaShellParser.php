<?php

namespace App\Support;

/**
 * Turns a CMS-paste encyclopedia draft into education_posts attributes.
 *
 * Prefers the cleaned cms-paste body when that is the file on disk.
 * Identity locks are applied after parse and win over a conflicting draft.
 */
class EncyclopediaShellParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $slug, string $bodyMarkdown, string $metaMarkdown): array
    {
        $fields = $this->fields($bodyMarkdown);
        $meta = $this->meta($metaMarkdown);

        $overviewShort = $this->plain($fields['overviewShort'] ?? '');
        $overview = $this->plain($fields['overview'] ?? '');
        if ($overviewShort !== '' && ($overview === '' || !str_starts_with($overview, mb_substr($overviewShort, 0, 40)))) {
            $overview = trim($overviewShort."\n\n".$overview);
        }

        $molecular = $this->molecular($fields['molecularInfo'] ?? '');
        $sequence = $this->sequence($fields['molecularInfo'] ?? '');

        $attributes = [
            'title' => $meta['h1'] !== '' ? $meta['h1'] : $slug,
            'seo_h1' => $meta['h1'] !== '' ? $meta['h1'] : null,
            'peptide_full_name' => $meta['subtitle'] !== '' ? $meta['subtitle'] : null,
            'description' => $overviewShort !== '' ? $overviewShort : null,
            'overview' => $overview !== '' ? $overview : null,
            'background' => $this->plainOrNull($fields['background'] ?? ''),
            'mechanism_of_action_intro' => $this->plainOrNull($fields['mechanismOfActionIntro'] ?? ''),
            'mechanism_subsections' => $this->mechanismSubsections($fields['mechanismSubsections'] ?? ''),
            'preclinical_intro' => $this->plainOrNull($fields['preclinicalIntro'] ?? ''),
            'preclinical_subsections' => $this->preclinicalSubsections($fields['preclinicalSubsections'] ?? ''),
            'preclinical_disclaimer' => $this->plainOrNull($fields['preclinicalDisclaimer'] ?? ''),
            'human_use_intro' => $this->plainOrNull($fields['humanUseIntro'] ?? ''),
            'human_use_subsections' => $this->entrySubsections($fields['humanUseSubsections'] ?? ''),
            'regulatory_subsections' => $this->entrySubsections($fields['regulatorySubsections'] ?? ''),
            'regulatory_important_note' => $this->plainOrNull($fields['regulatoryImportantNote'] ?? ''),
            'potential_applications_intro' => $this->plainOrNull($fields['potentialApplicationsIntro'] ?? ''),
            'potential_applications' => $this->titledItems($fields['potentialApplications'] ?? '', 'title'),
            'potential_applications_important_context' => $this->plainOrNull($fields['potentialApplicationsImportantContext'] ?? ''),
            'areas_of_research_intro' => $this->plainOrNull($fields['areasOfResearchIntro'] ?? ''),
            'areas_of_research' => $this->titledItems($fields['areasOfResearch'] ?? '', 'name'),
            'key_points' => $this->bullets($fields['keyPoints'] ?? ''),
            'conclusion' => $this->plainOrNull($fields['conclusion'] ?? ''),
            'references' => $this->references($fields['references'] ?? ''),
            'faqs' => $this->faqs($fields['FAQs'] ?? '') ?: $this->metaFaqs($metaMarkdown),
            'molecular_formula' => $molecular['formula'],
            'molecular_weight' => $molecular['weight'],
            'cas_registry_number' => $molecular['cas'],
            'amino_acid_sequence' => $sequence,
            'half_life' => $this->halfLife($fields['halfLife'] ?? ''),
            'seo_page_title' => $meta['title'] !== '' ? $meta['title'] : null,
            'seo_description' => $meta['description'] !== '' ? $meta['description'] : null,
            'seo_og_title' => $meta['og_title'] !== '' ? $meta['og_title'] : ($meta['title'] !== '' ? $meta['title'] : null),
            'seo_og_description' => $meta['og_description'] !== '' ? $meta['og_description'] : ($meta['description'] !== '' ? $meta['description'] : null),
            'tags' => $meta['keywords'],
            'research_title' => $meta['title'] !== '' ? $meta['title'] : null,
            'research_outline' => $meta['description'] !== '' ? $meta['description'] : null,
        ];

        $attributes['how_it_works'] = $attributes['mechanism_of_action_intro'];
        $attributes['key_effects'] = $attributes['key_points'];

        return $this->enforceLocks($slug, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function enforceLocks(string $slug, array $attributes): array
    {
        $blob = json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: '';
        if (preg_match('/picsum|unsplash|placeholder\.com|loremflickr/i', $blob)) {
            throw new \RuntimeException("{$slug} draft contains a stock CDN image reference.");
        }

        if ($slug === 'kpv') {
            if (str_contains($blob, '67724-34-9')) {
                throw new \RuntimeException('KPV draft still contains forbidden CAS 67724-34-9.');
            }
            $attributes['cas_registry_number'] = '67727-97-3';
            if (!$attributes['peptide_full_name']) {
                $attributes['peptide_full_name'] = 'Lys-Pro-Val (α-MSH C-terminal tripeptide)';
            }
        }

        if ($slug === 'epitalon') {
            $attributes['molecular_formula'] = 'C14H22N4O9';
            $attributes['molecular_weight'] = '390.35 g/mol';
            $attributes['cas_registry_number'] = '307297-39-8';
            $attributes['amino_acid_sequence'] = 'Ala-Glu-Asp-Gly';
            $attributes['peptide_full_name'] = 'Epithalon (Ala-Glu-Asp-Gly); PubChem CID 219042';
            if (!str_contains((string) $attributes['overview'], '219042')) {
                $attributes['overview'] = trim((string) $attributes['overview']."\n\nPubChem CID 219042. Formula C14H22N4O9. Molecular weight 390.35. CAS 307297-39-8.");
            }
        }

        if ($slug === 'glutathione') {
            foreach (['molecular_weight', 'overview', 'description', 'background', 'conclusion'] as $field) {
                if (is_string($attributes[$field] ?? null)) {
                    $attributes[$field] = str_replace('307.32', '307.33', $attributes[$field]);
                }
            }
            $attributes['molecular_weight'] = '307.33 g/mol';
            if (!str_contains((string) $attributes['overview'], '307.33')) {
                $attributes['overview'] = trim((string) $attributes['overview']."\n\nMolecular weight 307.33 g/mol (CAS 70-18-8).");
            }
        }

        if ($slug === 'BPC-157-TB-500') {
            $haystack = (string) $attributes['overview']."\n".(string) $attributes['background'];
            if (!str_contains($haystack, 'LKKTETQ')) {
                throw new \RuntimeException('BPC-157 / TB-500 draft does not identify TB-500 as Ac-LKKTETQ.');
            }
            if (!preg_match('/not (?:the )?full(?:-length)?|≠ full|not full-length/i', $haystack)) {
                throw new \RuntimeException('BPC-157 / TB-500 draft does not distinguish TB-500 from full-length thymosin β4.');
            }
            $attributes['peptide_full_name'] = 'BPC-157 and TB-500 (Ac-LKKTETQ fragment, not full-length thymosin β4)';
        }

        if ($slug === 'klow-blend-ghk-cu-bpc-157-tb-500-kpv') {
            $attributes['peptide_full_name'] = 'GHK-Cu, BPC-157, TB-500 (Ac-LKKTETQ), and KPV';
            $overview = (string) $attributes['overview'];
            if (!preg_match('/not (?:extra|more) GHK/i', $overview)) {
                $attributes['overview'] = trim($overview."\n\nKLOW adds KPV as a fourth component on top of GLOW (GHK-Cu, BPC-157, and TB-500). It is not extra GHK.");
            }
        }

        if ($slug === 'glow') {
            $attributes['peptide_full_name'] = 'GHK-Cu, BPC-157, and TB-500 (Ac-LKKTETQ)';
            $overview = (string) $attributes['overview'];
            if (!preg_match('/not (?:extra|more) GHK/i', $overview)) {
                $attributes['overview'] = trim($overview."\n\nKLOW adds KPV as a fourth component on top of this trio. It is not extra GHK.");
            }
        }

        if ($slug === 'Melanotan-II') {
            $note = (string) $attributes['regulatory_important_note'];
            if (!preg_match('/not final 503A Bulks List permission to compound/i', $note)) {
                $note = trim($note.' A scheduled PCAC discussion is not permission to compound and is not a 503A Bulks List status.');
                $attributes['regulatory_important_note'] = $note;
            }
        }

        if (is_string($attributes['cas_registry_number'] ?? null) && trim($attributes['cas_registry_number']) === '') {
            $attributes['cas_registry_number'] = null;
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    private function fields(string $markdown): array
    {
        $markdown = preg_replace('/\A---\s*\n.*?\n---\s*\n/s', '', $markdown) ?? $markdown;
        $markdown = str_replace(["## FAQs\n", "## FAQs\r\n"], "## Field: FAQs\n", $markdown);

        $parts = preg_split(
            '/^(?:## Field:\s*|###\s+)([A-Za-z][A-Za-z0-9]*)\s*$/m',
            $markdown,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if (!is_array($parts)) {
            return [];
        }

        $fields = [];
        for ($i = 1; $i < count($parts); $i += 2) {
            $name = $parts[$i];
            $body = $parts[$i + 1] ?? '';
            $body = preg_split('/^## (?:Editor notes|Cite this page|Related compares|Related)\b/m', $body)[0] ?? $body;
            $fields[$name] = trim($body);
        }

        return $fields;
    }

    /**
     * @return array{title: string, description: string, h1: string, og_title: string, og_description: string, subtitle: string, keywords: array<int, string>}
     */
    private function meta(string $markdown): array
    {
        $title = $this->recommendedIn($markdown, '2. SEO title');
        $description = $this->recommendedIn($markdown, '3. Meta description');
        $h1 = '';
        $subtitle = '';
        if (preg_match('/## 4\. H1\s*(.+?)(?=\n## |\z)/s', $markdown, $section)) {
            $firstPara = preg_split("/\n\s*\n/", trim($section[1]))[0] ?? $section[1];
            if (preg_match_all('/`([^`]+)`/', $firstPara, $ticks)) {
                $candidates = array_values(array_filter(
                    $ticks[1],
                    fn ($tick) => !in_array(strtolower(trim($tick)), ['name', 'h1'], true) && trim($tick) !== ''
                ));
                if ($candidates !== []) {
                    $h1 = trim((string) end($candidates));
                }
            }
            if (preg_match('/subtitle\s+([^`\n:]+)/i', $firstPara, $sub)) {
                $candidate = trim($sub[1], " \t:*");
                if ($candidate !== '' && !preg_match('/\b(should|must|ensure|include|states|list)\b/i', $candidate)) {
                    $subtitle = $candidate;
                }
            }
        }

        $ogTitle = '';
        if (preg_match('/\*\*OG\/Twitter title:\*\*\s*`([^`]+)`/', $markdown, $og)) {
            $ogTitle = trim($og[1]);
        }
        $ogDescription = '';
        if (preg_match('/\*\*OG\/Twitter description:\*\*\s*`([^`]+)`/', $markdown, $og)) {
            $ogDescription = trim($og[1]);
        }

        $keywords = [];
        if (preg_match('/## 6\. Keywords\s*(.+?)(?=\n## |\z)/s', $markdown, $kw)) {
            $primary = preg_split('/\*\*Secondary:\*\*/', $kw[1])[0] ?? $kw[1];
            if (preg_match_all('/^-\s+(.+)$/m', $primary, $items)) {
                foreach ($items[1] as $item) {
                    $item = trim($this->plain($item));
                    if ($item !== '') {
                        $keywords[] = $item;
                    }
                }
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $h1,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'subtitle' => $subtitle,
            'keywords' => $keywords,
        ];
    }

    private function recommendedIn(string $markdown, string $heading): string
    {
        if (!preg_match('/## '.preg_quote($heading, '/').'\s*(.+?)(?=\n## |\z)/s', $markdown, $section)) {
            return '';
        }
        if (preg_match('/\*\*Recommended:\*\*\s*`([^`]+)`/', $section[1], $rec)) {
            return trim($rec[1]);
        }

        return '';
    }

    /**
     * @return array{formula: ?string, weight: ?string, cas: ?string}
     */
    private function molecular(string $body): array
    {
        $grab = function (string $key) use ($body): ?string {
            $quoted = [
                '/\*\*'.preg_quote($key, '/').':\*\*\s*"([^"]*)"/',
                '/'.preg_quote($key, '/').':\s*"([^"]*)"/',
            ];
            foreach ($quoted as $pattern) {
                if (preg_match($pattern, $body, $m)) {
                    $value = trim($m[1]);
                    if ($value === '' || strtolower($value) === 'empty') {
                        return null;
                    }

                    return $this->plain($value);
                }
            }
            if (!preg_match('/'.preg_quote($key, '/').':\s*([^\n(]+)/', $body, $m)) {
                return null;
            }
            $value = trim($this->plain($m[1]), " \t\"'*");
            if ($value === '' || strtolower($value) === 'empty') {
                return null;
            }

            return $value;
        };

        return [
            'formula' => $grab('formula'),
            'weight' => $grab('molecularWeight'),
            'cas' => $grab('casNumber'),
        ];
    }

    private function sequence(string $body): ?string
    {
        if (!preg_match('/Sequence:\s*([A-Za-z]{2,4}(?:-[A-Za-z]{2,4})+)/', $body, $m)) {
            return null;
        }

        return $m[1];
    }

    private function halfLife(string $body): ?string
    {
        $text = $this->plain($body);
        if ($text === '' || mb_strlen($text) > 255) {
            return null;
        }

        return $text;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mechanismSubsections(string $body): array
    {
        $out = [];
        foreach ($this->chunks($body) as $chunk) {
            $items = [];
            foreach ($this->lines($chunk['body']) as $line) {
                if (preg_match('/^- item:\s*(.+?)\s+[—–-]\s+description:\s*(.+)$/u', $line, $m)) {
                    $items[] = [
                        'item' => $this->plain($m[1]),
                        'description' => $this->plain($m[2]),
                    ];
                }
            }
            if ($chunk['title'] === '' && $items === []) {
                continue;
            }
            $out[] = [
                'title' => $chunk['title'],
                'intro' => $this->labeled($chunk['body'], 'intro'),
                'items' => $items,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function preclinicalSubsections(string $body): array
    {
        $out = [];
        foreach ($this->chunks($body) as $chunk) {
            $findings = [];
            foreach ($this->lines($chunk['body']) as $line) {
                if (preg_match('/^- title:\s*(.+?)\s+[—–-]\s+content:\s*(.+)$/u', $line, $m)) {
                    $findings[] = [
                        'title' => $this->plain($m[1]),
                        'content' => $this->plain($m[2]),
                    ];
                }
            }
            if ($chunk['title'] === '' && $findings === []) {
                continue;
            }
            $out[] = [
                'title' => $chunk['title'],
                'findings' => $findings,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entrySubsections(string $body): array
    {
        $out = [];
        foreach ($this->chunks($body) as $chunk) {
            $entries = [];
            foreach ($this->lines($chunk['body']) as $line) {
                if (preg_match('/^- type:\s*(\w+)\s+[—–-]\s+value:\s*(.+)$/u', $line, $m)) {
                    $entries[] = [
                        'type' => strtolower($m[1]) === 'item' ? 'item' : 'content',
                        'value' => $this->plain($m[2]),
                    ];
                }
            }
            if ($chunk['title'] === '' && $entries === []) {
                continue;
            }
            $out[] = [
                'title' => $chunk['title'],
                'entries' => $entries,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function titledItems(string $body, string $key): array
    {
        $label = $key === 'name' ? 'name' : 'title';
        $valueKey = $key === 'name' ? 'description' : 'description';
        $out = [];
        foreach ($this->lines($body) as $line) {
            if (preg_match('/^- '.$label.':\s*(.+?)\s+[—–-]\s+description:\s*(.+)$/u', $line, $m)) {
                $row = [
                    $key => $this->plain($m[1]),
                    $valueKey => $this->plain($m[2]),
                ];
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function bullets(string $body): array
    {
        $out = [];
        foreach ($this->lines($body) as $line) {
            if (preg_match('/^-\s+(.+)$/', $line, $m)) {
                $text = $this->plain($m[1]);
                if ($text !== '') {
                    $out[] = $text;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function references(string $body): array
    {
        $out = [];
        foreach ($this->lines($body) as $line) {
            if (!preg_match('/^\d+\.\s+title:\s*(.+)$/u', $line, $m)) {
                continue;
            }
            $rest = $m[1];
            $parts = preg_split('/\s+[—–]\s+/u', $rest) ?: [];
            $title = $this->plain(array_shift($parts) ?? '');
            $authors = '';
            $citation = '';
            $description = '';
            $links = [];
            foreach ($parts as $part) {
                if (preg_match('/^authors:\s*(.+)$/iu', $part, $hit)) {
                    $authors = $this->plain($hit[1]);
                } elseif (preg_match('/^citation:\s*(.+)$/iu', $part, $hit)) {
                    $citation = $this->plain($hit[1]);
                } elseif (preg_match('/^description:\s*(.+)$/iu', $part, $hit)) {
                    $description = $this->plain($hit[1]);
                } elseif (preg_match('/^links:\s*(.+)$/iu', $part, $hit)) {
                    if (preg_match_all('/https?:\/\/[^\s\]]+/', $hit[1], $urls)) {
                        foreach ($urls[0] as $url) {
                            $links[] = ['url' => rtrim($url, '.),'), 'label' => 'Source'];
                        }
                    }
                }
            }
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'authors' => $authors,
                'citation' => $citation,
                'description' => $description,
                'links' => $links,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function metaFaqs(string $markdown): array
    {
        if (!preg_match('/"@type"\s*:\s*"FAQPage"(.*?)```/s', $markdown, $block)) {
            return [];
        }
        if (!preg_match_all(
            '/"name"\s*:\s*"((?:\\\\.|[^"\\\\])*)"\s*,\s*"acceptedAnswer"\s*:\s*\{.*?"text"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/s',
            $block[1],
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }
        $out = [];
        foreach ($matches as $match) {
            $question = trim(stripcslashes($match[1]));
            $answer = trim(stripcslashes($match[2]));
            if ($question !== '' && $answer !== '') {
                $out[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function faqs(string $body): array
    {
        $out = [];
        foreach ($this->lines($body) as $line) {
            if (preg_match('/^\*\*(.+?)\*\*\s*(.+)$/', $line, $m)) {
                $question = $this->plain($m[1]);
                $answer = $this->plain($m[2]);
                if ($question !== '' && $answer !== '') {
                    $out[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{title: string, body: string}>
     */
    private function chunks(string $body): array
    {
        $parts = preg_split('/^###\s+subsection\s+\d+[^\n]*?\btitle:\s*/mu', $body) ?: [];
        $out = [];
        foreach ($parts as $index => $part) {
            if ($index === 0 && !preg_match('/^###\s+subsection/m', $body)) {
                if (trim($part) === '') {
                    continue;
                }
            }
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $lines = preg_split('/\r\n|\r|\n/', $part) ?: [];
            $title = $this->plain(array_shift($lines) ?? '');
            $out[] = ['title' => $title, 'body' => implode("\n", $lines)];
        }

        return $out;
    }

    private function labeled(string $body, string $label): string
    {
        if (preg_match('/\*\*'.$label.':\*\*\s*(.+)/i', $body, $m)) {
            $text = preg_split('/\*\*[^*]+:\*\*/', $m[1])[0] ?? $m[1];

            return $this->plain($text);
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $body): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $merged = [];
        foreach ($lines as $line) {
            $trim = rtrim($line);
            if ($trim === '') {
                continue;
            }
            if ($merged !== [] && !preg_match('/^(?:- |\*\*|###|##|\d+\.)/', ltrim($trim))) {
                $merged[count($merged) - 1] .= ' '.trim($trim);
                continue;
            }
            $merged[] = trim($trim);
        }

        return $merged;
    }

    private function plainOrNull(string $text): ?string
    {
        $text = $this->plain($text);

        return $text === '' ? null : $text;
    }

    private function plain(string $text): string
    {
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1 ($2)', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '$1', $text) ?? $text;
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}


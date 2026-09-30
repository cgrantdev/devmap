<?php

namespace App\Support;

/**
 * Resolved encyclopedia framing for one compound. Built from a profile
 * class (SLU-PP-332 and the earlier non-peptide classes) or from a
 * catalog definition.
 */
final class FramingProfile
{
    /**
     * @param  array<string, mixed>  $def
     */
    private function __construct(private array $def)
    {
    }

    public static function fromClass(string $class): self
    {
        $article = $class::articleAttributes();

        return new self([
            'class' => $class,
            'seoTitle' => $class::seoTitle(),
            'seoDescription' => $class::seoDescription(),
            'h1' => $class::h1(),
            'subtitle' => $class::subtitle(),
            'tag' => $class::educationTag(),
            'eyebrow' => $class::ogEyebrow(),
            'card' => $class::cardDescription(),
            'institution' => $class::researchInstitution(),
            'researchUrl' => $class::researchUrl(),
            'preserves' => (bool) $class::preservesExistingArticle(),
            'phrases' => method_exists($class, 'phraseReplacements') ? $class::phraseReplacements() : [],
            'rejectsPeptide' => true,
            'markers' => [],
            'tags' => $article['tags'] ?? [],
            'article' => $article,
        ]);
    }

    /**
     * @param  array<string, mixed>  $def
     */
    public static function fromDefinition(array $def): self
    {
        $def['article'] = self::buildArticle($def);

        return new self($def);
    }

    public function seoTitle(): string
    {
        return (string) $this->def['seoTitle'];
    }

    public function seoDescription(): string
    {
        return (string) $this->def['seoDescription'];
    }

    public function h1(): string
    {
        return (string) $this->def['h1'];
    }

    public function subtitle(): string
    {
        return (string) $this->def['subtitle'];
    }

    public function educationTag(): string
    {
        return (string) $this->def['tag'];
    }

    public function cardDescription(): string
    {
        return (string) ($this->def['card'] ?? $this->def['seoDescription']);
    }

    public function ogEyebrow(): string
    {
        return (string) $this->def['eyebrow'];
    }

    public function researchInstitution(): ?string
    {
        $value = $this->def['institution'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function researchUrl(): ?string
    {
        $value = $this->def['researchUrl'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function preservesExistingArticle(): bool
    {
        return (bool) ($this->def['preserves'] ?? false);
    }

    public function rejectsPeptideIdentity(): bool
    {
        return (bool) ($this->def['rejectsPeptide'] ?? false);
    }

    /**
     * @return array<string, string>
     */
    public function phraseReplacements(): array
    {
        return $this->def['phrases'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function requiredMarkers(): array
    {
        return $this->def['markers'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return $this->def['tags'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function articleAttributes(): array
    {
        return $this->def['article'];
    }

    /**
     * @return array<string, mixed>
     */
    public function stubNarrative(): array
    {
        if (isset($this->def['class'])) {
            return ($this->def['class'])::stubNarrative();
        }

        return self::narrativeFromArticle($this->articleAttributes());
    }

    /**
     * @param  array<string, mixed>  $article
     * @return array<string, mixed>
     */
    public static function narrativeFromArticle(array $article): array
    {
        return [
            'subtitle' => $article['peptide_full_name'],
            'tags' => $article['tags'],
            'overview' => $article['overview'],
            'keyPoints' => $article['key_points'],
            'areasOfResearch' => $article['areas_of_research'],
            'areasOfResearchIntro' => $article['areas_of_research_intro'],
            'background' => $article['background'],
            'mechanismOfActionIntro' => $article['mechanism_of_action_intro'],
            'mechanismSubsections' => $article['mechanism_subsections'],
            'preclinicalIntro' => $article['preclinical_intro'],
            'preclinicalSubsections' => $article['preclinical_subsections'],
            'preclinicalDisclaimer' => $article['preclinical_disclaimer'],
            'humanUseIntro' => $article['human_use_intro'],
            'humanUseSubsections' => $article['human_use_subsections'],
            'regulatorySubsections' => $article['regulatory_subsections'],
            'regulatoryImportantNote' => $article['regulatory_important_note'],
            'potentialApplicationsIntro' => $article['potential_applications_intro'],
            'potentialApplications' => $article['potential_applications'],
            'potentialApplicationsImportantContext' => $article['potential_applications_important_context'],
            'conclusion' => $article['conclusion'],
            'references' => $article['references'],
            'molecularInfo' => [
                'formula' => $article['molecular_formula'],
                'molecularWeight' => $article['molecular_weight'],
                'casNumber' => $article['cas_registry_number'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    public static function buildArticle(array $d): array
    {
        return [
            'title' => $d['name'],
            'peptide_full_name' => $d['subtitle'],
            'research_title' => $d['seoTitle'],
            'research_outline' => $d['seoDescription'],
            'research_url' => $d['researchUrl'] ?? null,
            'education_tag' => $d['tag'],
            'tags' => $d['tags'],
            'description' => $d['seoDescription'],
            'overview' => $d['overview'],
            'molecular_formula' => $d['formula'] ?? '',
            'molecular_weight' => $d['mw'] ?? '',
            'cas_registry_number' => $d['cas'] ?? '',
            'half_life' => 'Not stated on this page.',
            'bioavailability' => 'Not stated on this page.',
            'storage' => 'Follow the supplier label or certificate of analysis.',
            'background' => $d['background'],
            'mechanism_of_action_intro' => $d['mechanismIntro'],
            'mechanism_subsections' => [[
                'title' => 'Chemical class',
                'intro' => $d['mechanismIntro'],
                'items' => $d['items'],
            ]],
            'preclinical_intro' => 'The notes below keep the identity of the substance clear. They are not a protocol.',
            'preclinical_subsections' => [[
                'title' => 'Scope of this entry',
                'findings' => $d['findings'],
            ]],
            'preclinical_disclaimer' => 'This page does not provide dosing, prices, or preparation instructions.',
            'human_use_intro' => 'This entry is informational.',
            'human_use_subsections' => [[
                'title' => 'What this page covers',
                'entries' => [[
                    'type' => 'content',
                    'value' => $d['human'],
                ]],
            ]],
            'regulatory_subsections' => [[
                'title' => 'Identity and status',
                'entries' => [[
                    'type' => 'content',
                    'value' => $d['regulatory'],
                ]],
            ]],
            'regulatory_important_note' => 'Informational only. Not medical advice, and not a dosing guide.',
            'potential_applications_intro' => 'The points below name the research class only.',
            'potential_applications' => $d['applications'],
            'potential_applications_important_context' => 'No therapeutic protocol is described.',
            'conclusion' => $d['conclusion'],
            'references' => $d['references'] ?? [],
            'key_points' => $d['keyPoints'],
            'areas_of_research_intro' => 'Topics that match the chemical class of this entry.',
            'areas_of_research' => $d['areas'],
            'key_effects' => $d['keyPoints'],
            'common_use_cases' => $d['uses'] ?? [],
            'how_it_works' => $d['how'],
            'faqs' => $d['faqs'],
            'seo_page_title' => $d['seoTitle'],
            'seo_description' => $d['seoDescription'],
            'seo_og_title' => $d['seoTitle'],
            'seo_og_description' => $d['seoDescription'],
            'show_in_encyclopedia' => true,
            'rating' => '0.00',
            'rating_count' => 0,
        ];
    }
}

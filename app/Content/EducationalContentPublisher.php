<?php

namespace App\Content;

use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Support\SimpleMarkdown;

/**
 * Publishes the educational blog post, two guides, and the corrected
 * FDA reclassification note into the existing Blog and EducationalGuide tables.
 */
class EducationalContentPublisher
{
    public const CANONICAL_HOST = 'https://peptidemap.com';

    public const COMPETITOR_HOSTS = [
        'formblends.com',
        'peptides.so',
        'peptidemethods.com',
        'kinetixatx.com',
    ];

    public static function sync(): void
    {
        foreach (self::catalog() as $page) {
            $markdown = self::prepare((string) file_get_contents(resource_path('content/educational/'.$page['file'])));
            $html = SimpleMarkdown::toHtml($markdown);
            if ($page['kind'] === 'blog') {
                self::upsertBlog($page, $html);
            } else {
                self::upsertGuide($page, $html);
            }
        }
    }

    /**
     * Strip draft-only frontmatter, meta-optimizer notes, and competitor source lines.
     */
    public static function prepare(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = preg_replace('/\A---\n.*?\n---\n+/s', '', $markdown) ?? $markdown;
        $markdown = preg_replace('/\n## Note for Meta Optimizer\b.*\z/s', '', $markdown) ?? $markdown;

        $kept = [];
        foreach (preg_split("/\n/", $markdown) ?: [] as $line) {
            $lower = strtolower($line);
            $drop = false;
            foreach (self::COMPETITOR_HOSTS as $host) {
                if (str_contains($lower, $host)) {
                    $drop = true;
                    break;
                }
            }
            if (
                str_contains($lower, 'competitor pages')
                || str_contains($lower, 'competitor awareness')
                || str_contains($lower, 'competitor beginner')
                || str_contains($lower, 'formblends-style')
            ) {
                $drop = true;
            }
            if (! $drop) {
                $kept[] = $line;
            }
        }

        return trim(implode("\n", $kept))."\n";
    }

    private static function upsertBlog(array $page, string $html): void
    {
        $existing = Blog::where('slug', $page['slug'])->first();
        $payload = [
            'title' => $page['h1'],
            'slug' => $page['slug'],
            'blog_type' => $page['blog_type'],
            'description' => $page['lede'],
            'introduction' => null,
            'key_points' => $page['key_points'],
            'detailed_analysis' => null,
            'conclusion' => null,
            'outline' => null,
            'tags' => $page['tags'],
            'content' => $html,
            'read_time' => $page['read_time'],
            'status' => 'published',
            'seo_page_title' => $page['seo_title'],
            'seo_description' => $page['seo_description'],
            'seo_og_title' => $page['og_title'],
            'seo_og_description' => $page['og_description'],
            'seo_og_image' => self::CANONICAL_HOST.'/images/og-default-v7.png',
            'seo_schema' => [$page['faq']],
        ];

        if ($existing) {
            $existing->fill($payload)->save();

            return;
        }

        $payload['author_name'] = $page['author_name'] ?? 'Peptidemap';
        $payload['author_job'] = $page['author_job'] ?? null;
        $payload['published_at'] = $page['published_at'];
        $payload['is_featured'] = $page['is_featured'] ?? false;
        Blog::create($payload);
    }

    private static function upsertGuide(array $page, string $html): void
    {
        EducationalGuide::updateOrCreate(
            ['slug' => $page['slug']],
            [
                'title' => $page['h1'],
                'slug' => $page['slug'],
                'guide_type' => 'Literacy',
                'tag' => $page['tag'],
                'reading_time' => $page['read_time'],
                'description' => $page['lede'],
                'outline' => null,
                'introduction' => null,
                'content' => $html,
                'status' => 'published',
                'published_at' => $page['published_at'],
                'is_featured' => false,
                'seo_page_title' => $page['seo_title'],
                'seo_description' => $page['seo_description'],
                'seo_og_title' => $page['og_title'],
                'seo_og_description' => $page['og_description'],
                'seo_og_image' => self::CANONICAL_HOST.'/images/og-default-v7.png',
                'seo_schema' => [$page['faq']],
            ]
        );
    }

    private static function faq(string $id, array $pairs): array
    {
        $main = [];
        foreach ($pairs as [$name, $text]) {
            $main[] = [
                '@type' => 'Question',
                'name' => $name,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $text,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $id,
            'mainEntity' => $main,
        ];
    }

    private static function catalog(): array
    {
        $host = self::CANONICAL_HOST;

        return [
            [
                'kind' => 'blog',
                'file' => 'bpc-157-vs-tb-500-evidence.md',
                'slug' => 'bpc-157-vs-tb-500-evidence',
                'h1' => 'BPC-157 vs TB-500: What the Evidence Actually Shows',
                'lede' => 'BPC-157 and TB-500 are frequently discussed together online—often as a “healing stack”—but they are chemically unrelated molecules with separate research histories. Most of what is known about either compound comes from animal and cell studies, not from large, controlled human trials of injectable musculoskeletal use. This page compares what each peptide is, how researchers think they work, where the evidence stops, why stacking claims outrun the data, and how FDA and anti-doping rules currently treat them. It is educational only and is not medical advice.',
                'seo_title' => 'BPC-157 vs TB-500: What the Evidence Shows',
                'seo_description' => 'Educational BPC-157 vs TB-500 comparison: animal vs human evidence, proposed mechanisms, untested stacking claims, FDA compounding notes, and WADA bans.',
                'og_title' => 'BPC-157 vs TB-500: What the Evidence Shows',
                'og_description' => 'BPC-157 vs TB-500: what animal studies show, where human evidence stops, and why stacking claims outrun the data.',
                'blog_type' => 'Research',
                'read_time' => '12 Min Read',
                'published_at' => '2026-09-29',
                'author_name' => 'Peptidemap',
                'tags' => ['BPC-157', 'TB-500', 'Evidence', 'WADA', 'FDA compounding'],
                'key_points' => [
                    'BPC-157 and TB-500 are chemically unrelated peptides.',
                    'Most of the published record is preclinical; robust human efficacy trials for injectable injury use are lacking.',
                    'No published human combination trials were identified for stacking the pair.',
                    'Neither is an FDA-approved drug. A PCAC recommendation is not approval or a final bulks-list rule.',
                    'Both are prohibited in sport under the WADA 2026 Prohibited List.',
                ],
                'faq' => self::faq($host.'/blog/bpc-157-vs-tb-500-evidence#faq', [
                    ['Are BPC-157 and TB-500 the same peptide?', 'No. BPC-157 is a synthetic 15-amino-acid peptide from the gastric-cytoprotection research line. TB-500 usually refers to a short synthetic fragment of thymosin beta-4, a different endogenous protein involved in actin binding and cell migration. They are chemically unrelated.'],
                    ['Is either one FDA-approved for healing injuries?', 'No. Neither is an FDA-approved drug for musculoskeletal healing or any other indication. Compounding-list discussions (including PCAC recommendations) are not the same as drug approval.'],
                    ['Does animal evidence mean they work in humans?', 'No. Animal and cell studies can be scientifically interesting and still fail to predict human outcomes. For injectable injury recovery claims, both peptides still lack robust randomized human efficacy evidence.'],
                    ['Is there research on stacking BPC-157 with TB-500?', 'We did not find published human combination trials establishing safety or efficacy of the pair. Stacking narratives are mostly mechanistic reasoning and anecdote.'],
                    ['Are these substances banned in sport?', 'Yes under WADA rules as of the 2026 Prohibited List: BPC-157 is named in S0; thymosin-β4 and derivatives including TB-500 are named in S2.3. Both classes are prohibited at all times. Check your sport’s governing body as well.'],
                    ['Why do so many sites recommend them together?', 'They are marketed together, share overlapping online recovery audiences, and have complementary-sounding proposed mechanisms. Marketing frequency is not the same as clinical validation.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'fda-peptide-reclassification-2026.md',
                'slug' => 'fda-peptide-reclassification-2026-what-researchers-need-to-know',
                'h1' => 'FDA Peptide Reclassification 2026: What Researchers Need to Know',
                'lede' => 'Early 2026 coverage treated peptide Category 2 changes as if they were Category 1 status and as if pharmacies could compound the named peptides again. This correction separates those ideas. Leaving Category 2 is not Category 1, not the 503A Bulks List, and not compounding permission. Updated 2026-09-30.',
                'seo_title' => 'FDA 2026: Category 2 Removal Is Not Permission',
                'seo_description' => 'Category 2 removal is not Category 1, not the 503A Bulks List, and not compounding permission. PCAC votes are advisory. Educational only.',
                'og_title' => 'FDA 2026: Category 2 Removal Is Not Permission',
                'og_description' => 'Off Category 2 is not Category 1 and not a license to compound. July 2026 PCAC votes were advisory only.',
                'blog_type' => 'Regulation',
                'read_time' => '6 Min Read',
                'published_at' => '2026-04-04',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['FDA', 'Regulation', 'Compounding', 'Category 2', 'PCAC'],
                'key_points' => [
                    'Category 2 removal or a withdrawn nomination is not Category 1 status.',
                    'Off Category 2 is not permission for a pharmacy to compound the substance.',
                    'Category 1 is interim enforcement-discretion framing, not a license to compound and not drug approval.',
                    'BPC-157 is discussed in FDA safety-risk materials as a withdrawn nomination / off Category 2, not as Category 1.',
                    'July 2026 PCAC Bulks List recommendations were advisory only, not a final rule.',
                    'None of this is FDA approval for human therapeutic use.',
                ],
                'faq' => self::faq($host.'/blog/fda-peptide-reclassification-2026-what-researchers-need-to-know#faq', [
                    ['Does removal from Category 2 mean a peptide moved to Category 1?', 'No. Category 2 removal or a withdrawn nomination is not Category 1 status and is not placement on the 503A Bulks List.'],
                    ['Can compounding pharmacies produce a peptide because it left Category 2?', 'No. Off Category 2 is not compounding permission. Category 1 is interim enforcement-discretion framing, not a license to compound.'],
                    ['Is BPC-157 in Category 1 because a nomination was withdrawn?', 'No. FDA safety-risk materials discuss BPC-157 in a withdrawn-nomination / off Category 2 context. That is not Category 1 status and not FDA approval.'],
                    ['Did the July 2026 PCAC vote authorize compounding?', 'No. Contemporaneous reporting describes advisory recommendations for 503A Bulks List inclusion. A PCAC vote is not a final rule and not drug approval.'],
                ]),
            ],
            [
                'kind' => 'guide',
                'file' => 'beginners-guide-to-research-peptides.md',
                'slug' => 'beginners-guide-to-research-peptides',
                'h1' => 'Beginner’s Guide to Research Peptides',
                'lede' => '“Research peptides” is a marketplace phrase as much as a scientific one. Beginners often meet it as vials, certificates, and vendor claims before they know what a peptide is, how an RUO label differs from an approved drug, or how to tell a useful Certificate of Analysis (COA) from a decorative PDF. This guide covers those foundations. It is educational only—not medical advice, not a price guide, and not instructions for human use.',
                'seo_title' => 'Beginner’s Guide to Research Peptides',
                'seo_description' => 'Beginner’s guide to research peptides: definitions, FDA-approved vs compounded vs RUO, how to read COAs, and vendor red flags—no dosing or prices.',
                'og_title' => 'Beginner’s Guide to Research Peptides',
                'og_description' => 'What “research peptides” means: RUO vs approved vs compounded, COA literacy, vendor red flags, and how Peptidemap’s tools fit—educational only.',
                'tag' => 'Basics',
                'read_time' => '8 Min Read',
                'published_at' => '2026-09-29',
                'faq' => self::faq($host.'/guides/beginners-guide-to-research-peptides#faq', [
                    ['Are research peptides the same as prescription peptide drugs?', 'Not necessarily. Some peptide molecules have approved drug products; many marketplace “research peptides” are unapproved substances sold with research disclaimers. A shared name is not the same as an approved finished drug.'],
                    ['What does “research use only” mean?', 'It is a seller’s claimed intended-use framing—typically not marketed as a consumer medicine. It does not prove quality, and it does not create FDA approval. See the legality companion for how FDA looks at intended use.'],
                    ['What is a COA, simply?', 'A Certificate of Analysis is a batch test report—ideally from an identifiable lab—covering identity/purity (and sometimes other attributes). Match lot numbers and verify when possible.'],
                    ['Can Peptidemap tell me which peptide to use for a goal?', 'No. Tools help compare vendors and documentation. Personal treatment decisions are medical/legal questions this guide does not answer.'],
                    ['Where do I check if something can be compounded?', 'Not on this beginner page. Use the companion legality guide and current FDA compounding sources (and educational trackers such as PrescribedRX’s status page) because interim categories and advisory votes change.'],
                    ['Why do vendors differ so much?', 'Synthesis quality, testing practices, storage, naming honesty, and regulatory posture all vary. Documentation literacy matters more than slogans.'],
                ]),
            ],
            [
                'kind' => 'guide',
                'file' => 'peptide-legality-fda-ruo-compounding.md',
                'slug' => 'peptide-legality-fda-ruo-compounding',
                'h1' => 'Are Research Peptides Legal? FDA, RUO & Compounding Explained',
                'lede' => '“Is this peptide legal?” is usually the wrong shape of question. Under U.S. federal food-and-drug law, the better questions are: Is it an FDA-approved drug product? May a compounding pharmacy prepare it under section 503A or 503B? Is an online seller marketing it as a drug despite a research disclaimer? Do sport or workplace rules separately prohibit it? This guide is educational, not legal advice. Peptidemap is a vendor-comparison platform, not a pharmacy and not a law firm.',
                'seo_title' => 'Are Research Peptides Legal? FDA & RUO Basics',
                'seo_description' => 'Educational explainer: FDA approval vs 503A compounding vs RUO—why Category 2 removal and PCAC votes are not final compounding permission.',
                'og_title' => 'Are Research Peptides Legal? FDA & RUO Basics',
                'og_description' => 'FDA approval ≠ compounding ≠ RUO. Why Category 2 removal and PCAC recommendations are not final permission—educational only.',
                'tag' => 'Regulation',
                'read_time' => '10 Min Read',
                'published_at' => '2026-09-29',
                'faq' => self::faq($host.'/guides/peptide-legality-fda-ruo-compounding#faq', [
                    ['Are research peptides legal to buy online?', 'There is no single yes/no answer for every peptide, seller, claim, and jurisdiction. RUO listings exist; FDA still polices unapproved new drugs marketed for human use. This page does not advise purchases.'],
                    ['Does “removed from Category 2” mean pharmacies can compound it?', 'No. Removal from Category 2 is not placement on the 503A Bulks List and is not Category 1 permission.'],
                    ['Did PCAC “approve” BPC-157 or TB-500 in July 2026?', 'PCAC recommended inclusion on the 503A Bulks List for several peptides (per contemporaneous reporting). That is not FDA drug approval and not a final bulks-list rule.'],
                    ['Is compounding the same as FDA approval?', 'No. Compounding is a limited pharmacy pathway. Approval is a separate product authorization process.'],
                    ['Does WADA ban mean something is illegal for everyone?', 'No. WADA governs athletes in anti-doping programs. It is separate from FDA compounding and general consumer law—though the same substance can be constrained in multiple systems at once.'],
                    ['Where should I check status before believing a clinic ad?', 'FDA compounding / bulks / safety-risk pages first; then dated educational trackers; then counsel. On-site Peptidemap posts are context only—cross-check against primary FDA sources when making decisions.'],
                ]),
            ],
        ];
    }
}

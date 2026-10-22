<?php

namespace App\Content;

use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Support\SimpleMarkdown;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Publishes educational blogs and guides into the existing Blog and
 * EducationalGuide tables. Sync is idempotent: rows are upserted by slug.
 *
 * Listing cards read blogs.image and educational_guides.cover. Cover artwork
 * ships in resources/content/educational/images and is copied to
 * public/images/educational on sync. A stored stock-CDN image (Unsplash,
 * picsum, and the other lorem hosts) is replaced by the article cover.
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

    private const FAQ_RENDER_HOLD = ['orforglipron-vs-tirzepatide', 'cagrilintide-vs-eloralintide', 'eloralintide-tirzepatide-vs-retatrutide'];

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
        $markdown = preg_replace('/<!--.*?-->/s', '', $markdown) ?? $markdown;
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
                || str_contains($lower, '/workspace/')
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
        $coverPath = self::publishCover($page['image_file'] ?? null);
        $keepExistingImage = $existing
            && filled($existing->image)
            && ! ListingImageGuard::isGenericPlaceholder((string) $existing->image);

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
            'seo_og_image' => self::ogImageFor($keepExistingImage ? $existing->image : $coverPath),
            'seo_schema' => [$page['faq']],
        ];

        if (! $keepExistingImage && $coverPath) {
            $payload['image'] = $coverPath;
        }

        if ($existing) {
            $existing->fill($payload)->save();
            $blog = $existing->fresh();
        } else {
            $payload['author_name'] = $page['author_name'] ?? 'Peptidemap';
            $payload['author_job'] = $page['author_job'] ?? null;
            $payload['published_at'] = $page['published_at'];
            $payload['is_featured'] = $page['is_featured'] ?? false;
            $blog = Blog::create($payload);
        }

        ListingImageGuard::assertArticleSpecific('blog', $blog->slug, $blog->image);
    }

    private static function upsertGuide(array $page, string $html): void
    {
        $coverPath = self::publishCover($page['image_file'] ?? null);
        $attributes = [
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
            'seo_og_image' => $coverPath
                ? self::CANONICAL_HOST.$coverPath
                : self::CANONICAL_HOST.'/images/og-default-v8.png',
            'seo_schema' => [$page['faq']],
        ];

        if ($coverPath && Schema::hasColumn('educational_guides', 'cover')) {
            $attributes['cover'] = $coverPath;
        }

        $guide = EducationalGuide::updateOrCreate(
            ['slug' => $page['slug']],
            $attributes
        );

        if (Schema::hasColumn('educational_guides', 'cover')) {
            ListingImageGuard::assertArticleSpecific('guide', $guide->slug, $guide->cover);
        }
    }

    /**
     * Copy a cover from the content directory into the public images path.
     * Returns the root-relative URL stored on the listing record.
     */
    private static function publishCover(?string $filename): ?string
    {
        if ($filename === null || trim($filename) === '') {
            return null;
        }

        $filename = basename($filename);
        $source = resource_path('content/educational/images/'.$filename);
        if (! is_file($source)) {
            throw new RuntimeException(
                "Educational cover asset missing: resources/content/educational/images/{$filename}"
            );
        }

        $destDir = public_path('images/educational');
        if (! is_dir($destDir) && ! mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            throw new RuntimeException("Unable to create {$destDir}");
        }

        if (! copy($source, $destDir.'/'.$filename)) {
            throw new RuntimeException("Unable to publish cover asset {$filename}");
        }

        return '/images/educational/'.$filename;
    }

    private static function ogImageFor(?string $image): string
    {
        if ($image && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
            return $image;
        }

        if ($image && str_starts_with($image, '/images/educational/')) {
            return self::CANONICAL_HOST.$image;
        }

        return self::CANONICAL_HOST.'/images/og-default-v8.png';
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

    /**
     * Blog slugs this catalog publishes. Their faq() pairs are the
     * QA-passed set, so the visible-FAQ allowlist is derived from here
     * instead of copying the slugs into config.
     *
     * @return list<string>
     */
    public static function blogSlugs(): array
    {
        $slugs = [];
        foreach (self::catalog() as $page) {
            if (($page['kind'] ?? null) !== 'blog' || in_array($page['slug'] ?? null, self::FAQ_RENDER_HOLD, true)) {
                continue;
            }
            $slug = $page['slug'] ?? null;
            if (is_string($slug) && $slug !== '') {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    private static function catalog(): array
    {
        $host = self::CANONICAL_HOST;

        return array_merge([
            [
                'kind' => 'blog',
                'file' => 'bpc-157-vs-tb-500-evidence.md',
                'image_file' => 'bpc-157-vs-tb-500-evidence.png',
                'slug' => 'bpc-157-vs-tb-500-evidence',
                'h1' => 'BPC-157 vs TB-500: What the Evidence Actually Shows',
                'lede' => 'BPC-157 and TB-500 are frequently discussed together online—often as a “healing stack”—but they are chemically unrelated molecules with separate research histories. Most of what is known about either compound comes from animal and cell studies, not from large, controlled human trials of injectable musculoskeletal use. This page compares what each peptide is, how researchers think they work, where the evidence stops, why stacking claims outrun the data, and how FDA and anti-doping rules currently treat them. It is educational only and is not medical advice.',
                'seo_title' => 'BPC-157 vs TB-500: What the Evidence Shows',
                'seo_description' => 'Educational BPC-157 vs TB-500 comparison: animal vs human evidence, proposed mechanisms, untested stacking claims, FDA compounding notes, and WADA bans.',
                'og_title' => 'BPC-157 vs TB-500: What the Evidence Shows',
                'og_description' => 'BPC-157 vs TB-500: what animal studies show, where human evidence stops, and why stacking claims outrun the data.',
                'blog_type' => 'Research',
                'read_time' => '14 Min Read',
                'published_at' => '2026-09-29',
                'author_name' => 'Peptidemap',
                'tags' => ['BPC-157', 'TB-500', 'Thymosin beta-4', 'Evidence', 'WADA', 'FDA compounding'],
                'key_points' => [
                    'BPC-157 and TB-500 are chemically unrelated peptides.',
                    'In FDA’s July 2026 briefing, TB-500 is the 7-residue Ac-LKKTETQ fragment (thymosin β4 residues 17–23), not the 43-amino-acid thymosin β4 protein.',
                    'Full-length thymosin β4 has small human trials in eye and skin wounds, with mixed results. No published human study of TB-500 was identified.',
                    'No human trial has studied BPC-157 with TB-500. A 2026 rat Achilles study found no added benefit from combining them.',
                    'Neither is an FDA-approved drug. A PCAC recommendation is not approval or a final bulks-list rule.',
                    'Both are prohibited in sport under the WADA 2026 Prohibited List.',
                ],
                'faq' => self::faq($host.'/blog/bpc-157-vs-tb-500-evidence#faq', [
                    ['Are BPC-157 and TB-500 the same peptide?', 'No. BPC-157 is a synthetic 15-amino-acid peptide from the gastric-cytoprotection research line. TB-500 usually refers to a short synthetic fragment of thymosin beta-4, a different endogenous protein involved in actin binding and cell migration. They are chemically unrelated.'],
                    ['Is TB-500 the same as thymosin beta-4?', 'No. Full-length thymosin beta-4 is a 43-amino-acid human protein. In FDA’s July 2026 briefing, TB-500 is a 7-amino-acid acetylated fragment, Ac-LKKTETQ, which matches residues 17 to 23 of thymosin beta-4; vendors do not always use the name this way. The same briefing states that “thymosin beta-4 and TB-500 are not the same substance.” Full-length thymosin beta-4 has been tested in human trials as eye drops and skin gels, with mixed results. No published human study of TB-500 was identified.'],
                    ['Is either one FDA-approved for healing injuries?', 'No. Neither is an FDA-approved drug for musculoskeletal healing or any other indication. Compounding-list discussions, including PCAC recommendations, are not the same as drug approval.'],
                    ['Does animal evidence mean they work in humans?', 'No. Animal and cell studies can be scientifically interesting and still fail to predict human outcomes. For injectable injury recovery claims, neither peptide has randomized controlled human trial evidence.'],
                    ['What is the “Wolverine stack”?', 'It is a nickname that vendors and online communities use for pairing BPC-157 with TB-500. It is not a trial name or a protocol. No human trial has studied the pair. A 2026 rat Achilles study (Biçer et al., Jt Dis Relat Surg) reported that combined BPC-157 and TB-500 did not confer additional benefits compared with either agent alone, and that paper did not confirm its “TB-500” was the Ac-LKKTETQ fragment. A July 2026 FDA advisory committee vote recommending each substance for the 503A bulks list is advisory and is not FDA approval. Peptidemap does not give dosing or combination instructions.'],
                    ['Are these substances banned in sport?', 'Yes under WADA rules as of the 2026 Prohibited List: BPC-157 is named in S0; thymosin-β4 and derivatives including TB-500 are named in S2.3. Both classes are prohibited at all times. Check your sport’s governing body as well.'],
                    ['Why do so many sites recommend them together?', 'They are marketed together, share overlapping online recovery audiences, and have complementary-sounding proposed mechanisms. Marketing frequency is not the same as clinical validation.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'fda-peptide-reclassification-2026.md',
                'image_file' => 'fda-peptide-reclassification-2026.png',
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
                'image_file' => 'beginners-guide-to-research-peptides.png',
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
                    ['Where do I check if something can be compounded?', 'Not on this beginner page. Use the companion legality guide and current FDA compounding sources (and educational trackers) because interim categories and advisory votes change.'],
                    ['Why do vendors differ so much?', 'Synthesis quality, testing practices, storage, naming honesty, and regulatory posture all vary. Documentation literacy matters more than slogans.'],
                ]),
            ],
            [
                'kind' => 'guide',
                'file' => 'peptide-legality-fda-ruo-compounding.md',
                'image_file' => 'peptide-legality-fda-ruo-compounding.png',
                'slug' => 'peptide-legality-fda-ruo-compounding',
                'h1' => 'Are Research Peptides Legal? FDA, RUO & Compounding Explained',
                'lede' => '“Is this peptide legal?” is usually the wrong shape of question. Under U.S. federal food-and-drug law, the better questions are: Is it an FDA-approved drug product? May a compounding pharmacy prepare it under section 503A or 503B? Is an online seller marketing it as a drug despite a research disclaimer? Do sport or workplace rules separately prohibit it? This guide is educational, not legal advice. Peptidemap is a vendor-comparison platform, not a pharmacy and not a law firm.',
                'seo_title' => 'Are Research Peptides Legal? FDA & RUO Basics',
                'seo_description' => 'Educational explainer: FDA approval vs 503A compounding vs RUO, plus the October 2026 interim peptide compounding policy under OMB review (not issued).',
                'og_title' => 'Are Research Peptides Legal? FDA & RUO Basics',
                'og_description' => 'Educational explainer: FDA approval vs 503A compounding vs RUO, plus the October 2026 interim peptide compounding policy under OMB review (not issued).',
                'tag' => 'Regulation',
                'read_time' => '20 Min Read',
                'published_at' => '2026-09-29',
                'faq' => self::faq($host.'/guides/peptide-legality-fda-ruo-compounding#faq', [
                    ['Are research peptides legal to buy online?', 'There is no single yes/no answer for every peptide, seller, claim, and jurisdiction. RUO listings exist; FDA still polices unapproved new drugs marketed for human use. This page does not advise purchases.'],
                    ['Does “removed from Category 2” mean pharmacies can compound it?', 'No. Removal from Category 2 is not placement on the 503A Bulks List and is not Category 1 permission.'],
                    ['Did PCAC “approve” BPC-157 or TB-500 in July 2026?', 'PCAC recommended inclusion on the 503A Bulks List for several peptides (per contemporaneous reporting). That is not FDA drug approval and not a final bulks-list rule.'],
                    ['Did FDA approve compounded peptides in October 2026?', 'No. On 14 October 2026 HHS/FDA sent an “Interim Access Policy for Compounding Using Certain Peptides” to OMB for review. As of 19 October 2026 it is under OMB review, not yet issued, and its text is not public. A policy under review is not FDA drug approval and does not place any peptide on the 503A Bulks List.'],
                    ['Is compounding the same as FDA approval?', 'No. Compounding is a limited pharmacy pathway. Approval is a separate product authorization process.'],
                    ['Does WADA ban mean something is illegal for everyone?', 'No. WADA governs athletes in anti-doping programs. It is separate from FDA compounding and general consumer law—though the same substance can be constrained in multiple systems at once.'],
                    ['Where should I check status before believing a clinic ad?', 'FDA compounding / bulks / safety-risk pages first; then dated educational trackers; then counsel. On-site Peptidemap posts are context only—cross-check against primary FDA sources when making decisions.'],
                ]),
            ],
        ], self::evidenceNotes($host), self::octoberQaPieces($host), self::coaLiteracyAndIdentityNotes($host), self::bpc157VsGhkCu($host), self::aod9604VsTesamorelin($host), self::cagrisemaVsTirzepatide($host), self::orforglipronVsOralSemaglutide($host), self::compoundedGlp1Status($host), self::retatrutideSideEffectsTrialData($host), self::motsCVsNad($host), self::ipamorelinVsSermorelin($host), self::retatrutideVsSemaglutide($host), self::selankVsSemax($host), self::orforglipronVsOralSemaglutideCompare($host));
    }

    /**
     * Six RUO evidence notes that companion existing /compare price pages.
     * Bodies live in resources/content/educational. Content QA passed the drafts on 2026-10-05.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function evidenceNotes(string $host): array
    {
        $published = '2026-10-05';

        return [
            [
                'kind' => 'blog',
                'file' => 'glow-vs-klow.md',
                'image_file' => 'glow-vs-klow.png',
                'slug' => 'glow-vs-klow',
                'h1' => 'GLOW vs KLOW: What the Blend Labels Contain',
                'lede' => 'GLOW versus KLOW research blends: KLOW adds KPV, not extra GHK-Cu, to GHK-Cu, BPC-157, and TB-500. A contents comparison, not a clinical ranking or dose guide.',
                'seo_title' => 'GLOW vs KLOW: What the Blend Labels Contain',
                'seo_description' => 'GLOW versus KLOW research blends: KLOW adds KPV, not extra GHK-Cu, to GHK-Cu, BPC-157, and TB-500. A contents comparison, not a clinical ranking or dose guide.',
                'og_title' => 'GLOW vs KLOW: What the Blend Labels Contain',
                'og_description' => 'GLOW versus KLOW research blends: KLOW adds KPV, not extra GHK-Cu, to GHK-Cu, BPC-157, and TB-500. A contents comparison, not a clinical ranking or dose guide.',
                'blog_type' => 'Research',
                'read_time' => '8 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['GLOW', 'KLOW', 'KPV', 'RUO', 'Blend labels'],
                'key_points' => [
                    'On Peptidemap’s labels, GLOW names GHK-Cu, BPC-157, and TB-500. KLOW is that set plus KPV.',
                    'KPV is Lys-Pro-Val, CAS 67727-97-3. It is not extra GHK-Cu.',
                    'A paper on one component is not a trial of the blend. No human trial of the product names GLOW or KLOW was identified.',
                    'GHK-Cu alone is not GLOW or KLOW, and KPV alone is not KLOW.',
                    'This is a contents comparison. It does not rank the blends and it is not medical advice.',
                ],
                'faq' => self::faq($host.'/blog/glow-vs-klow#faq', [
                    ['What is the difference between GLOW and KLOW?', 'On Peptidemap’s catalog labels, GLOW is GHK-Cu, BPC-157, and TB-500. KLOW is that same named set plus KPV (Lys-Pro-Val). The added piece is KPV, not a higher GHK-Cu amount.'],
                    ['Is KPV the same as GHK-Cu?', 'No. KPV is the tripeptide Lys-Pro-Val, described as the C-terminal fragment of alpha-MSH. Wikipedia and PubChem list CAS 67727-97-3. It is not a copper peptide and not a fragment of GHK.'],
                    ['Does a study of one ingredient show what the blend does?', 'No. Work on GHK-Cu, BPC-157, TB-500, or KPV stays attached to the molecule studied. A PubMed check on 5 October 2026 did not find the commercial blend names as indexed phrases. No human trial of those product names was identified.'],
                    ['Does this page say which blend is better?', 'No. It is a contents comparison and a limit on blend evidence. It does not rank healing, skin, gut, or recovery, and it is not medical advice.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'orforglipron-vs-tirzepatide.md',
                'image_file' => 'orforglipron-vs-tirzepatide.png',
                'slug' => 'orforglipron-vs-tirzepatide',
                'h1' => 'Orforglipron vs Tirzepatide: Two Molecules, Two Programs',
                'lede' => 'Orforglipron is an oral small molecule, not a peptide. Tirzepatide is injectable. This evidence note keeps their trials, populations, and estimands apart.',
                'seo_title' => 'Orforglipron vs Tirzepatide: Two Molecules, Two Programs',
                'seo_description' => 'Orforglipron is an oral small molecule, not a peptide. Tirzepatide is injectable. This evidence note keeps their trials, populations, and estimands apart.',
                'og_title' => 'Orforglipron vs Tirzepatide: Two Molecules, Two Programs',
                'og_description' => 'Orforglipron is an oral small molecule, not a peptide. Tirzepatide is injectable. This evidence note keeps their trials, populations, and estimands apart.',
                'blog_type' => 'Research',
                'read_time' => '8 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Orforglipron', 'Tirzepatide', 'ATTAIN', 'SURMOUNT', 'Estimands'],
                'key_points' => [
                    'Orforglipron is an oral nonpeptide GLP-1 receptor agonist. Tirzepatide is an injectable GIP and GLP-1 peptide.',
                    'ATTAIN-1’s primary treatment-regimen result at 36 mg was −11.2% at week 72. The −12.4% figure is that trial’s efficacy estimand.',
                    'SURMOUNT-1’s treatment-regimen result at tirzepatide 15 mg was −20.9%. The −22.5% figure is that trial’s efficacy estimand, not orforglipron.',
                    'ATTAIN-2’s treatment-regimen result at 36 mg was −9.6% in type 2 diabetes. It is not the ATTAIN-1 −12.4% and not SURMOUNT-1.',
                    'Lilly’s 9 April 2026 release says FDA approved Foundayo (orforglipron) tablets on 1 April 2026. A research-use listing is not that tablet.',
                ],
                'faq' => self::faq($host.'/blog/orforglipron-vs-tirzepatide#faq', [
                    ['Is orforglipron a peptide?', 'No. Orforglipron is a small-molecule, nonpeptide GLP-1 receptor agonist taken by mouth. Tirzepatide is a peptide dual agonist at the GIP and GLP-1 receptors, given once weekly by subcutaneous injection.'],
                    ['What did ATTAIN-1 report for orforglipron?', 'ATTAIN-1’s primary treatment-regimen estimand at week 72 was −7.5% at 6 mg, −8.4% at 12 mg, and −11.2% at 36 mg, versus −2.1% with placebo. The −12.4% figure is the 36 mg efficacy estimand in that trial, not the primary −11.2%. Those milligram figures are trial assignments, not use instructions.'],
                    ['Are the SURMOUNT-1 percents orforglipron results?', 'No. SURMOUNT-1 is a tirzepatide trial without diabetes. Its treatment-regimen change at 15 mg was −20.9% versus −3.1% with placebo. The −22.5% figure is that trial’s 15 mg efficacy estimand.'],
                    ['Does Foundayo approval make a research-use listing the labeled tablet?', 'No. Lilly’s 9 April 2026 release says the FDA approved Foundayo (orforglipron) tablets on 1 April 2026, and DailyMed lists initial U.S. approval in 2026 for a labeled weight-management use with diet and activity. A research-use powder, capsule, or vial is not a Foundayo tablet. This note is not medical advice.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'retatrutide-vs-tirzepatide.md',
                'image_file' => 'retatrutide-vs-tirzepatide.png',
                'slug' => 'retatrutide-vs-tirzepatide',
                'h1' => 'Retatrutide vs Tirzepatide: Triple Agonist, Dual Agonist',
                'lede' => 'Retatrutide is an investigational triple agonist. Tirzepatide is an approved dual agonist. This note keeps their own trials, weeks, and estimands apart.',
                'seo_title' => 'Retatrutide vs Tirzepatide: Triple Agonist, Dual Agonist',
                'seo_description' => 'Retatrutide is an investigational triple agonist. Tirzepatide is an approved dual agonist. This note keeps their own trials, weeks, and estimands apart.',
                'og_title' => 'Retatrutide vs Tirzepatide: Triple Agonist, Dual Agonist',
                'og_description' => 'Retatrutide is an investigational triple agonist. Tirzepatide is an approved dual agonist. This note keeps their own trials, weeks, and estimands apart.',
                'blog_type' => 'Research',
                'read_time' => '7 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'Tirzepatide', 'TRIUMPH', 'SURMOUNT', 'Estimands'],
                'key_points' => [
                    'Retatrutide is an investigational GIP, GLP-1, and glucagon agonist. Tirzepatide is a dual GIP and GLP-1 agonist.',
                    'TRIUMPH-1 at the highest dose arm and 80 weeks: treatment-regimen −25.0% in the published paper; efficacy estimand −28.3%, reported in the same paper and company-reported by Lilly (press release of 21 May 2026). Those are two estimands of one trial.',
                    'TRIUMPH-2, in type 2 diabetes, reported treatment-regimen −18.8% and efficacy −20.8% (reported in the Lancet paper and company-reported by Lilly, press release of 23 July 2026) in the highest dose arm. It is not TRIUMPH-1.',
                    'SURMOUNT-1 studied tirzepatide to 72 weeks. Tirzepatide’s highest dose arm treatment-regimen result was −20.9%; −22.5% is the efficacy estimand.',
                    'TRIUMPH-5 is a registered retatrutide-versus-tirzepatide trial with no results in the record checked on 5 October 2026.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-vs-tirzepatide#faq', [
                    ['How do retatrutide and tirzepatide differ?', 'Retatrutide (LY3437943) is one investigational molecule with agonist activity at GIP, GLP-1, and glucagon receptors. Tirzepatide is a dual GIP and GLP-1 agonist and does not add glucagon-receptor agonism. Retatrutide is not an approved medicine.'],
                    ['Why can TRIUMPH-1 show both −25.0% and −28.3%?', 'They are two estimands of the same 80-week trial, for the highest dose arm. −25.0% is the treatment-regimen estimand in the New England Journal of Medicine paper. −28.3% is the efficacy estimand, reported in the same paper and company-reported by Lilly in its press release of 21 May 2026. They are not two trials.'],
                    ['Is TRIUMPH-2 the same result as TRIUMPH-1?', 'No. TRIUMPH-2 enrolled adults with type 2 diabetes. In the highest dose arm the treatment-regimen change in the Lancet paper was −18.8% versus −5.1% with placebo, and the efficacy estimand was −20.8%, reported in the same paper and company-reported by Lilly in its press release of 23 July 2026, versus −4.0% with placebo. That is not the TRIUMPH-1 treatment-regimen −25.0%.'],
                    ['Has TRIUMPH-5 already compared retatrutide with tirzepatide?', 'TRIUMPH-5 (NCT06662383) is a registered phase 3 trial of retatrutide versus tirzepatide. The ClinicalTrials.gov record retrieved on 5 October 2026 was active, not recruiting, and had no results. No TRIUMPH-5 percent is used in this note.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'retatrutide-cagrilintide-blend.md',
                'image_file' => 'retatrutide-cagrilintide-blend.png',
                'slug' => 'retatrutide-cagrilintide-blend',
                'h1' => 'Retatrutide–Cagrilintide Blend: Catalog Name, Not a Published Trial',
                'lede' => 'A research blend named retatrutide plus cagrilintide is not a published trial. This note separates TRIUMPH, REDEFINE/CagriSema evidence from that label.',
                'seo_title' => 'Retatrutide–Cagrilintide Blend: Catalog Name, Not a Published Trial',
                'seo_description' => 'A research blend named retatrutide plus cagrilintide is not a published trial. This note separates TRIUMPH, REDEFINE/CagriSema evidence from that label.',
                'og_title' => 'Retatrutide–Cagrilintide Blend: Catalog Name, Not a Published Trial',
                'og_description' => 'A research blend named retatrutide plus cagrilintide is not a published trial. This note separates TRIUMPH, REDEFINE/CagriSema evidence from that label.',
                'blog_type' => 'Research',
                'read_time' => '7 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'Cagrilintide', 'RUO', 'TRIUMPH', 'REDEFINE'],
                'key_points' => [
                    'A vial named retatrutide plus cagrilintide is a catalog label, not a published trial and not CagriSema.',
                    'TRIUMPH-1 and TRIUMPH-2 studied retatrutide alone. Their percents are not blend results.',
                    'REDEFINE-1’s −20.4% (treatment-policy) and −22.7% (trial-product) are cagrilintide with semaglutide.',
                    'Cagrilintide alone in REDEFINE-1 was −11.5% (treatment-policy) and −11.8% (trial-product).',
                    'TRIUMPH-5 compares retatrutide with tirzepatide. It does not list cagrilintide, and the checked record had no results.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-cagrilintide-blend#faq', [
                    ['Is a retatrutide–cagrilintide vial a published trial?', 'No. The product name is a catalog label. TRIUMPH studies retatrutide alone. REDEFINE’s combination, CagriSema, is cagrilintide with semaglutide, not retatrutide.'],
                    ['What did TRIUMPH-1 report for retatrutide alone?', 'At week 80 the treatment-regimen change in the New England Journal of Medicine paper was −17.6% in the lowest dose arm, −23.7% in the middle dose arm, and −25.0% in the highest dose arm, versus −3.9% with placebo. The efficacy estimand in the highest dose arm, reported in the same paper and company-reported by Lilly in its press release of 21 May 2026, was −28.3%. Those rows are retatrutide monotherapy, not a blend result.'],
                    ['Is TRIUMPH-5 the blend trial?', 'No. TRIUMPH-5 (NCT06662383) compares retatrutide with tirzepatide in adults with obesity. The record checked on 5 October 2026 did not list cagrilintide as an intervention, and HasResults was false. This note invents no outcome percent for TRIUMPH-5.'],
                    ['Can CagriSema’s −20.4% be applied to this blend?', 'No. REDEFINE-1’s −20.4% (treatment-policy) and −22.7% (trial-product) are cagrilintide with semaglutide at 68 weeks. The cagrilintide-only arms were −11.5% and −11.8%. None of those percents is a retatrutide–cagrilintide blend.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'cagrilintide-vs-eloralintide.md',
                'image_file' => 'cagrilintide-vs-eloralintide.png',
                'slug' => 'cagrilintide-vs-eloralintide',
                'h1' => 'Cagrilintide vs Eloralintide: Two Amylin-Pathway Compounds, Not One Trial',
                'lede' => 'Cagrilintide is not eloralintide. This note separates the published trials, estimands, and populations behind the price comparison, without any winner.',
                'seo_title' => 'Cagrilintide vs Eloralintide: Two Amylin-Pathway Compounds, Not One Trial',
                'seo_description' => 'Cagrilintide is not eloralintide. This note separates the published trials, estimands, and populations behind the price comparison, without any winner.',
                'og_title' => 'Cagrilintide vs Eloralintide: Two Amylin-Pathway Compounds, Not One Trial',
                'og_description' => 'Cagrilintide is not eloralintide. This note separates the published trials, estimands, and populations behind the price comparison, without any winner.',
                'blog_type' => 'Research',
                'read_time' => '8 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Cagrilintide', 'Eloralintide', 'REDEFINE', 'EloraTZP', 'Estimands'],
                'key_points' => [
                    'Cagrilintide and eloralintide are different molecules. No head-to-head was identified in the primaries checked here.',
                    'REDEFINE-1’s cagrilintide-only arm was −11.5% on the treatment-policy estimand. −20.4% is cagrilintide plus semaglutide.',
                    'REDEFINE-2’s −13.7% is CagriSema in type 2 diabetes. That trial had no cagrilintide-only arm.',
                    'Eloralintide monotherapy efficacy-estimand results ran from −9.5% at 1 mg to −20.1% at 9 mg, versus −0.4% with placebo, at 48 weeks.',
                    'EloraTZP’s −23.3% is eloralintide 9 mg plus tirzepatide 15 mg on the efficacy estimand. It is not a cagrilintide result.',
                ],
                'faq' => self::faq($host.'/blog/cagrilintide-vs-eloralintide#faq', [
                    ['Are cagrilintide and eloralintide the same molecule?', 'No. Cagrilintide is Novo Nordisk’s long-acting amylin analogue. Eloralintide (LY3841136) is Eli Lilly’s investigational once-weekly selective amylin receptor agonist. No randomized comparison of the two was identified in the primaries checked for this note.'],
                    ['What is the cagrilintide-only result in REDEFINE-1?', 'On the treatment-policy estimand at week 68, cagrilintide 2.4 mg alone was −11.5%, and the trial-product row was −11.8%. The −20.4% and −22.7% figures are cagrilintide plus semaglutide, not cagrilintide alone.'],
                    ['What did eloralintide monotherapy report?', 'Lilly’s phase 2 release gives an efficacy-estimand range from −9.5% at 1 mg to −20.1% at 9 mg, versus −0.4% with placebo, at 48 weeks in adults without type 2 diabetes. Those percents are eloralintide alone. They are not CagriSema.'],
                    ['Is the −23.3% figure a cagrilintide result?', 'No. −23.3% is the EloraTZP efficacy estimand for eloralintide 9 mg plus tirzepatide 15 mg at 48 weeks in adults with type 2 diabetes, versus −14.8% for tirzepatide 15 mg alone and −3.0% for placebo. It is not a REDEFINE comparison.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'eloralintide-tirzepatide-vs-retatrutide.md',
                'image_file' => 'eloralintide-tirzepatide-vs-retatrutide.png',
                'slug' => 'eloralintide-tirzepatide-vs-retatrutide',
                'h1' => 'Eloralintide + Tirzepatide vs Retatrutide: What the Published Data Actually Show',
                'lede' => 'Eloralintide plus tirzepatide vs retatrutide: published trial figures, why TRIUMPH-1\'s 25.0% and 28.3% both fit, and why combo data are not head-to-heads.',
                'seo_title' => 'Eloralintide + Tirzepatide vs Retatrutide: What the Published Data Actually Show',
                'seo_description' => 'Eloralintide plus tirzepatide vs retatrutide: published trial figures, why TRIUMPH-1\'s 25.0% and 28.3% both fit, and why combo data are not head-to-heads.',
                'og_title' => 'Eloralintide + Tirzepatide vs Retatrutide: What the Published Data Actually Show',
                'og_description' => 'Eloralintide plus tirzepatide vs retatrutide: published trial figures, why TRIUMPH-1\'s 25.0% and 28.3% both fit, and why combo data are not head-to-heads.',
                'blog_type' => 'Research',
                'read_time' => '7 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Eloralintide', 'Tirzepatide', 'Retatrutide', 'EloraTZP', 'TRIUMPH'],
                'key_points' => [
                    'EloraTZP’s efficacy estimand for eloralintide 9 mg plus tirzepatide 15 mg was −23.3% at 48 weeks in type 2 diabetes.',
                    'TRIUMPH-1 at 12 mg and 80 weeks, without diabetes: treatment-regimen −25.0% and efficacy estimand −28.3%. Both can be true.',
                    'TRIUMPH-2, with type 2 diabetes, was −18.8% treatment-regimen and −20.8% efficacy estimand at 12 mg. It is not TRIUMPH-1.',
                    'The programs differ in population, duration, phase, intervention, and estimand. No published head-to-head was identified.',
                    'An unverified early-combo slide figure is not printed. This note does not rank a winner.',
                ],
                'faq' => self::faq($host.'/blog/eloralintide-tirzepatide-vs-retatrutide#faq', [
                    ['Why can TRIUMPH-1 list both −25.0% and −28.3%?', 'They are two estimands of one trial at 12 mg and 80 weeks in adults without type 2 diabetes. −25.0% is the treatment-regimen estimand. −28.3% is the efficacy estimand. Lead with −25.0% when comparing programs. −28.3% is the efficacy-estimand companion, not a second trial.'],
                    ['What did EloraTZP report for eloralintide plus tirzepatide?', 'Lilly’s 30 September 2026 release reports an efficacy estimand of −23.3% for eloralintide 9 mg plus tirzepatide 15 mg at 48 weeks, versus −14.8% for tirzepatide 15 mg alone and −3.0% for placebo, in 367 adults with obesity or overweight and type 2 diabetes.'],
                    ['Is that combination result a head-to-head with TRIUMPH-1?', 'No. EloraTZP phase 2b included type 2 diabetes, ran 48 weeks, and its table is an efficacy estimand. TRIUMPH-1 excluded diabetes, ran 80 weeks, and its lead figure is a treatment-regimen estimand. No published head-to-head of the combination versus retatrutide was identified.'],
                    ['What did TRIUMPH-2 report?', 'TRIUMPH-2 enrolled adults with type 2 diabetes, a closer population match for EloraTZP and still a different study. At 80 weeks the 12 mg treatment-regimen change was −18.8% (placebo −5.1%). The efficacy estimand at 12 mg was −20.8% (placebo −4.0% on Lilly Medical).'],
                ]),
            ],
        ];
    }

    /**
     * Content-QA pieces cleared after the six evidence notes: one GHRH comparison,
     * one diluent-literacy guide. Bodies live in resources/content/educational.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function octoberQaPieces(string $host): array
    {
        $published = '2026-09-30';

        return [
            [
                'kind' => 'blog',
                'file' => 'tesamorelin-vs-sermorelin.md',
                'image_file' => 'tesamorelin-vs-sermorelin.png',
                'slug' => 'tesamorelin-vs-sermorelin',
                'h1' => 'Tesamorelin vs Sermorelin: How These GHRH Analogs Differ',
                'lede' => 'Tesamorelin and sermorelin both act on the growth hormone–releasing hormone (GHRH / GHRF) pathway, so they are often grouped together online. They are not interchangeable. They differ in structure, U.S. regulatory history, and labeled study populations. This page is an educational head-to-head of what each compound is and how approval (and discontinuation) records treat them—not a shopping guide, not a beginner primer, and not medical advice.',
                'seo_title' => 'Tesamorelin vs Sermorelin: GHRH Analog Differences',
                'seo_description' => 'Educational tesamorelin vs sermorelin comparison: GHRH chemistry, EGRIFTA vs GEREF approval history, studied populations, and why RUO vials are not the same.',
                'og_title' => 'Tesamorelin vs Sermorelin: GHRH Analog Differences',
                'og_description' => 'How tesamorelin and sermorelin differ: chemistry, EGRIFTA vs GEREF history, and who was studied. Educational only, not a price guide.',
                'blog_type' => 'Research',
                'read_time' => '7 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Tesamorelin', 'Sermorelin', 'EGRIFTA', 'GEREF', 'GHRH'],
                'key_points' => [
                    'Tesamorelin is a 44-amino-acid GRF analog plus an N-terminal hexenoyl moiety (EGRIFTA). Sermorelin is synthetic GHRH(1-29)-amide (historical brand GEREF).',
                    'EGRIFTA is labeled for excess abdominal fat in HIV-associated lipodystrophy. It is not indicated for weight-loss management.',
                    'GEREF NDAs were withdrawn effective June 18, 2009 after the sponsor discontinued marketing. FDA later found that withdrawal was not for safety or effectiveness.',
                    'A research-use vial that shares a peptide name is not the approved finished drug.',
                    'This page does not give dosing, reconstitution, stacking, or a recommendation of which compound to take.',
                ],
                'faq' => self::faq($host.'/blog/tesamorelin-vs-sermorelin#faq', [
                    ['Are tesamorelin and sermorelin the same?', 'No. Both are GHRH-pathway analogs, but tesamorelin is a modified full-length GRF analog (44 amino acids plus an N-terminal hexenoyl moiety), while sermorelin is the shorter GHRH(1-29)-amide fragment. Brand histories (EGRIFTA vs GEREF) and labeled uses also differ.'],
                    ['Is either FDA-approved?', 'Tesamorelin is FDA-approved as specific EGRIFTA finished products for reduction of excess abdominal fat in HIV-infected adults with lipodystrophy (initial U.S. approval 2010). Sermorelin (GEREF) was approved historically for diagnostic pituitary GH testing (1990) and for idiopathic growth hormone deficiency in children with growth failure (1997). The sponsor discontinued GEREF; FDA withdrew those NDA approvals effective June 18, 2009, and later determined the withdrawal was not for safety or effectiveness. A research-use vial is not an FDA-approved drug merely because it shares a peptide name.'],
                    ['Is this medical advice?', 'No. This is an educational comparison from a research-use peptide comparison publisher. It is not medical advice, not a prescription decision aid, and not an instruction to use a research product as treatment.'],
                    ['Which one should someone take?', 'This page does not answer that. It does not give dosing, reconstitution, stacking, or product-selection guidance. Therapy decisions belong with licensed clinicians and approved labeling, not with a blog or a research catalog.'],
                ]),
            ],
            [
                'kind' => 'guide',
                'file' => 'bacteriostatic-water-literacy.md',
                'image_file' => 'bacteriostatic-water-literacy.png',
                'slug' => 'bacteriostatic-water-literacy',
                'h1' => 'Bacteriostatic Water Literacy Guide',
                'lede' => '“Bac water” shows up constantly next to research-peptide listings. Shoppers often treat every clear diluent as the same thing—or assume a multi-dose vial label is a reconstitution recipe. This guide is diluent literacy only: what bacteriostatic water is, how the preservative relates to multi-puncture containers, how it differs from sterile water, and how to read storage and population warnings on real labels. It is educational only—not medical advice, not a price guide, and not instructions for reconstituting research chemicals.',
                'seo_title' => 'Bacteriostatic Water Literacy Guide',
                'seo_description' => 'What bacteriostatic water is, why benzyl alcohol matters for multi-dose vials, how it differs from sterile water, and RUO framing—no reconstitution recipes.',
                'og_title' => 'Bacteriostatic Water Literacy Guide',
                'og_description' => 'BAC water vs sterile water, benzyl alcohol multi-dose role, and label literacy—educational only; prices live on a separate Peptidemap page.',
                'tag' => 'Diluent',
                'read_time' => '6 Min Read',
                'published_at' => $published,
                'faq' => self::faq($host.'/guides/bacteriostatic-water-literacy#faq', [
                    ['Is bacteriostatic water a peptide?', 'No. It is a preserved sterile diluent (USP concept / labeled sterile product)—not an active peptide drug.'],
                    ['Is bacteriostatic water the same as sterile water?', 'No. Sterile Water for Injection typically has no antimicrobial preservative and is handled as a single-dose container concept. Bacteriostatic water includes a preservative (often benzyl alcohol about 0.9%) for a multi-dose paradigm when labeled as such.'],
                    ['Why does Peptidemap refuse reconstitution charts?', 'Site policy: diluent and peptide literacy without dosing math or reconstitution recipes for research chemicals. That keeps educational pages from doubling as protocols.'],
                    ['Can neonates receive bacteriostatic water?', 'Typical product labeling contraindicates benzyl alcohol–preserved bacteriostatic water in neonates. Follow current inserts—not informal summaries.'],
                    ['Where do I compare vendors or prices?', 'On the separate product page at https://peptidemap.com/bacteriostatic-water. This guide does not restate prices or stock.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed these three on 2026-10-07. The COA literacy post is an
     * in-place deepen of the live slug. The CJC-1295 and retatrutide testing
     * notes are new. They ship together because the COA post and the retatrutide
     * testing post link each other. Bodies live in resources/content/educational.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function coaLiteracyAndIdentityNotes(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'how-to-verify-a-peptide-vendor-certificate-of-analysis.md',
                'image_file' => 'how-to-verify-a-peptide-vendor-certificate-of-analysis.png',
                'slug' => 'how-to-verify-a-peptide-vendor-certificate-of-analysis',
                'h1' => 'How to Verify a Peptide Vendor Certificate of Analysis',
                'lede' => 'How to read a peptide COA, spot shared-lot reuse, verify a report on the lab site, and understand what purity paperwork does and does not prove.',
                'seo_title' => 'How to Verify a Peptide Vendor Certificate of Analysis',
                'seo_description' => 'How to read a peptide COA, spot shared-lot reuse, verify a report on the lab site, and understand what purity paperwork does and does not prove.',
                'og_title' => 'How to Verify a Peptide Vendor Certificate of Analysis',
                'og_description' => 'How to read a peptide COA, spot shared-lot reuse, verify a report on the lab site, and understand what purity paperwork does and does not prove.',
                'blog_type' => 'Guides',
                'read_time' => '9 Min Read',
                'published_at' => '2026-04-06',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['COA', 'Certificate of Analysis', 'Janoshik', 'Shared lot', 'RUO'],
                'key_points' => [
                    'A COA is evidence about a submitted sample, not a blanket guarantee that harmful contaminants are absent.',
                    'Match the vial lot to the report lot, then verify the record on the issuing lab’s site when a lookup exists.',
                    'One PDF reused across storefronts does not prove the purchase is the tested sample.',
                    'Redacting a supplier name can be normal. Missing task numbers, keys, or lot identifiers leaves the report unverified.',
                    'A purity percentage is not sterility, not safety, and not a Peptidemap pass rate.',
                ],
                'faq' => self::faq($host.'/blog/how-to-verify-a-peptide-vendor-certificate-of-analysis#faq', [
                    ['Why do several vendors show the same COA?', 'Shared lot paperwork or copied PDFs. Shared paperwork is not proof your purchase matches the tested sample—lot match plus lab verification are.'],
                    ['The unique key is redacted. Is the COA fake?', 'Not necessarily. Supplier names are often redacted. If verification identifiers are missing and the report will not resolve on the lab tool, treat it as unverified.'],
                    ['Is a PDF enough if it looks professional?', 'No. Prefer independent lookup on the issuing lab’s site and field-by-field comparison.'],
                    ['Vial lot ≠ COA lot — can I use the purity number?', 'Not as documentation for that vial. A genuine report for another lot answers another question.'],
                    ['Does Peptidemap endorse Janoshik?', 'No. Janoshik is one lab with a public verify page common in circulating paperwork. Mention is process literacy, not endorsement.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'cjc-1295-dac-vs-no-dac.md',
                'image_file' => 'cjc-1295-dac-vs-no-dac.png',
                'slug' => 'cjc-1295-dac-vs-no-dac',
                'h1' => 'CJC-1295 DAC vs No DAC: Naming Map, Half-Life Limits, and Blend-Vial Identity',
                'lede' => 'Literature CJC-1295 is the DAC construct. Catalog “no DAC” is usually Modified GRF 1-29. Do not transfer Teichman half-life across that rename.',
                'seo_title' => 'CJC-1295 DAC vs No DAC: Naming Map, Half-Life Limits, and Blend-Vial Identity',
                'seo_description' => 'Literature CJC-1295 is the DAC construct. Catalog “no DAC” is usually Modified GRF 1-29. Do not transfer Teichman half-life across that rename.',
                'og_title' => 'CJC-1295 DAC vs No DAC: Naming Map, Half-Life Limits, and Blend-Vial Identity',
                'og_description' => 'Literature CJC-1295 is the DAC construct. Catalog “no DAC” is usually Modified GRF 1-29. Do not transfer Teichman half-life across that rename.',
                'blog_type' => 'Research',
                'read_time' => '6 Min Read',
                'published_at' => '2026-10-07',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['CJC-1295', 'DAC', 'Modified GRF 1-29', 'Teichman', 'RUO'],
                'key_points' => [
                    'Literature CJC-1295 is the DAC construct. Catalog “no DAC” is the Modified GRF 1-29 lane.',
                    'Teichman et al. 2006 reported a 5.8–8.1 day half-life for that DAC construct. It does not transfer to no-DAC material.',
                    'PubChem lists the DAC construct as C165H269N47O46, 3647.2 g/mol, CAS 446262-90-4, and Modified GRF 1-29 as C152H252N44O42, 3367.9 g/mol, CAS 863288-34-0.',
                    'A CJC-1295 / ipamorelin blend is two receptor lanes in one vial, not proof of synergy.',
                    'This page does not rank the forms and does not give dosing or reconstitution steps.',
                ],
                'faq' => self::faq($host.'/blog/cjc-1295-dac-vs-no-dac#faq', [
                    ['Is “CJC-1295 no DAC” the same molecule as literature CJC-1295?', 'No. Literature CJC-1295 in the Teichman-era PK work is the DAC construct. Catalog “no DAC” is the Modified GRF 1-29 nickname lane.'],
                    ['Can I quote the 5.8–8.1-day half-life for Mod GRF?', 'No. That estimate is from Teichman et al. 2006 for the DAC construct. It does not transfer to no-DAC material.'],
                    ['Does a blend COA only need one peptide name?', 'Ideally no. If two peptides are in the vial, documentation should make clear which analytes were identified and how. Ambiguous “CJC” labeling is a documentation gap.'],
                    ['Which form is better?', 'This page does not rank them. Different identity, different evidence depth, different analytical mass. Research design depends on the hypothesis and the exact material on the certificate—not on a forum winner.'],
                ]),
            ],
            [
                'kind' => 'blog',
                'file' => 'retatrutide-testing-coa-limits.md',
                'image_file' => 'retatrutide-testing-coa-limits.png',
                'slug' => 'retatrutide-testing-coa-limits',
                'h1' => 'Retatrutide Testing and COA Limits: Grey-Market Labels, Buyer Tests, and Victoria’s Alert',
                'lede' => 'What buyer-submitted retatrutide tests and COAs show—and miss. Victoria’s CHO alert quoted carefully. Grey-market labels are not Lilly trial material.',
                'seo_title' => 'Retatrutide Testing and COA Limits: Grey-Market Labels, Buyer Tests, and Victoria’s Alert',
                'seo_description' => 'What buyer-submitted retatrutide tests and COAs show—and miss. Victoria’s CHO alert quoted carefully. Grey-market labels are not Lilly trial material.',
                'og_title' => 'Retatrutide Testing and COA Limits: Grey-Market Labels, Buyer Tests, and Victoria’s Alert',
                'og_description' => 'What buyer-submitted retatrutide tests and COAs show—and miss. Victoria’s CHO alert quoted carefully. Grey-market labels are not Lilly trial material.',
                'blog_type' => 'Research',
                'read_time' => '7 Min Read',
                'published_at' => '2026-10-07',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'COA', 'Victoria CHO', 'Grey market', 'RUO'],
                'key_points' => [
                    'A grey-market retatrutide label is not Lilly trial material and not an approved medicine.',
                    'Buyer-submitted tests are not a market audit. This note does not reprint pass-rate leaderboards.',
                    'A typical identity/purity COA does not establish sterility, endotoxin, the next lot, or safety. A 99% figure does not make a vial safe.',
                    'Victoria’s 19 June 2026 alert reports six acute liver injury cases linked to unapproved products labelled Retatrutide, Reta, R-10, or R-20, with investigation ongoing.',
                    'That alert does not prove the pharmaceutical retatrutide molecule studied by Lilly is hepatotoxic.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-testing-coa-limits#faq', [
                    ['Does a 99% COA make a retatrutide-labeled vial safe?', 'No. Purity/identity of one sample is not sterility, not a contaminant clearance, not next-lot assurance, and not clinical safety.'],
                    ['Does Victoria prove retatrutide is hepatotoxic?', 'No. The CHO alert associates six acute liver injury cases with unapproved products labelled Retatrutide / Reta / R-10 / R-20, notes possible contaminants, and states investigations are ongoing. That is not a completed proof that the trial molecule causes the injuries.'],
                    ['Are buyer-submitted test dashboards a market quality score?', 'No. Sampling and publication are chosen by people with incentives. Useful as scattered data points; not as an audit.'],
                    ['Is a grey-market vial the same as Lilly trial material?', 'No. Lilly states no retatrutide medicine is approved anywhere; grey-market labels are outside that accountability chain.'],
                    ['Where should I read general COA verification steps?', 'How to verify a peptide vendor Certificate of Analysis — including shared-lot skepticism and verify-on-lab-site habits.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this BPC-157 vs GHK-Cu comparison on 2026-10-11.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function bpc157VsGhkCu(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'bpc-157-vs-ghk-cu.md',
                'image_file' => 'bpc-157-vs-ghk-cu.png',
                'slug' => 'bpc-157-vs-ghk-cu',
                'h1' => 'BPC-157 vs GHK-Cu: Identity, Evidence Levels, and Regulatory Status',
                'lede' => 'BPC-157 and GHK-Cu share a skin-and-recovery shelf but not a molecule or an evidence base. Identity, mechanisms as hypotheses, topical vs injectable evidence, FDA and WADA status, and CoA limits.',
                'seo_title' => 'BPC-157 vs GHK-Cu: Identity, Evidence, and FDA Status',
                'seo_description' => 'BPC-157 is mostly rodent data; GHK-Cu human data are mostly topical. Compare identity, evidence, FDA compounding, and WADA status. Educational only.',
                'og_title' => 'BPC-157 vs GHK-Cu: Identity, Evidence, and FDA Status',
                'og_description' => 'BPC-157 is mostly rodent data; GHK-Cu human data are mostly topical. Compare identity, evidence, FDA compounding, and WADA status. Educational only.',
                'blog_type' => 'Research',
                'read_time' => '10 Min Read',
                'published_at' => '2026-10-11',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['BPC-157', 'GHK-Cu', 'Evidence', 'WADA', 'FDA compounding'],
                'key_points' => [
                    'BPC-157 is a synthetic 15-amino-acid peptide. GHK-Cu is the copper complex of Gly-His-Lys. They are unrelated molecules.',
                    'Most published BPC-157 evidence is preclinical. Human reports are a few small uncontrolled pilots, and a 2025 review found no clinical safety data.',
                    'GHK-Cu human data are mostly topical cosmetic studies with mixed results. Those studies do not establish what injection does.',
                    'Neither is an FDA-approved drug. A withdrawn Category 2 nomination is not compounding permission. Category 1 for non-injectable GHK-Cu is interim review status, not a 503A Bulks List listing.',
                    'BPC-157 is named under WADA S0. GHK-Cu is not named. S0 still covers unapproved substances, and the 2027 List adds peptides to its examples.',
                ],
                'faq' => self::faq($host.'/blog/bpc-157-vs-ghk-cu#faq', [
                    ['Are BPC-157 and GHK-Cu related?', 'No. BPC-157 is a synthetic 15-amino-acid peptide. GHK-Cu is the copper complex of the naturally occurring tripeptide Gly-His-Lys. They have different sequences, sizes, and research histories.'],
                    ['How does the human evidence differ?', 'They have different kinds of human evidence. GHK-Cu has small topical cosmetic studies with mixed results. BPC-157 has a few small uncontrolled pilot reports. Neither has controlled trials establishing efficacy for injected use.'],
                    ['Do GHK-Cu skin cream studies apply to injected GHK-Cu?', 'No. Topical studies test a specific formulation on skin. They do not establish safety or effects for injection, and FDA notes limited human data for injectable GHK-Cu.'],
                    ['Is BPC-157 still on FDA\'s Category 2 list?', 'FDA\'s page now lists BPC-157 among nominations withdrawn from Category 2, with its safety concerns kept. A July 2026 advisory committee recommended it for the 503A Bulks List, but as of October 2026 no final FDA listing was identified.'],
                    ['Is GHK-Cu legal because it is in cosmetics?', 'Cosmetic ingredients do not need FDA premarket approval, but intended use decides whether a product is a cosmetic or a drug. A cosmetic serum\'s status does not make an injectable vial an approved drug.'],
                    ['Are they banned in sport?', 'BPC-157 is named under S0 on the WADA 2026 Prohibited List and remains named on the 2027 List. GHK-Cu is not named on either, but S0 covers unapproved substances generally, and the 2027 List adds peptides to its examples, so athletes should check with their anti-doping organization.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this AOD-9604 vs tesamorelin comparison on 2026-10-12.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function aod9604VsTesamorelin(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'aod-9604-vs-tesamorelin.md',
                'image_file' => 'aod-9604-vs-tesamorelin.png',
                'slug' => 'aod-9604-vs-tesamorelin',
                'h1' => 'AOD-9604 vs Tesamorelin: GH Fragment vs GHRH Analog, Evidence, and Status',
                'lede' => 'AOD-9604 and tesamorelin share a "fat loss" shelf but not a class or an evidence tier. An hGH fragment vs a GHRH analog: identity, proposed mechanisms, human trials, FDA, compounding, WADA status, and CoA limits.',
                'seo_title' => 'AOD-9604 vs Tesamorelin: Class, Evidence, and FDA Status',
                'seo_description' => 'AOD-9604 is an hGH fragment whose obesity program ended; tesamorelin is a GHRH analog with one FDA-labeled use. Compare evidence, FDA, and WADA status.',
                'og_title' => 'AOD-9604 vs Tesamorelin: Class, Evidence, and FDA Status',
                'og_description' => 'AOD-9604 is an hGH fragment whose obesity program ended; tesamorelin is a GHRH analog with one FDA-labeled use. Compare evidence, FDA, and WADA status.',
                'blog_type' => 'Research',
                'read_time' => '11 Min Read',
                'published_at' => '2026-10-12',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['AOD-9604', 'Tesamorelin', 'Evidence', 'WADA', 'FDA compounding'],
                'key_points' => [
                    'AOD-9604 is a synthetic 16-amino-acid fragment of hGH (Tyr-hGH 177–191). Tesamorelin is a 44-amino-acid GHRH analog with an N-terminal hexenoyl group.',
                    'Tesamorelin has two published phase 3 trials in adults with HIV and excess abdominal fat. Specific Egrifta products are FDA-approved for that indication only, and the label says it is not indicated for weight-loss management.',
                    'AOD-9604 was developed as an oral obesity drug. Its larger phase 2b trial did not reach statistical significance on weight, and the obesity program was terminated in 2007.',
                    'A developer-reported self-affirmed GRAS determination is not an FDA finding. No FDA GRAS notice was found. Off Category 2 is not compounding permission, and tesamorelin is outside the 503A and 503B exemptions because it is a licensed biologic.',
                    'Both are prohibited at all times on the WADA 2026 and 2027 lists: AOD-9604 as a growth hormone fragment and tesamorelin as a GHRH analog.',
                ],
                'faq' => self::faq($host.'/blog/aod-9604-vs-tesamorelin#faq', [
                    ['Are AOD-9604 and tesamorelin the same kind of peptide?', 'No. AOD-9604 is a 16-amino-acid fragment modeled on the C-terminal end of human growth hormone. Tesamorelin is a modified 44-amino-acid GHRH analog that signals the pituitary to release growth hormone.'],
                    ['Is either FDA-approved?', 'Tesamorelin is approved only as specific Egrifta products for the reduction of excess abdominal fat in HIV-infected adults with lipodystrophy. AOD-9604 is not an approved drug. Research vials of either are not FDA-approved products.'],
                    ['Is AOD-9604 a growth hormone peptide?', 'It is derived from part of the growth hormone sequence, but it is not growth hormone and is not thought to act through the GH receptor. In cell assays it did not compete for the hGH receptor, and WADA classes it as a growth hormone fragment.'],
                    ['How does the human evidence differ?', 'Tesamorelin has two published phase 3 trials in adults with HIV and excess abdominal fat, which led to FDA approval. AOD-9604 has developer-run trials in obese adults, and its larger weight-loss trial did not reach statistical significance.'],
                    ['Are they banned in sport?', 'Yes. Both are named on the WADA 2026 Prohibited List and remain on the 2027 List: AOD-9604 as a growth hormone fragment and tesamorelin as a GHRH analog. Both are prohibited at all times.'],
                    ['Is this medical advice?', 'No. This is educational comparison content from an RUO peptide publisher. It does not give dosing, protocols, or product recommendations, and decisions about treatment belong with a licensed clinician.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this CagriSema vs tirzepatide comparison on 2026-10-19.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function cagrisemaVsTirzepatide(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'cagrisema-vs-tirzepatide.md',
                'image_file' => 'cagrisema-vs-tirzepatide.png',
                'slug' => 'cagrisema-vs-tirzepatide',
                'h1' => 'CagriSema vs Tirzepatide: What REDEFINE 4 Showed, and What It Didn’t',
                'lede' => 'CagriSema pairs an amylin analogue with semaglutide; tirzepatide is a single GIP/GLP-1 agonist. In the open-label REDEFINE 4 trial, CagriSema missed its non-inferiority endpoint vs tirzepatide. Estimand-labelled results, REDEFINE 1 context, safety as published, and FDA status. Educational only.',
                'seo_title' => 'CagriSema vs Tirzepatide: REDEFINE 4 Results and FDA Status',
                'seo_description' => 'Company-reported REDEFINE 4: CagriSema missed non-inferiority vs tirzepatide (23.0% vs 25.5%, efficacy estimand). REDEFINE 1 context, safety, FDA status.',
                'og_title' => 'CagriSema vs Tirzepatide: REDEFINE 4 Results and FDA Status',
                'og_description' => 'CagriSema missed non-inferiority vs tirzepatide in REDEFINE 4 (company-reported). Estimand-labelled results, REDEFINE 1 context, and FDA status.',
                'blog_type' => 'Research',
                'read_time' => '12 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['CagriSema', 'Tirzepatide', 'Cagrilintide', 'REDEFINE 4', 'Estimands'],
                'key_points' => [
                    'CagriSema is a fixed-dose combination of cagrilintide, a long-acting amylin analogue, and semaglutide, a GLP-1 receptor agonist. Tirzepatide is a single GIP and GLP-1 receptor agonist.',
                    'REDEFINE 4 was open-label, ran 84 weeks and randomized 809 adults with obesity. CagriSema did not achieve its primary non-inferiority endpoint vs tirzepatide at its highest maintenance dose.',
                    'REDEFINE 4 at week 84 (company-reported): efficacy estimand 23.0% vs 25.5%; treatment-regimen estimand 20.2% vs 23.6% (CagriSema vs tirzepatide). Arm-level safety and discontinuation data have not been published.',
                    'REDEFINE 1 at week 68, treatment-policy estimand: CagriSema −20.4%, semaglutide alone −14.9%, cagrilintide alone −11.5%, placebo −3.0%. Gastrointestinal adverse events: 79.6% vs 39.9% with placebo.',
                    'CagriSema is not FDA-approved. Novo submitted an NDA on 18 December 2025 and expects an FDA decision in Q4 2026. Tirzepatide is approved as Mounjaro (13 May 2022) and Zepbound (8 November 2023).',
                    'Research-labelled cagrilintide or blends sold online are not CagriSema and are not FDA-approved.',
                ],
                'faq' => self::faq($host.'/blog/cagrisema-vs-tirzepatide#faq', [
                    ['Is CagriSema FDA-approved?', 'No. As of 19 October 2026, CagriSema is investigational and not FDA-approved. Novo Nordisk submitted a New Drug Application for weight management on 18 December 2025, and its 21 September 2026 announcement says an FDA decision is expected in the fourth quarter of 2026. That is an expected timeframe, not an outcome.'],
                    ['What did REDEFINE 4 compare?', 'REDEFINE 4 was an open-label, 84-week phase 3 trial that randomized 809 adults with obesity and at least one comorbidity to once-weekly CagriSema or once-weekly tirzepatide at its highest maintenance dose. The primary endpoint was non-inferiority of CagriSema on percent weight change at week 84.'],
                    ['Did CagriSema meet its primary endpoint against tirzepatide?', 'No. In company-reported headline results, Novo Nordisk said non-inferiority was not achieved. Mean weight reduction was 23.0% with CagriSema vs 25.5% with tirzepatide under the efficacy estimand, and 20.2% vs 23.6% under the treatment-regimen estimand. Those figures are from one head-to-head trial and should not be compared with numbers from other trials.'],
                    ['Why are there two sets of numbers for each trial?', 'They answer different questions. In REDEFINE 4, the efficacy estimand estimates the effect if everyone had stayed on treatment, and the treatment-regimen estimand counts everyone regardless of adherence. REDEFINE 1 uses the names trial-product and treatment-policy for two analogous questions. A number without its estimand label is easy to misread.'],
                    ['How is CagriSema different from tirzepatide?', 'CagriSema is a fixed-dose combination of two molecules: cagrilintide, a long-acting amylin analogue, and semaglutide, a GLP-1 receptor agonist. Tirzepatide is a single peptide that activates both GIP and GLP-1 receptors. The two products reach appetite pathways by different routes.'],
                    ['What were the main side effects reported?', 'In REDEFINE 4, Novo Nordisk said the most common adverse events with CagriSema were gastrointestinal and mostly mild to moderate, but it has not published arm-level adverse event or discontinuation figures. In REDEFINE 1, gastrointestinal adverse events were reported in 79.6% of the CagriSema group vs 39.9% with placebo, and 5.9% vs 3.5% stopped treatment permanently because of adverse events.'],
                    ['Is research-labelled cagrilintide the same as CagriSema?', 'No. Research-labelled cagrilintide vials or blends sold online are not CagriSema, are not the product studied in the REDEFINE trials, and are not FDA-approved. This article is educational and is not medical advice.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this orforglipron vs oral semaglutide comparison on 2026-10-19.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function orforglipronVsOralSemaglutide(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'orforglipron-vs-oral-semaglutide.md',
                'image_file' => 'orforglipron-vs-oral-semaglutide.png',
                'slug' => 'orforglipron-vs-oral-semaglutide',
                'h1' => 'Orforglipron vs Oral Semaglutide: What the ACHIEVE-3 Head-to-Head Trial Reported',
                'lede' => 'Orforglipron is a non-peptide small-molecule GLP-1 pill; oral semaglutide is a peptide tablet made with the absorption enhancer SNAC. ACHIEVE-3 compared them for 52 weeks in type 2 diabetes. Estimand-labelled HbA1c and weight results, tolerability as published, and FDA status. Educational only.',
                'seo_title' => 'Orforglipron vs Oral Semaglutide: ACHIEVE-3 and FDA Status',
                'seo_description' => 'ACHIEVE-3 compared orforglipron with oral semaglutide in type 2 diabetes for 52 weeks. HbA1c and weight by estimand, GI events, and FDA status.',
                'og_title' => 'Orforglipron vs Oral Semaglutide: ACHIEVE-3 and FDA Status',
                'og_description' => 'Orforglipron vs oral semaglutide in the 52-week ACHIEVE-3 trial: estimand-labelled HbA1c and weight results, tolerability, and US FDA status.',
                'blog_type' => 'Research',
                'read_time' => '13 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Orforglipron', 'Oral Semaglutide', 'Rybelsus', 'Foundayo', 'ACHIEVE-3', 'Estimands'],
                'key_points' => [
                    'Orforglipron is a non-peptide small-molecule GLP-1 receptor agonist taken with or without food. Oral semaglutide is a peptide GLP-1 receptor agonist co-formulated with the absorption enhancer SNAC and taken fasting.',
                    'ACHIEVE-3 was a 52-week, open-label phase 3 trial in 1,698 adults with type 2 diabetes on metformin. Oral semaglutide was tested only at the two Rybelsus maintenance doses for type 2 diabetes.',
                    'HbA1c change, treatment-regimen estimand (Lancet primary): −1.71% and −1.91% with orforglipron vs −1.23% and −1.47% with oral semaglutide. Non-inferiority was met and both orforglipron arms were superior to both semaglutide arms.',
                    'Lilly headlines use the efficacy estimand: HbA1c −1.9% and −2.2% vs −1.1% and −1.4%; weight −6.7% and −9.2% vs −3.7% and −5.3%. Treatment-regimen weight (company-reported): −6.1% and −8.2% vs −3.9% and −5.3%.',
                    'Gastrointestinal adverse events: 59% and 58% with orforglipron vs 37% and 45%. Discontinuation due to adverse events: 8.7% and 9.7% vs 4.5% and 4.9%. The study was not powered for safety comparisons.',
                    'Rybelsus was FDA-approved for type 2 diabetes on 20 September 2019. Orforglipron was approved as Foundayo for weight management on 1 April 2026; its US type 2 diabetes submission is pending.',
                ],
                'faq' => self::faq($host.'/blog/orforglipron-vs-oral-semaglutide#faq', [
                    ['What did ACHIEVE-3 compare?', 'ACHIEVE-3 was a 52-week, open-label, randomized phase 3 trial in 1,698 adults with type 2 diabetes not controlled on metformin. It compared two once-daily orforglipron dose arms with two once-daily oral semaglutide dose arms. Oral semaglutide was tested only at the two Rybelsus maintenance doses for type 2 diabetes.'],
                    ['Was orforglipron non-inferior or superior on HbA1c?', 'Both. The Lancet paper reports that the primary non-inferiority objective was met and that both orforglipron arms were superior to both oral semaglutide arms on HbA1c change at week 52. Under the treatment-regimen estimand, HbA1c fell by 1.71% and 1.91% with orforglipron vs 1.23% and 1.47% with oral semaglutide.'],
                    ['Why do Lilly\'s numbers differ from the Lancet abstract?', 'They use different estimands. The Lancet paper\'s primary estimand is the treatment-regimen estimand, which counts all randomized participants regardless of stopping treatment. Lilly\'s headline figures use the efficacy estimand, which estimates the effect had everyone stayed on treatment. For example, the higher orforglipron arm\'s HbA1c change is 1.91% under the treatment-regimen estimand and 2.2% under the efficacy estimand.'],
                    ['How much weight change was reported?', 'Under the efficacy estimand, mean weight change was 6.7% and 9.2% with orforglipron vs 3.7% and 5.3% with oral semaglutide. Under the treatment-regimen estimand, company-reported by Lilly, the figures were 6.1% and 8.2% vs 3.9% and 5.3%. Baseline weight averaged 97.0 kg.'],
                    ['Did orforglipron have more side effects?', 'Yes, in this trial. Gastrointestinal adverse events were reported in 59% and 58% of the orforglipron arms vs 37% and 45% of the oral semaglutide arms. Discontinuation due to adverse events was 8.7% and 9.7% vs 4.5% and 4.9%, roughly twice as high. Mean pulse rate rose more with orforglipron.'],
                    ['How do the two drugs differ chemically?', 'Orforglipron is a non-peptide small-molecule GLP-1 receptor agonist taken once daily with or without food. Oral semaglutide is a peptide GLP-1 receptor agonist formulated with the absorption enhancer SNAC, and its label requires it to be taken on an empty stomach with a little water and a wait before eating.'],
                    ['Are both FDA-approved?', 'Rybelsus (oral semaglutide) was approved on 20 September 2019 for type 2 diabetes. Orforglipron was approved as Foundayo on 1 April 2026 for weight management only. Lilly reported submitting it to the FDA for type 2 diabetes in the second quarter of 2026, and as of 19 October 2026 no FDA diabetes approval was found. Research-labelled orforglipron sold online is not Foundayo and is not FDA-approved.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this compounded GLP-1 status note on 2026-10-19.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function compoundedGlp1Status(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'compounded-glp-1-status.md',
                'image_file' => 'compounded-glp-1-status.png',
                'slug' => 'compounded-glp-1-status',
                'h1' => 'Compounded GLP-1 Status: Shortage Resolution, Grace Periods and What FDA Did Next',
                'lede' => 'FDA resolved the tirzepatide shortage on 19 December 2024 and the semaglutide shortage on 21 February 2025. The dated 503A and 503B grace periods, the essentially-a-copy and bulks rules, the OFA v. FDA rulings, and FDA’s 2026 actions, from primary sources. Educational only.',
                'seo_title' => 'Compounded GLP-1 Status: Shortages, Courts, 2026 FDA Moves',
                'seo_description' => 'Dated timeline of FDA’s tirzepatide and semaglutide shortage decisions, 503A/503B grace periods, OFA v. FDA rulings and 2026 GLP-1 compounding actions.',
                'og_title' => 'Compounded GLP-1 Status: Shortages, Courts, 2026 FDA Moves',
                'og_description' => 'How the tirzepatide and semaglutide shortages ended, the 503A and 503B wind-down dates, the court rulings, and FDA’s 2026 GLP-1 compounding actions.',
                'blog_type' => 'Research',
                'read_time' => '14 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Compounding', 'GLP-1', 'Semaglutide', 'Tirzepatide', 'FDA', '503A', '503B'],
                'key_points' => [
                    'FDA found the tirzepatide injection shortage resolved on 2 October 2024, re-evaluated on remand, and confirmed it in a declaratory order on 19 December 2024. The semaglutide injection shortage was resolved on 21 February 2025.',
                    'Tirzepatide grace periods: 503A ended when a preliminary injunction was denied on 5 March 2025; 503B ended 19 March 2025. Semaglutide: 503A ended when an injunction was denied on 24 April 2025; 503B ended 22 May 2025.',
                    'After a shortage ends, 503A bars compounding essentially copies of a commercially available drug regularly or in inordinate amounts. 503B requires the bulks list or the shortage list, and FDA says tirzepatide and semaglutide are on neither.',
                    'The Fifth Circuit affirmed judgment for FDA in both OFA v. FDA cases on 27 August 2026, in unpublished opinions.',
                    'In 2026 FDA announced intent to restrict GLP-1 APIs for mass-marketed compounded drugs (6 Feb), issued 30 telehealth warning letters (3 Mar), and proposed excluding semaglutide, tirzepatide and liraglutide from the 503B bulks list (30 Apr), which is not yet final.',
                    'Compounded drugs are not FDA-approved, and a research-use-only label is neither an approval nor a compounding pathway.',
                ],
                'faq' => self::faq($host.'/blog/compounded-glp-1-status#faq', [
                    ['When did FDA resolve the tirzepatide and semaglutide shortages?', 'FDA first announced the tirzepatide injection shortage resolved on 2 October 2024. After litigation, it re-evaluated that decision and issued a declaratory order on 19 December 2024 confirming the shortage was resolved. FDA determined the semaglutide injection shortage resolved on 21 February 2025.'],
                    ['What grace periods did FDA give compounders?', 'For tirzepatide, FDA said it would not act on shortage-dependent violations until 18 February 2025 for 503A pharmacies and physicians and 19 March 2025 for 503B outsourcing facilities, later tied to a court ruling. The 503A period ended when the court denied a preliminary injunction on 5 March 2025. For semaglutide, the dates were 22 April 2025 for 503A and 22 May 2025 for 503B. The 503A period ended when a preliminary injunction was denied on 24 April 2025.'],
                    ['Does the end of a shortage end all compounding of these drugs?', 'Not entirely, but it removes the shortage pathway. Under 503A, a pharmacy may not compound essentially a copy of a commercially available drug regularly or in inordinate amounts, and FDA generally treats a drug as commercially available once its shortage is resolved. Under 503B, outsourcing facilities cannot compound from bulk substances unless the substance is on the 503B bulks list or the drug is on the shortage list, and FDA says tirzepatide and semaglutide are on neither.'],
                    ['What happened in the OFA v. FDA lawsuits?', 'Compounders challenged both delisting decisions in the Northern District of Texas. The court denied preliminary injunctions in the tirzepatide case on 5 March 2025 and the semaglutide case on 24 April 2025, then entered summary judgment for FDA and the manufacturers. On 27 August 2026 the Fifth Circuit affirmed both judgments in unpublished opinions.'],
                    ['What has FDA done on GLP-1 compounding in 2026?', 'On 6 February 2026 FDA announced its intent to restrict GLP-1 active ingredients intended for mass-marketed compounded drugs. On 3 March 2026 it announced 30 warning letters to telehealth companies over compounded GLP-1 claims. On 30 April 2026 it proposed not to include semaglutide, tirzepatide and liraglutide on the 503B bulks list; the comment period closed on 30 July 2026 and no final determination was found as of 19 October 2026.'],
                    ['What does FDA say about dosing errors and adverse events?', 'FDA\'s page on unapproved GLP-1 drugs says it received adverse event reports, some involving hospitalization, that may be related to dosing errors with compounded injectable semaglutide, and reports tied to doses beyond the approved labels. As of 31 May 2026 it had received 990 reports for compounded semaglutide and more than 730 for compounded tirzepatide. FDA notes these are likely underreported and causation is not always clear.'],
                    ['Is research-labelled semaglutide or tirzepatide the same as compounded?', 'No. Compounding is a legal pathway with conditions under sections 503A and 503B. A research-use-only label is not a compounding pathway or an approval. FDA says it has warned companies that sold semaglutide, tirzepatide and other GLP-1 drugs falsely labelled for research purposes or not for human consumption. This article is educational and is not medical or legal advice.'],
                ]),
            ],
        ];
    }

    /**
     * Content QA passed this retatrutide side-effects trial-data note on 2026-10-19.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function retatrutideSideEffectsTrialData(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'retatrutide-side-effects-trial-data.md',
                'image_file' => 'retatrutide-side-effects-trial-data.png',
                'slug' => 'retatrutide-side-effects-trial-data',
                'h1' => 'Retatrutide Side Effects: What the Trial Publications Report',
                'lede' => 'Adverse events from the retatrutide phase 2 papers, TRIUMPH-2 and company-reported TRIUMPH data: GI events, discontinuation, heart rate and dysesthesia.',
                'seo_title' => 'Retatrutide Side Effects: GI, Heart Rate, Dysesthesia Data',
                'seo_description' => 'Retatrutide adverse events as reported in phase 2 papers, TRIUMPH-2 and Lilly data: GI events, discontinuation, heart rate, dysesthesia. Educational only.',
                'og_title' => 'Retatrutide Side Effects: GI, Heart Rate, Dysesthesia Data',
                'og_description' => 'Retatrutide adverse events as reported in phase 2 papers, TRIUMPH-2 and Lilly data: GI events, discontinuation, heart rate, dysesthesia. Educational only.',
                'blog_type' => 'Research',
                'read_time' => '19 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'Adverse events', 'TRIUMPH', 'Clinical trials', 'RUO'],
                'key_points' => [
                    'Gastrointestinal events were the most common adverse events in every retatrutide trial reviewed, and were dose-related in the phase 2 papers.',
                    'Discontinuation due to adverse events in the TRIUMPH-2 paper was 4%, 12% and 8% across the lowest, middle and highest dose arms vs 5% with placebo.',
                    'Both phase 2 papers report a heart-rate rise that peaked and then declined during treatment; phase 3 heart-rate data were not in the sources reviewed.',
                    'Phase 3 trials report dysesthesia, which Lilly defines as skin burning sensation and related events; mechanism, onset and duration are not characterized.',
                    'Several phase 3 figures are company-reported, not peer reviewed, and are labelled that way.',
                    'Research-labelled retatrutide is not the trial drug, is not approved, and is not described by these data.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-side-effects-trial-data#faq', [
                    ['What were the most common side effects in the retatrutide trials?', 'Gastrointestinal events: nausea, diarrhoea, vomiting, constipation and decreased appetite. In both phase 2 papers they were dose-related and mostly mild to moderate, and the obesity paper says they occurred mainly during dose escalation. In the TRIUMPH-2 paper, diarrhoea was reported in 27% to 34% of the retatrutide arms vs 13% with placebo, and nausea in 14% to 28% vs 8%.'],
                    ['How many people stopped retatrutide because of adverse events?', 'In the phase 2 obesity trial, 6% to 16% of the retatrutide arms vs none on placebo. In the phase 2 type 2 diabetes trial, 16 of 190 retatrutide participants (8%) vs 2 of 45 on placebo (4%). In the TRIUMPH-2 paper, permanent discontinuation due to adverse events or death was 4%, 12% and 8% in the lowest, middle and highest dose arms vs 5% with placebo. Company-reported TRIUMPH-1 figures were 4.1% to 11.3% vs 4.9%.'],
                    ['Does retatrutide raise heart rate?', 'In the phase 2 obesity paper, heart rate rose in a dose-dependent manner, peaked at 24 weeks and declined at 36 and 48 weeks. In the phase 2 type 2 diabetes paper, pulse rose by up to about 7 beats per minute early in treatment and had decreased by 36 weeks. Heart-rate time courses for the phase 3 TRIUMPH trials were not available in the sources reviewed.'],
                    ['What is the dysesthesia reported in the TRIUMPH trials?', 'Lilly defines it as a skin burning sensation and related adverse events. In the TRIUMPH-2 paper it was reported in 4% to 7% of the retatrutide arms vs 1% with placebo. The TRIUMPH-1 rate of up to 12.5% is reported in the paper and company-reported by Lilly. Company-reported rates were up to 20.9% in TRIUMPH-4. Lilly says events were generally mild to moderate and most resolved during treatment. The cause, timing and duration have not been characterized in the sources reviewed.'],
                    ['Were pancreatitis, gallbladder or hypoglycaemia events reported?', 'In the phase 2 papers these events were few. The obesity trial reported one acute pancreatitis, three biliary events and no clinically significant hypoglycaemia. The type 2 diabetes trial reported two adjudicated pancreatitis events and three participants with hypoglycaemia below 54 mg/dL, none severe. Phase 3 rates for these events were not available in the published abstracts reviewed.'],
                    ['Is research-labelled retatrutide the same as the trial drug?', 'No. Retatrutide is investigational and not approved anywhere. Lilly states it cannot be legally sold or marketed for human use. Products sold online as research-labelled retatrutide are not the material used in these trials, and the trial safety data do not describe them.'],
                    ['Can these trials be compared with trials of other drugs?', 'Not reliably. The trials differ in population, duration, dose escalation and how events were defined and counted. This article reports results within each trial only and does not rank retatrutide against other drugs.'],
                ]),
            ],
        ];
    }

    /**
     * MOTS-c vs NAD+ evidence and regulatory comparison, drafted 2026-10-19.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML. Companion to /compare/mots-c-vs-nad.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function motsCVsNad(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'mots-c-vs-nad.md',
                'image_file' => 'mots-c-vs-nad.png',
                'slug' => 'mots-c-vs-nad',
                'h1' => 'MOTS-c vs NAD+: A Mitochondrial Peptide and a Coenzyme, Compared on the Evidence',
                'lede' => 'MOTS-c is a 16-amino-acid mitochondrial-derived peptide; NAD+ is a coenzyme, not a peptide. Mechanisms, evidence graded from human RCTs to animal-only work, NR and NMN vs direct NAD+, and dated FDA compounding status. Educational only.',
                'seo_title' => 'MOTS-c vs NAD+: Evidence, Mechanisms and FDA Status',
                'seo_description' => 'MOTS-c has animal data and no published human trials; NAD+ precursors have RCTs with mostly null outcomes. Mechanisms, evidence grades and FDA status.',
                'og_title' => 'MOTS-c vs NAD+: What Human and Animal Studies Show',
                'og_description' => 'A mitochondrial peptide vs a coenzyme: graded evidence, NR and NMN trials including NOPARK, and dated FDA Category 1 and 2 compounding status.',
                'blog_type' => 'Research',
                'read_time' => '20 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['MOTS-c', 'NAD+', 'Nicotinamide Riboside', 'NMN', 'Mitochondrial-Derived Peptides', 'FDA compounding', 'Category 2'],
                'key_points' => [
                    'MOTS-c is a 16-amino-acid peptide encoded in mitochondrial DNA. NAD+ is a coenzyme made from vitamin B3 forms and tryptophan; it is not a peptide.',
                    'Every proposed MOTS-c effect, from insulin sensitivity to exercise capacity (plus a non-significant lifespan trend), comes from mouse, rat or cell studies (animal-only). FDA identified no published human trials of MOTS-c; one phase 2a trial (NCT07505745) is recruiting.',
                    'Most human NAD+ research gives the precursors NR or NMN, not NAD+ itself. Oral precursors consistently raise NAD+ metabolites, but clinical outcomes are mixed and mostly null.',
                    'In the phase 3 NOPARK trial of nicotinamide riboside (JAMA, 8 October 2026), the primary Parkinson disease score at 52 weeks modestly favoured placebo, by 2.72 points, less than the trial\'s prespecified clinically important worsening of 5.2 points. NR is a precursor, not NAD+ itself.',
                    'MOTS-c entered 503A Category 2 on 29 September 2023; the nomination was later withdrawn. FDA staff proposed not adding it to the 503A Bulks List, and the July 2026 PCAC recommended it anyway. No final listing as of 19 October 2026.',
                    'NAD and NADH sit in 503A Category 1 (under evaluation), which is enforcement discretion, not approval. Research-labelled vials of either are not compounded, supplement or approved products.',
                ],
                'faq' => self::faq($host.'/blog/mots-c-vs-nad#faq', [
                    ['Is NAD+ a peptide like MOTS-c?', 'No. NAD+ (nicotinamide adenine dinucleotide) is a coenzyme made from vitamin B3 forms and tryptophan, used by hundreds of enzymes in every cell. MOTS-c is a 16-amino-acid peptide encoded in mitochondrial DNA. They are often listed together, but they are different kinds of molecule.'],
                    ['Has MOTS-c been tested in human clinical trials?', 'No published trial has given MOTS-c to people. FDA\'s May 2026 evaluation identified no clinical studies or human exposure data. A phase 2a placebo-controlled trial in adults with prediabetes (NCT07505745) is registered on ClinicalTrials.gov as recruiting, with no results posted. Human MOTS-c data so far measure the body\'s own peptide, for example after exercise.'],
                    ['Do NR and NMN raise NAD+ in people?', 'Yes, as a biomarker. Randomized trials of nicotinamide riboside and nicotinamide mononucleotide consistently show higher NAD+ metabolites in blood or muscle. A 2026 systematic review of 33 human intervention studies described this as consistent target engagement. Raising a biomarker is not the same as improving a health outcome.'],
                    ['Did NAD+ precursors improve health outcomes in trials?', 'Results are mixed and mostly null. NR did not improve insulin sensitivity in obese men, while one small trial found NMN improved muscle insulin sensitivity in women with prediabetes. In the phase 3 NOPARK trial, published in October 2026, the primary Parkinson disease score at 52 weeks modestly favoured placebo over NR, by 2.72 points, less than the trial\'s prespecified clinically important worsening of 5.2 points. A 2025 meta-analysis found no benefit of NMN for muscle mass or strength in older adults.'],
                    ['Is MOTS-c on FDA\'s Category 2 list?', 'Not any more. FDA added MOTS-c to 503A Category 2 on 29 September 2023. After the nominator withdrew its nomination, FDA listed it among withdrawn substances previously in Category 2 and kept its safety concerns. FDA staff proposed not adding MOTS-c to the 503A Bulks List. The July 2026 advisory committee recommended it anyway, but as of 19 October 2026 no final FDA listing was identified. Leaving Category 2 is not permission to compound.'],
                    ['What is NAD+\'s compounding status?', 'FDA\'s 503A categories list, updated 14 May 2026, places NAD and NADH in Category 1, meaning under evaluation. Category 1 is an interim enforcement policy for 503A compounders, not FDA approval and not a listing on the 503A Bulks List. No FDA-approved drug with NAD+ as its active ingredient was found.'],
                    ['Has anyone studied MOTS-c and NAD+ together?', 'We found no interventional study combining or comparing them in searches of PubMed and ClinicalTrials.gov as of 19 October 2026. Claims that they work together or should be stacked are not based on a direct test.'],
                    ['Is research-labelled MOTS-c or NAD+ the same as a compounded or approved product?', 'No. A research-use-only label is not a compounding pathway, a dietary supplement status or an FDA approval. Research-labelled vials are not the NR or NMN products used in most human trials. This article is educational and is not medical advice.'],
                ]),
            ],
        ];
    }

    /**
     * Ipamorelin vs sermorelin evidence and FDA-status comparison. Drafted 2026-10-19;
     * sources checked 2026-10-19. Companions /compare/ipamorelin-vs-sermorelin.
     * The body lives in resources/content/educational. The publisher reads
     * this catalog, not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function ipamorelinVsSermorelin(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'ipamorelin-vs-sermorelin.md',
                'image_file' => 'ipamorelin-vs-sermorelin.png',
                'slug' => 'ipamorelin-vs-sermorelin',
                'h1' => 'Ipamorelin vs Sermorelin: Receptors, Evidence and FDA Status',
                'lede' => 'Sermorelin is a GHRH(1-29) analogue FDA once approved as GEREF. Ipamorelin is a ghrelin-receptor agonist that stopped at phase 2. Mechanism, trials, evidence grades and dated FDA compounding status, from primary sources. Educational only.',
                'seo_title' => 'Ipamorelin vs Sermorelin: Mechanism, Evidence, FDA Status',
                'seo_description' => 'Sermorelin is a GHRH(1-29) analogue once approved as GEREF; ipamorelin is a ghrelin-receptor agonist never approved. Trials and FDA status compared.',
                'og_title' => 'Ipamorelin vs Sermorelin: Mechanism, Evidence, FDA Status',
                'og_description' => 'GHRH receptor vs ghrelin receptor: GEREF approval and 2009 withdrawal, ipamorelin ileus trials and PCAC votes, and the evidence grades for each.',
                'blog_type' => 'Research',
                'read_time' => '20 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Ipamorelin', 'Sermorelin', 'GEREF', 'GHRH', 'Ghrelin receptor', 'Compounding'],
                'key_points' => [
                    'Sermorelin is GHRH(1-29)-amide and acts at the GHRH receptor. Ipamorelin is a synthetic pentapeptide that acts at the ghrelin receptor, GHS-R1a.',
                    'Raun et al. 1998 reported that ipamorelin did not raise ACTH or cortisol to levels significantly different from those after GHRH in swine, unlike GHRP-6 and GHRP-2. That selectivity is an animal finding.',
                    'GEREF was approved on 28 December 1990 (NDA 19-863) and 26 September 1997 (NDA 20-443). Approvals were withdrawn effective 18 June 2009, and FDA found in 2013 they were not withdrawn for safety or effectiveness.',
                    'The published ipamorelin ileus trial (NCT00672074) found no significant difference from placebo. A second phase 2 trial (NCT01280344) has no posted or published results.',
                    'Ipamorelin acetate has been in 503B Category 2 since 29 September 2023, and PCAC voted against 503A bulks listing on 29 October 2024. Sermorelin acetate is in 503B Category 1, marked as a component of an approved drug.',
                    'Neither compound was on the July 2026 PCAC agenda. No head-to-head human trial was found, and this page does not rank them.',
                ],
                'faq' => self::faq($host.'/blog/ipamorelin-vs-sermorelin#faq', [
                    ['Are ipamorelin and sermorelin the same kind of peptide?', 'No. Sermorelin is GHRH(1-29)-amide, the first 29 amino acids of human growth hormone-releasing hormone, and it acts at the GHRH receptor. Ipamorelin is a synthetic five-residue peptide that acts at the ghrelin receptor, GHS-R1a. Both lead to growth hormone release from the pituitary, but through different receptors.'],
                    ['Is sermorelin FDA-approved?', 'Not currently. FDA approved GEREF (sermorelin acetate) under NDA 19-863 on 28 December 1990 for diagnostic pituitary testing and under NDA 20-443 on 26 September 1997 for idiopathic growth hormone deficiency in children with growth failure. EMD Serono discontinued both products in 2008. FDA withdrew the approvals effective 18 June 2009. In 2013 FDA determined they were not withdrawn for reasons of safety or effectiveness. No approved sermorelin generic was found in Drugs@FDA.'],
                    ['Has ipamorelin ever been FDA-approved?', 'No. Ipamorelin reached phase 2 trials for postoperative ileus. The published trial, NCT00672074, found no significant difference from placebo on its key and secondary efficacy endpoints. A second phase 2 trial, NCT01280344, was completed in 2014 but has no posted or published results.'],
                    ['What does "selective" mean for ipamorelin?', 'It comes from a 1998 study in rats and swine. Ipamorelin released growth hormone about as potently as GHRP-6. Unlike GHRP-6 and GHRP-2, it did not raise ACTH or cortisol to levels significantly different from those after GHRH, even at doses more than 200-fold above the ED50 for GH release. This was selectivity compared with older GHRPs, in animals. It was not tested against sermorelin in humans.'],
                    ['Can ipamorelin be compounded?', 'FDA lists ipamorelin acetate in 503B Category 2 for significant safety risks, effective 29 September 2023. On 29 October 2024, FDA\'s Pharmacy Compounding Advisory Committee voted 0 yes, 12 no and 1 abstain on adding each of ipamorelin free base and ipamorelin acetate to the 503A bulks list, after FDA proposed against it. It is not on the 503A bulks list. Ipamorelin was not on the July 2026 PCAC agenda.'],
                    ['Where does sermorelin stand for compounding?', 'Sermorelin is not on the 503A bulks list and does not appear in FDA\'s 503A category list. FDA\'s 503B list places sermorelin acetate in Category 1 and marks it as a component of an FDA-approved drug. FDA\'s 503A page lists components of FDA-approved drug products as one permitted source of bulk drug substances. FDA has not published a sermorelin-specific statement on how this applies to a drug whose approval was withdrawn, and absence from FDA\'s 503A lists is not, by itself, an FDA statement either way. Whether a specific compounded product qualifies is a legal question this article does not answer.'],
                    ['Which one is better?', 'This page does not rank them. They act on different receptors, were studied in different populations for different uses, and have no head-to-head human trial. Neither has controlled human evidence for the anti-ageing or recovery uses common in marketing.'],
                    ['Is a research-use-only vial the same as GEREF or a trial drug?', 'No. GEREF was a specific FDA-approved finished product, now discontinued, and the ipamorelin trial material was a sponsor\'s investigational drug used under a protocol. A research-use-only vial is neither, and none of the data on this page describe its identity, purity or sterility.'],
                ]),
            ],
        ];
    }

    /**
     * Retatrutide vs semaglutide comparison (mechanism, trial data by estimand, side effects, regulatory status).
     * Body lives in resources/content/educational/retatrutide-vs-semaglutide.md.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function retatrutideVsSemaglutide(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'retatrutide-vs-semaglutide.md',
                'image_file' => 'retatrutide-vs-semaglutide.png',
                'slug' => 'retatrutide-vs-semaglutide',
                'h1' => 'Retatrutide vs Semaglutide: Mechanism, Trial Data and Regulatory Status',
                'lede' => 'Retatrutide is an investigational GIP, GLP-1 and glucagon receptor agonist; semaglutide is an FDA-approved GLP-1 receptor agonist sold as Ozempic, Wegovy and Rybelsus. No head-to-head trial has reported. Estimand-labelled TRIUMPH and STEP results, side effects as published, and regulatory status. Educational only.',
                'seo_title' => 'Retatrutide vs Semaglutide: Trials, Side Effects, FDA Status',
                'seo_description' => 'Retatrutide vs semaglutide: triple vs single receptor agonist, TRIUMPH and STEP results by estimand, side effects as reported, and FDA status.',
                'og_title' => 'Retatrutide vs Semaglutide: Trials, Side Effects, FDA Status',
                'og_description' => 'No head-to-head trial has reported yet. Retatrutide and semaglutide compared on mechanism, trial data by estimand, side effects and FDA status.',
                'blog_type' => 'Research',
                'read_time' => '18 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'Semaglutide', 'Wegovy', 'Ozempic', 'TRIUMPH', 'Estimands'],
                'key_points' => [
                    'Retatrutide is an investigational single peptide that activates GIP, GLP-1 and glucagon receptors. Semaglutide is a GLP-1 receptor agonist approved as Ozempic (injection and tablets), Rybelsus and Wegovy.',
                    'No head-to-head trial has reported. TRANSCEND-T2D-2 (NCT06260722), an open-label phase 3 trial vs once-weekly semaglutide in type 2 diabetes, was active, not recruiting, with no results as of 19 October 2026.',
                    'TRIUMPH-1 (NEJM 2026), treatment-regimen estimand at week 80: −17.6%, −23.7% and −25.0% vs −3.9% with placebo. The −28.3% figure for the highest dose arm under the efficacy estimand is reported in the paper and company-reported by Lilly.',
                    'STEP 1 (NEJM 2021) at week 68: −14.9% vs −2.4% (treatment-policy estimand) and −16.9% vs −2.4% (trial-product estimand). SELECT showed fewer major cardiovascular events with semaglutide (HR 0.80).',
                    'Trials differ in population, duration and estimand and were not head to head, so figures from different trials are not ranked. Gastrointestinal events were the most common adverse events for both drugs.',
                    'Retatrutide is not FDA- or EMA-approved; Lilly plans a US submission in Q1 2027. Research-labelled retatrutide or semaglutide is not the trial drug or an approved product.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-vs-semaglutide#faq', [
                    ['Has retatrutide been compared head to head with semaglutide?', 'Not with results. TRANSCEND-T2D-2 (NCT06260722) is a phase 3, open-label trial comparing two retatrutide dose arms with once-weekly semaglutide in about 1,250 adults with type 2 diabetes, with HbA1c change at week 80 as the primary endpoint. As of 19 October 2026 it was active, not recruiting, with no results posted or published.'],
                    ['How do retatrutide and semaglutide work differently?', 'Semaglutide is a modified GLP-1 analogue that activates the GLP-1 receptor only. Retatrutide is a single peptide that activates GIP, GLP-1 and glucagon receptors. Researchers have proposed that the glucagon component may add effects on energy intake or energy expenditure, but this has not been established in humans, and a different mechanism does not by itself make a drug more effective or safer.'],
                    ['Can the TRIUMPH and STEP weight results be compared directly?', 'No. They are separate placebo-controlled trials that differ in population, duration, era, dose escalation and estimand definitions, and none compared the two drugs with each other. This article reports results within each trial only and does not rank the two drugs.'],
                    ['Why do some retatrutide figures look higher in news coverage?', 'They usually use a different estimand. In TRIUMPH-1, mean weight change in the highest dose arm was 25.0% under the treatment-regimen estimand in the NEJM paper and 28.3% under the efficacy estimand, reported in the paper and company-reported by Lilly. The first counts everyone randomized. The second estimates the effect had everyone stayed on treatment.'],
                    ['What side effects were reported in the trials?', 'For both drugs, gastrointestinal events such as nausea, diarrhoea, vomiting and constipation were the most common. In the TRIUMPH-2 paper, diarrhoea was reported in 27% to 34% of the retatrutide arms vs 13% with placebo, and dysesthesia and hypotension were more frequent than with placebo. In STEP 1, gastrointestinal disorders were reported in 74.2% of the semaglutide group vs 47.9% with placebo.'],
                    ['Is retatrutide FDA-approved?', 'No. As of 19 October 2026, retatrutide is investigational and not approved by the FDA or the EMA. Lilly says it plans to submit it for US approval in the first quarter of 2027. The FDA has determined that retatrutide is a drug rather than a biological product, a classification Lilly is challenging in court.'],
                    ['What is semaglutide approved for?', 'In the US, Ozempic (injection and tablets) and Rybelsus are approved for type 2 diabetes, with cardiovascular risk-reduction indications and, for Ozempic injection, a kidney-outcomes indication. Wegovy injection is approved for weight management in adults and adolescents aged 12 and older, cardiovascular risk reduction in adults with established cardiovascular disease and obesity or overweight, and MASH with moderate to advanced fibrosis. Wegovy tablets were approved on 22 December 2025.'],
                    ['Is research-labelled retatrutide or semaglutide the same as the trial drugs?', 'No. Products sold online as research-use-only retatrutide or semaglutide are not the material used in these trials and are not FDA-approved products. The FDA says retatrutide cannot be used in compounding and has warned companies selling GLP-1 drugs falsely labelled for research purposes. This article is educational and is not medical advice.'],
                ]),
            ],
        ];
    }

    /**
     * Selank vs Semax evidence and FDA-status comparison. Drafted 2026-10-19;
     * sources checked 2026-10-19. Companion to /compare/selank-vs-semax
     * (pair page) and /compare/selanksemax (blend landing). The body lives
     * in resources/content/educational. The publisher reads this catalog,
     * not the draft YAML.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function selankVsSemax(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'selank-vs-semax.md',
                'image_file' => 'selank-vs-semax.png',
                'slug' => 'selank-vs-semax',
                'h1' => 'Selank vs Semax: Structure, Evidence and FDA Status',
                'lede' => 'Selank is a tuftsin analogue and Semax an ACTH(4-10) analogue: two Russian heptapeptides with the same Pro-Gly-Pro tail. Sequences, identifiers, proposed mechanisms, evidence tiers and dated FDA compounding status, from primary sources. Educational only.',
                'seo_title' => 'Selank vs Semax: Structure, Evidence and FDA Status',
                'seo_description' => 'Selank is a tuftsin analogue, Semax an ACTH(4-10) analogue. Sequences, PubChem IDs, evidence tiers and dated FDA compounding status, from primary sources.',
                'og_title' => 'Selank vs Semax: Two Russian Heptapeptides Compared',
                'og_description' => 'Same Pro-Gly-Pro tail, different parents: small Russian studies, the one head-to-head imaging study, and FDA\'s 2023-2026 actions on Semax and Selank.',
                'blog_type' => 'Research',
                'read_time' => '21 Min Read',
                'published_at' => '2026-10-19',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Selank', 'Semax', 'Tuftsin', 'ACTH(4-10)', 'Russian peptides', 'FDA compounding', 'Category 2'],
                'key_points' => [
                    'Selank (Thr-Lys-Pro-Arg-Pro-Gly-Pro) is a tuftsin analogue; Semax (Met-Glu-His-Phe-Pro-Gly-Pro) is an ACTH(4-10) analogue that keeps ACTH(4-7). Both end in Pro-Gly-Pro, added for stability, and both were developed at the Institute of Molecular Genetics, Russian Academy of Sciences.',
                    'Identifiers: Selank PubChem CID 11765600, CAS 129954-34-3; Semax PubChem CID 9811102, CAS 80714-61-0 (free base), per FDA\'s substance registry.',
                    'Proposed mechanisms come mostly from rodent and cell work, much of it developer-affiliated: GABA-A modulation and enkephalinase inhibition for Selank; BDNF and trkB signalling for Semax. FDA calls Semax\'s mechanisms poorly understood.',
                    'Human evidence is small and mostly in Russian: two benzodiazepine-comparator studies for Selank and stroke studies without described randomization for Semax. One 52-person imaging study compared both with placebo; no study gave them together (PubMed and ClinicalTrials.gov, 19 October 2026).',
                    'Semax was in 503A Category 2 from 29 September 2023. FDA staff proposed against 503A listing (memo dated 11 May 2026); the PCAC recommended it by a narrow majority on 24 July 2026. No final FDA action as of 19 October 2026.',
                    'FDA lists Selank acetate (TP-7) among substances that may present significant safety risks. Neither peptide is FDA- or EMA-approved, and research-use-only vials are not Russian-registered or compounded medicines.',
                ],
                'faq' => self::faq($host.'/blog/selank-vs-semax#faq', [
                    ['Are Selank and Semax the same molecule?', 'No. Both are seven-amino-acid peptides ending in Pro-Gly-Pro, but their first four residues come from different parent molecules. Selank is Thr-Lys-Pro-Arg-Pro-Gly-Pro, built on tuftsin, an immune peptide. Semax is Met-Glu-His-Phe-Pro-Gly-Pro, built on a fragment of ACTH. They have different PubChem records (CID 11765600 and CID 9811102) and different CAS numbers.'],
                    ['Why are they so often listed together?', 'Both were developed at the Institute of Molecular Genetics of the Russian Academy of Sciences, both carry the same Pro-Gly-Pro tail, and both are described as registered medicines in Russia. FDA has also noted Semax products marketed in combination with Selank. Shared origin and marketing do not make them interchangeable.'],
                    ['Is Selank or Semax approved anywhere?', 'Russia only, as far as we found. FDA\'s July 2026 briefing says Semax is a registered drug in Russia. Peer-reviewed reviews describe Selank as approved in Russia; we could not open the Russian register to confirm that entry on 19 October 2026. Neither appears in Drugs@FDA or in EMA\'s centralised medicines table.'],
                    ['Where does Semax stand for compounding in the US?', 'Semax is not on the 503A bulks list as of 19 October 2026, and FDA says it has no USP or NF monograph and is not a component of an FDA-approved drug. FDA placed Semax in 503A Category 2 on 29 September 2023, and the nominations were later withdrawn. FDA staff proposed against listing it. On 24 July 2026 the Pharmacy Compounding Advisory Committee recommended adding it by a narrow majority, but the vote is advisory and no final FDA action was found.'],
                    ['Where does Selank stand with FDA?', 'FDA lists Selank acetate (TP-7) among substances that may present significant safety risks, citing possible immunogenicity and a lack of human safety information. It is not on the 503A bulks list or in FDA\'s 503A or 503B category lists, and it was not on the July 2026 advisory committee agenda.'],
                    ['Has any study compared or combined Selank and Semax?', 'One small study compared them. In 52 healthy participants, it measured brain connectivity on MRI minutes after Semax, Selank or placebo, with no clinical outcome. Some animal and test-tube studies tested both in parallel. We found no study of the two given together, and no registered trial of either, in PubMed and ClinicalTrials.gov searches on 19 October 2026.'],
                    ['Which one is better?', 'This page does not rank them. They come from different parent molecules, were studied in different patient groups, mostly in small Russian-language studies, and have no head-to-head clinical trial. Neither has controlled human evidence for the memory, focus or mood claims common in marketing.'],
                    ['Is a research-use-only vial the same as the Russian medicine?', 'No. The Russian products are registered medicines made under that country\'s rules. A research-use-only vial is neither a Russian-registered medicine nor a compounded or FDA-approved drug, and FDA found that none of the websites it searched said whether their Semax was the free base or a salt. None of the studies on this page describe the identity, purity or sterility of a research-labelled product.'],
                ]),
            ],
        ];
    }

    /**
     * Educational compare page for /compare/orforglipron-vs-oral-semaglutide.
     * The row is stored under a distinct blog slug so it does not overwrite
     * the ACHIEVE-3 article at /blog/orforglipron-vs-oral-semaglutide.
     * Sources checked 22 October 2026. The body lives in
     * resources/content/educational. The publisher reads this catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function orforglipronVsOralSemaglutideCompare(string $host): array
    {
        return [
            [
                'kind' => 'blog',
                'file' => 'orforglipron-vs-oral-semaglutide-compare.md',
                'image_file' => 'orforglipron-vs-oral-semaglutide-compare.png',
                'slug' => 'orforglipron-vs-oral-semaglutide-compare',
                'h1' => 'Orforglipron vs Oral Semaglutide: Labels and Status',
                'lede' => 'Orforglipron is Lilly\'s oral small-molecule nonpeptide GLP-1 receptor agonist. Oral semaglutide is Novo Nordisk\'s peptide tablet. US and EU status as of 22 October 2026, label conditions for food and water, and what ACHIEVE, ATTAIN, PIONEER, and OASIS studied. Educational only.',
                'seo_title' => 'Orforglipron vs Oral Semaglutide: Labels and Status',
                'seo_description' => 'Orforglipron vs oral semaglutide: molecule type, US and EU status on 22 October 2026, label food and water conditions, and trial design. Educational only.',
                'og_title' => 'Orforglipron vs Oral Semaglutide: Labels and Status',
                'og_description' => 'Small-molecule orforglipron versus Novo\'s peptide tablet: US and EU status on 22 October 2026, label food and water conditions, and trial designs.',
                'blog_type' => 'Research',
                'read_time' => '13 Min Read',
                'published_at' => '2026-10-22',
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Orforglipron', 'Oral semaglutide', 'Foundayo', 'Rybelsus', 'Wegovy', 'RUO'],
                'key_points' => [
                    'Orforglipron is a small-molecule, nonpeptide oral GLP-1 receptor agonist. Oral semaglutide is a peptide tablet co-formulated with the absorption enhancer SNAC.',
                    'As of 22 October 2026, FDA-approved Foundayo is a US weight-management tablet. The CHMP positive opinion of 15 October 2026 is pending a European Commission decision and is not an EU authorisation.',
                    'US labels for the oral semaglutide tablets state an empty-stomach condition and a water limit. The Foundayo label says with or without food and does not state that water limit.',
                    'ACHIEVE-3 compared the two in type 2 diabetes. ATTAIN, PIONEER, and OASIS are separate programmes. This page does not rank them and does not repeat per-arm weight-change percentages.',
                    'A research-use listing is not Foundayo, Rybelsus, an Ozempic tablet, or a Wegovy tablet.',
                ],
                'faq' => self::faq($host.'/compare/orforglipron-vs-oral-semaglutide#faq', [
                    ['Is orforglipron a peptide?', 'No. The ATTAIN-1 paper in the New England Journal of Medicine, which is developer-affiliated, describes orforglipron as a small-molecule, nonpeptide oral GLP-1 receptor agonist, and a phase 2 paper in the same journal, also developer-affiliated, calls it a nonpeptide GLP-1 receptor agonist. Oral semaglutide is a peptide. The US label says its peptide backbone is produced by yeast fermentation and that the tablets contain salcaprozate sodium (SNAC), an absorption enhancer.'],
                    ['What is oral semaglutide on the current labels?', 'Oral semaglutide is Novo Nordisk\'s peptide GLP-1 receptor agonist in a tablet. On the US labels checked on 22 October 2026, Rybelsus tablets and Ozempic tablets are indicated for type 2 diabetes, and Wegovy tablets are indicated for adult weight management and for cardiovascular risk reduction in adults with established cardiovascular disease and obesity or overweight. In the EU, Rybelsus has been authorised for type 2 diabetes since 3 April 2020, and the Wegovy authorisation includes tablets for adult weight management.'],
                    ['Is Foundayo approved in the United States and the European Union as of 22 October 2026?', 'In the United States, yes, for weight management. The FDA approved Foundayo (orforglipron) tablets on 1 April 2026 for adults with obesity, or overweight with at least one weight-related condition, together with diet and activity. The US label indication checked on 22 October 2026 is that use. It does not list type 2 diabetes. In the European Union, not yet as a granted authorisation. The CHMP adopted a positive opinion on 15 October 2026 recommending a marketing authorisation for type 2 diabetes and weight management. EMA\'s meeting highlights, published on 16 October 2026, list that opinion as pending a European Commission decision. A positive opinion is not a granted EU authorisation.'],
                    ['How do the labels differ on food and water?', 'The US labels for Rybelsus tablets, Ozempic tablets, and Wegovy tablets state an empty-stomach morning condition, water up to 4 ounces, no other liquids, and a wait of at least 30 minutes before food, drink, or other oral medicines. Those sentences are label conditions, not instructions from this page. The Foundayo label states that the tablet is taken once daily with or without food, and it does not state an empty-stomach condition or a water-volume limit. A phase 2 trial, reported in a developer-affiliated paper, gave orforglipron once daily without meal-timing restrictions. Lilly\'s 1 April 2026 press release, which is company-reported, says Foundayo can be taken any time of day without food or water restrictions.'],
                    ['Did a trial compare orforglipron with oral semaglutide, and does this page rank the programmes?', 'ACHIEVE-3 compared them in adults with type 2 diabetes who were taking metformin. It was a 52-week, open-label, randomized phase 3 trial, reported in a developer-affiliated Lancet paper. This page does not repeat its outcome figures. Those figures, with the estimand labelled, are on the orforglipron versus oral semaglutide blog. ATTAIN-1, PIONEER 6, OASIS 1, and OASIS 4 enrolled different populations and used different designs. This page does not rank one programme against another.'],
                    ['Is a research-use listing the same as the approved tablet?', 'No. A research-use-only product sold under one of these names is not Foundayo, not Rybelsus, not an Ozempic tablet, and not a Wegovy tablet. Peptidemap is a comparison publisher for research-use and grey-area peptide listings. It is not a clinic, a pharmacy, or a seller. The peptide legality guide separates FDA approval, research-use labelling, and compounding. This page is not medical advice and does not give a dose.'],
                ]),
            ],
        ];
    }
}

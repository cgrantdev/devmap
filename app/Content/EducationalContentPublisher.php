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
                'read_time' => '12 Min Read',
                'published_at' => '2026-09-29',
                'author_name' => 'Peptidemap',
                'tags' => ['BPC-157', 'TB-500', 'Evidence', 'WADA', 'FDA compounding'],
                'key_points' => [
                    'BPC-157 and TB-500 are chemically unrelated peptides.',
                    'Most of the published record is preclinical; robust human efficacy trials for injectable injury use are lacking.',
                    'A 2026 rat Achilles study found no added combination benefit on three named scores. That paper’s “TB-500” was not verified as Ac-LKKTETQ, and no human combination trials were identified.',
                    'Neither is an FDA-approved drug. A PCAC recommendation is not approval or a final bulks-list rule.',
                    'Both are prohibited in sport under the WADA 2026 Prohibited List.',
                ],
                'faq' => self::faq($host.'/blog/bpc-157-vs-tb-500-evidence#faq', [
                    ['Are BPC-157 and TB-500 the same peptide?', 'No. BPC-157 is a synthetic 15-amino-acid peptide from the gastric-cytoprotection research line. TB-500 usually refers to a short synthetic fragment of thymosin beta-4, a different endogenous protein involved in actin binding and cell migration. They are chemically unrelated.'],
                    ['Is either one FDA-approved for healing injuries?', 'No. Neither is an FDA-approved drug for musculoskeletal healing or any other indication. Compounding-list discussions (including PCAC recommendations) are not the same as drug approval.'],
                    ['Does animal evidence mean they work in humans?', 'No. Animal and cell studies can be scientifically interesting and still fail to predict human outcomes. For injectable injury recovery claims, both peptides still lack robust randomized human efficacy evidence.'],
                    ['Is there research on stacking BPC-157 with TB-500?', 'A 2026 rat Achilles study (Biçer et al.) reported that combined BPC-157 and TB-500 did not confer additional benefit versus either agent alone on maximum load to failure, total Bonar score, and total Movin score. That is animal histopathology and biomechanics, not a human stack. No published human combination trials were identified. The paper’s “TB-500” was not verified as the 7-residue Ac-LKKTETQ fragment.'],
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
                    ['Where do I check if something can be compounded?', 'Not on this beginner page. Use the companion legality guide and current FDA compounding sources (and educational trackers such as PrescribedRX’s status page) because interim categories and advisory votes change.'],
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
        ], self::evidenceNotes($host), self::octoberQaPieces($host), self::coaLiteracyAndIdentityNotes($host));
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
                'read_time' => '8 Min Read',
                'published_at' => $published,
                'is_featured' => false,
                'author_name' => 'Peptidemap',
                'tags' => ['Retatrutide', 'Tirzepatide', 'TRIUMPH', 'SURMOUNT', 'Estimands'],
                'key_points' => [
                    'Retatrutide is an investigational GIP, GLP-1, and glucagon agonist. Tirzepatide is a dual GIP and GLP-1 agonist.',
                    'TRIUMPH-1 at 12 mg and 80 weeks: treatment-regimen −25.0%, efficacy estimand −28.3%. Those are two estimands of one trial.',
                    'TRIUMPH-2, in type 2 diabetes, reported treatment-regimen −18.8% and efficacy −20.8% at 12 mg. It is not TRIUMPH-1.',
                    'SURMOUNT-1 studied tirzepatide to 72 weeks. Its 15 mg treatment-regimen result was −20.9%; −22.5% is the efficacy estimand.',
                    'TRIUMPH-5 is a registered retatrutide-versus-tirzepatide trial with no results in the record checked on 5 October 2026.',
                ],
                'faq' => self::faq($host.'/blog/retatrutide-vs-tirzepatide#faq', [
                    ['How do retatrutide and tirzepatide differ?', 'Retatrutide (LY3437943) is one investigational molecule with agonist activity at GIP, GLP-1, and glucagon receptors. Tirzepatide is a dual GIP and GLP-1 agonist and does not add glucagon-receptor agonism. Retatrutide is not an approved medicine.'],
                    ['Why can TRIUMPH-1 show both −25.0% and −28.3%?', 'They are two estimands of the same 80-week trial at 12 mg. −25.0% is the treatment-regimen estimand. −28.3% is the efficacy estimand. They are not two trials.'],
                    ['Is TRIUMPH-2 the same result as TRIUMPH-1?', 'No. TRIUMPH-2 enrolled adults with type 2 diabetes. At 12 mg the treatment-regimen change was −18.8% versus −5.1% with placebo, and the efficacy estimand was −20.8% versus −4.0% with placebo. That is not the TRIUMPH-1 treatment-regimen −25.0%.'],
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
                    ['What did TRIUMPH-1 report for retatrutide alone?', 'At week 80 the treatment-regimen change was −17.6% at 4 mg, −23.7% at 9 mg, and −25.0% at 12 mg, versus −3.9% with placebo. The efficacy estimand at 12 mg was −28.3%. Those rows are retatrutide monotherapy, not a blend result.'],
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
}

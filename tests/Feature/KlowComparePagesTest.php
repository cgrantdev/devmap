<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\SitemapController;
use App\Models\ProductCategory;
use App\Support\KlowCompareCopy;
use App\Support\VsPageSeo;
use App\Support\VsPairIndexing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class KlowComparePagesTest extends TestCase
{
    use RefreshDatabase;

    private const GLOW = 'glow-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv';

    private const GHK = 'ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_short_klow_urls_redirect_once_and_keep_the_query(): void
    {
        $this->activateCategories();

        $this->assertOneHop(
            '/compare/glow-vs-klow?utm=qa',
            '/compare/'.self::GLOW.'?utm=qa'
        );
        $this->assertOneHop(
            '/compare/klow-vs-glow?ref=1',
            '/compare/'.self::GLOW.'?ref=1'
        );
        $this->assertOneHop(
            '/compare/KLOW-vs-GHK-Cu?utm=qa',
            '/compare/'.self::GHK.'?utm=qa'
        );
        $this->assertOneHop(
            '/compare/ghk-cu-vs-klow?location=US',
            '/compare/'.self::GHK.'?location=US'
        );
    }

    public function test_canonical_pages_render_copy_and_are_indexed_once(): void
    {
        $allow = config('seo.vs_sitemap_allowlist');
        $this->assertCount(24, $allow);
        $this->assertSame(
            'ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv',
            $allow[array_search('elamipretide-vs-mots-c', $allow, true) + 1]
        );
        $this->assertSame(
            'ghk-cu-vs-tb-500',
            $allow[array_search(self::GHK, $allow, true) + 1]
        );
        $this->assertSame(
            self::GLOW,
            $allow[array_search('ghk-cu-vs-tb-500', $allow, true) + 1]
        );

        $this->activateCategories();
        Cache::forget(SitemapController::CACHE_KEY);
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach ([self::GLOW, self::GHK] as $slug) {
            $this->assertTrue(VsPairIndexing::isIndexable($slug));
            $this->assertTrue(VsPairIndexing::hasEditorialContent($slug));
            $this->assertSame(1, substr_count($xml, 'https://peptidemap.com/compare/'.$slug.'</loc>'), $slug);

            $copy = KlowCompareCopy::forSlug($slug);
            $this->assertNotNull($copy);
            $this->assertCount(8, $copy['faqs']);

            $response = $this->get('/compare/'.$slug);
            $response->assertOk();
            $response->assertHeaderMissing('Location');
            $response->assertSee('Evidence at a glance', false);
            $response->assertDontSee('noindex', false);
            $response->assertDontSee('<meta name="robots"', false);

            $response->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->where('evidence.title', 'Evidence at a glance')
                ->has('faqs', 8)
                ->where('seo.schema.1.@type', 'FAQPage')
                ->has('seo.schema.1.mainEntity', 8));

            $props = $response->inertiaPage()['props'];
            $faqs = $props['faqs'];
            $entities = $props['seo']['schema'][1]['mainEntity'];
            $this->assertCount(8, $faqs);
            $this->assertCount(8, $entities);
            foreach ($faqs as $i => $faq) {
                $this->assertSame($faq['q'], $entities[$i]['name']);
                $this->assertSame($faq['a'], $entities[$i]['acceptedAnswer']['text']);
                $response->assertSee($faq['q'], false);
            }

            $blob = (string) json_encode([
                'evidence' => $props['evidence'],
                'faqs' => $faqs,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->assertCopyHasNoForbiddenStrings($blob);

            $title = $props['seo']['title'];
            $h1 = $props['seo']['h1'];
            $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
            $this->assertLessThanOrEqual(60, mb_strlen($h1), $h1);
            $this->assertLessThanOrEqual(60, mb_strlen($title.VsPageSeo::SITE_SUFFIX), $title.VsPageSeo::SITE_SUFFIX);
            $response->assertSee('<h1 class="ssr-seo-h1">'.$h1.'</h1>', false);
        }

        $glow = KlowCompareCopy::forSlug(self::GLOW);
        $ghk = KlowCompareCopy::forSlug(self::GHK);
        $glowSeo = VsPageSeo::forPair('GLOW — GHK-Cu/BPC-157/TB-500', 'KLOW — GHK-Cu/BPC-157/TB-500/KPV', self::GLOW);
        $ghkSeo = VsPageSeo::forPair('GHK-Cu', 'KLOW — GHK-Cu/BPC-157/TB-500/KPV', self::GHK);
        $this->assertSame('GLOW vs KLOW — Vendor & Price Comparison', $glowSeo['title']);
        $this->assertSame('GLOW vs KLOW', $glowSeo['h1']);
        $this->assertSame(
            'GLOW vs KLOW research blend labels: KLOW adds KPV, not more GHK-Cu. Component table, evidence limits, and listings. RUO only; Peptidemap sells nothing.',
            $glowSeo['description']
        );
        $this->assertSame('GHK-Cu vs KLOW', $ghkSeo['h1']);
        $this->assertSame(
            'GHK-Cu vs KLOW: one copper peptide vs a four-peptide blend label. Component table, evidence limits, and listings. RUO only; Peptidemap sells nothing.',
            $ghkSeo['description']
        );
        $this->assertLessThanOrEqual(60, mb_strlen($ghkSeo['title'].VsPageSeo::SITE_SUFFIX));

        $glowBlob = (string) json_encode($glow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ghkBlob = (string) json_encode($ghk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('None of the four is an FDA-approved drug.', $glowBlob);
        $this->assertStringContainsString('None of them is an FDA-approved drug.', $ghkBlob);
        $this->assertStringContainsString('lab teams', $glowBlob);
        $this->assertStringContainsString('lab teams', $ghkBlob);
        $this->assertStringNotContainsString('lab buyers', $glowBlob);
        $this->assertStringNotContainsString('lab buyers', $ghkBlob);
        $this->assertStringContainsString('8(4)', $glowBlob);
        $this->assertStringContainsString('A histopathological and biomechanical study', $glowBlob);
        $this->assertStringContainsString('as reported by Reuters', $glowBlob);
        $this->assertStringContainsString('as reported by Reuters', $ghkBlob);
        $this->assertStringContainsString('https://www.reuters.com/legal/litigation/us-fda-advisers-weigh-restrictions-seven-peptides-industry-presses-expansion-2026-07-23/', $glowBlob);
    }

    private function activateCategories(): void
    {
        ProductCategory::create(['name' => 'GLOW', 'slug' => 'glow', 'is_active' => true]);
        ProductCategory::create(['name' => 'KLOW', 'slug' => 'klow-blend-ghk-cu-bpc-157-tb-500-kpv', 'is_active' => true]);
        ProductCategory::create(['name' => 'GHK-Cu', 'slug' => 'ghk-cu', 'is_active' => true]);
    }

    private function assertOneHop(string $from, string $to): TestResponse
    {
        $response = $this->get($from);
        $response->assertStatus(301);
        $response->assertHeader('Location', url($to));

        $target = $this->get($response->headers->get('Location'));
        $target->assertOk();
        $target->assertHeaderMissing('Location');

        return $target;
    }

    private function assertCopyHasNoForbiddenStrings(string $text): void
    {
        foreach ([
            'Category 2 page names',
            '67724-34-9',
            ' mg',
            '%',
            'best',
            'cheapest',
            'verified',
            'efficacy estimand',
        ] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $text, $forbidden);
        }
    }
}

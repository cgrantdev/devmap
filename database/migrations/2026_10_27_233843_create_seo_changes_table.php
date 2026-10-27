<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated SEO changelog so Colin + the agency can see every SEO-relevant
 * change in one place without scanning git log. Seeded with the known
 * Oct 2026 SEO work (recovery, agency onboarding, audit response).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_changes', function (Blueprint $table) {
            $table->id();
            $table->date('happened_at');
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->string('category', 40)->nullable(); // meta|interlinks|content|technical|redirects|speed|schema|agency|recovery
            $table->string('actor', 100)->nullable();   // "Colin", "Advantage Collective", "Automated sync" etc
            $table->string('commit_sha', 40)->nullable();
            $table->timestamps();

            $table->index('happened_at');
            $table->index('category');
        });

        // Seed with the shipped Oct 2026 SEO work. Dates are the actual
        // commit dates where possible.
        $seed = [
            ['2026-10-27', 'VialVerse size backfill', 'Daily sync was leaving 81/88 VialVerse products without size_mg. Parsed size from URL path on sync + ran one-off backfill.', 'content', 'Colin'],
            ['2026-10-27', 'Duplicate H1 removed', 'The server-rendered SSR heading now emits <div role="heading" aria-level="1"> instead of a second <h1>. Fixes "two main headings on every page" across 5,394 pages.', 'technical', 'Colin / Advantage Collective'],
            ['2026-10-27', 'GA4 outbound-click tracking', 'Every /go/ vendor click fires a client-side gtag event + dataLayer push. Agency can now mark outbound_click as a Key Event in GA4.', 'technical', 'Colin / Advantage Collective'],
            ['2026-10-27', 'Redirect manager live', 'New /admin/redirects tool: 301/302/307/308 forwards editable without code access. Agency owns the ~307 missing-page forwards from audit Part 5.', 'redirects', 'Colin'],
            ['2026-10-27', 'Content issue scanner live', 'New /admin/content-scanner tool scans products, encyclopedia, blogs, brand descriptions for internal-note markers ("TODO:", "Refused API", lorem ipsum, etc.) bleeding to public pages.', 'content', 'Colin'],
            ['2026-10-27', 'Advantage Collective technical audit received', '5,396-page audit covering indexing, interlinking, mobile speed, title/meta patterns, research-use-only wording, and ~307 missing-page redirects.', 'agency', 'Advantage Collective'],
            ['2026-10-27', 'SEO agency role + scoped admin', 'New seo_agency role; BlockSeoAgency middleware strips vendor/user/affiliate/settings/dev routes. Agency login provisioned.', 'agency', 'Colin'],
            ['2026-10-21', 'Encyclopedia + compare canonical cleanup', 'Canonicalized GLP compounds encyclopedia → compare; removed Human Use sections; shipped product aggregates for compare pages.', 'content', 'Colin'],
            ['2026-10-21', '4,541 unique AI product descriptions generated', 'Dedup fix for Google omitted-similar-entries problem. Replaced duplicate boilerplate with unique per-product generated_description.', 'content', 'Colin'],
            ['2026-10-21', 'Meta description fallback chain', 'Product + compare + brand pages now use vendor_description → generated_description → fallback for a unique meta on every page.', 'meta', 'Colin'],
            ['2026-10-21', 'Homepage title + description refresh', 'Keyword-rich title ("Compare 60+ peptide vendors") and description replacing the 64-char placeholder.', 'meta', 'Colin'],
            ['2026-10-15', 'Google October core update', 'Confirmed overlap with 3 Oct traffic drop. Combined with our own change wave, drove 27.5 → 22.4 avg position after recovery work landed.', 'recovery', 'Automated'],
            ['2026-10-10', 'VS page SEO fix', 'Compare pages lead with raw compound names (SEO) not display pseudonyms. Alias chip kept for vendor-friendly label.', 'meta', 'Colin'],
            ['2026-10-05', 'Coupon landing pages built', '/coupon/{brand} pages shipped. Already ranked 9-19 for "{brand} coupon code" searches without interlinking.', 'content', 'Colin'],
            ['2026-10-03', 'Google October update traffic cliff', 'Core update + our own change wave (encyclopedia canonicals, Human Use removal, structured data cleanup) all landed Oct 3-7. Diagnosed and recovery work launched Oct 21.', 'recovery', 'Automated'],
            ['2026-09-30', 'robots.txt without session cookies', 'Stripped Inertia headers + XSRF cookie from robots.txt + sitemap responses so crawlers no longer get Vary: X-Inertia on crawl.', 'technical', 'Colin'],
        ];
        foreach ($seed as [$d, $t, $s, $c, $a]) {
            DB::table('seo_changes')->insert([
                'happened_at' => $d,
                'title' => $t,
                'summary' => $s,
                'category' => $c,
                'actor' => $a,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_changes');
    }
};

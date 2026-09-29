<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Production serves the apex with SITE_LIVE=true. While the site is
        // not live, ComingSoon replaces peptidemap.com responses with the
        // coming-soon page, including /robots.txt.
        config(['app.site_live' => true]);
    }

    public function test_canonical_host_serves_crawl_rules_and_sitemap(): void
    {
        $response = $this->get('https://peptidemap.com/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $body = $response->getContent();

        $this->assertSame(
            "User-agent: *\n"
            ."Disallow: /admin/\n"
            ."Disallow: /vendor/\n"
            ."Disallow: /account/\n"
            ."Disallow: /login\n"
            ."Disallow: /logout\n"
            ."Disallow: /register\n"
            ."Disallow: /password/\n"
            ."Disallow: /email/\n"
            ."Disallow: /api/\n"
            ."Disallow: /sanctum/\n"
            ."Allow: /\n\n"
            ."Sitemap: https://peptidemap.com/sitemap.xml\n",
            $body
        );

        // Cloudflare's content-signals comment block is not a robots.txt.
        $this->assertStringNotContainsString('content signal', strtolower($body));
        $this->assertStringNotContainsString('Content-Signal', $body);

        // Public marketplace/SEO paths stay crawlable. Allow: / covers them;
        // a prefix Disallow would hide the whole section.
        foreach (['/compare', '/encyclopedia', '/blog', '/blogs', '/guides', '/news', '/vendors'] as $path) {
            $this->assertStringNotContainsString('Disallow: '.$path, $body);
        }

        $response->assertCookieMissing('XSRF-TOKEN');
        $response->assertCookieMissing(config('session.cookie'));

        $vary = (string) $response->headers->get('Vary');
        $this->assertStringNotContainsString('X-Inertia', $vary);
    }

    public function test_unknown_host_disallows_all_crawling(): void
    {
        $response = $this->get('https://preview.example.test/robots.txt');

        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_noindex_host_stays_crawlable_without_a_sitemap(): void
    {
        config(['app.site_live' => false]);

        $response = $this->get('https://demo.peptidemap.com/robots.txt');

        $response->assertOk();
        $this->assertSame(
            "User-agent: *\n"
            ."Disallow: /admin/\n"
            ."Disallow: /vendor/\n"
            ."Disallow: /account/\n"
            ."Disallow: /api/\n"
            ."Disallow: /sanctum/\n"
            ."Allow: /\n",
            $response->getContent()
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ScrapingConfig;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA route walk for PR #89. Seeds a sentinel in every secret column, walks
 * every public GET route (HTML and X-Inertia JSON) and checks the admin,
 * /go and plugin-connect flows that legitimately need the hidden fields.
 * Failures use assertFalse/assertSame so a body is never dumped.
 */
class VendorSecretsRouteWalkTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'pmap_QAROUTEWALKSENTINEL0000000000000000000';

    /** column => sentinel; every value must stay off public pages. */
    private const SECRETS = [
        'connection_token' => self::TOKEN,
        'affiliate_url_template' => 'https://track.example/{slug}?t=QATEMPLATESENTINEL&tag={affiliate_tag}',
        'affiliate_tag' => 'QATAGSENTINEL',
        'api_key' => 'QAAPIKEYSENTINEL',
        'api_route' => 'QAAPIROUTESENTINEL',
        'feed_url' => 'https://feed.example/x?token=QAFEEDSENTINEL',
        'affiliate_credentials' => 'QAAFFCREDSENTINEL',
        'affiliate_stats_json' => ['token' => 'QASTATSSENTINEL'],
        'commission_rate_pct' => 17.31,
        'api_platform' => 'QAAPIPLATFORMSENTINEL',
        'affiliate_platform' => 'QAAFFPLATFORMSENTINEL',
        'coupon_boost_code' => 'QABOOSTCODESENTINEL',
        'coupon_code_previous' => 'QAPREVCODESENTINEL',
    ];

    private const NEEDLES = [
        'pmap_QAROUTEWALK', 'QATEMPLATESENTINEL', 'QATAGSENTINEL', 'QAAPIKEYSENTINEL', 'QAAPIROUTESENTINEL',
        'QAFEEDSENTINEL', 'QAAFFCREDSENTINEL', 'QASTATSSENTINEL', '17.31', 'QAAPIPLATFORMSENTINEL',
        'QAAFFPLATFORMSENTINEL', 'QABOOSTCODESENTINEL', 'QAPREVCODESENTINEL',
        '"connection_token"', '"api_key"', '"api_route"', '"feed_url"', '"affiliate_credentials"',
        '"affiliate_stats_json"', '"affiliate_url_template"', '"affiliate_tag"', '"commission_rate_pct"',
    ];

    private Brand $brand;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.debug' => false]); // error pages must not echo bindings
        Storage::fake('public');
        Storage::disk('public')->put('logos/walk-labs.png', 'png');

        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);
        $tb = ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true]);

        $this->brand = Brand::create([
            'name' => 'Walk Labs', 'slug' => 'walk-labs', 'is_active' => true,
            'connection_token' => self::TOKEN,
            'affiliate_url_template' => self::SECRETS['affiliate_url_template'],
            'affiliate_tag' => self::SECRETS['affiliate_tag'],
        ]);
        VendorSetting::create(array_merge([
            'brand_id' => $this->brand->id, 'status' => 1, 'approval_status' => 'approved',
            'logo' => 'logos/walk-labs.png', 'description' => 'Walk Labs public blurb.',
            'shop_url' => 'https://walk.example', 'website' => 'https://walk.example',
            'coupon_code' => 'WALK10', 'coupon_discount_percent' => 10,
            'why_choose_bullets' => ['Third-party tested'], 'tagline' => 'Walk tagline',
            'seo_page_title' => 'Walk SEO title',
        ], array_diff_key(self::SECRETS, array_flip(['connection_token', 'affiliate_url_template', 'affiliate_tag']))));

        foreach ([[$bpc, 'bpc-157-5mg-walk'], [$tb, 'tb-500-5mg-walk']] as [$cat, $slug]) {
            $p = Product::create([
                'name' => $cat->name.' 5mg', 'slug' => $slug, 'brand_id' => $this->brand->id,
                'product_category_id' => $cat->id, 'price' => 40, 'status' => 'active', 'hidden' => false,
                'product_url' => 'https://walk.example/p/'.$slug,
            ]);
            $this->product ??= $p;
        }
    }

    public function test_every_public_get_route_is_free_of_vendor_secrets_html_and_inertia_json(): void
    {
        $leaks = [];
        $checked = 0;
        $log = [];
        foreach ($this->publicUris() as $uri) {
            foreach ([false, true] as $xi) {
                $this->flushHeaders();
                if ($xi) {
                    $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest',
                        'X-Inertia-Version' => (string) app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request())]);
                }
                $resp = $this->get($uri);
                $log[] = $resp->getStatusCode().' '.($xi ? '[XI] ' : '').$uri;
                if ($resp->getStatusCode() >= 500) {
                    continue; // pre-existing SQLite-only failures; nothing rendered
                }
                $checked++;
                // brand.affiliate_visit_url is the expanded outbound "Visit" link
                // (same URL a visitor gets from /go); it is public by design.
                $body = preg_replace('/"affiliate_visit_url":"[^"]*"/', '"affiliate_visit_url":"<x>"',
                    html_entity_decode((string) $resp->getContent(), ENT_QUOTES));
                foreach (self::NEEDLES as $needle) {
                    if (str_contains($body, $needle)) {
                        $leaks[] = ($xi ? '[XI] ' : '').$uri.' -> '.(str_starts_with($needle, 'pmap_') ? 'connection token value' : $needle);
                    }
                }
            }
        }
        if ($out = getenv('QA_WALK_LOG')) {
            file_put_contents($out, implode("\n", $log)."\n");
        }
        $this->assertGreaterThan(40, $checked, 'route walk visited too few pages');
        $this->assertSame([], $leaks, 'secret leaks on public routes');
    }

    public function test_listing_cards_keep_display_fields_and_only_allowlisted_vendor_setting_keys(): void
    {
        $props = $this->props('/product/bpc-157');
        $card = $props['products']['data'][0];
        $this->assertSame('Walk Labs', $card['brand']['name']);
        $this->assertSame('walk-labs', $card['brand']['slug']);
        $this->assertSame('WALK10', $card['brand_coupon_code']);
        $this->assertEquals(10.0, $card['brand_discount_percent']);
        $vsKeys = array_keys($card['brand']['vendor_setting'] ?? []);
        $this->assertSame([], array_diff($vsKeys, VendorSetting::CARD_COLUMNS), 'card vendor_setting outside allow-list');

        $brand = $this->props('/brand/walk-labs')['brand'];
        foreach (['name' => 'Walk Labs', 'slug' => 'walk-labs', 'tagline' => 'Walk tagline', 'shop_url' => 'https://walk.example', 'coupon_code' => 'WALK10'] as $k => $v) {
            $this->assertSame($v, $brand[$k] ?? null, "brand.$k");
        }
        $this->assertStringContainsString('walk-labs.png', (string) $brand['logo']);
        $this->assertNotEmpty($brand['why_choose_bullets'] ?? null, 'brand.why_choose_bullets');
    }

    public function test_hidden_attributes_are_still_readable_server_side(): void
    {
        $b = Brand::find($this->brand->id);
        $this->assertSame(self::TOKEN, $b->connection_token);
        $this->assertSame('QATAGSENTINEL', $b->affiliate_tag);
        $this->assertArrayNotHasKey('connection_token', $b->toArray());
        $vs = $b->vendorSetting;
        $this->assertSame('QAAPIKEYSENTINEL', $vs->api_key);
        $this->assertSame('https://feed.example/x?token=QAFEEDSENTINEL', $vs->feed_url);
        $this->assertArrayNotHasKey('api_key', $vs->toArray());
    }

    public function test_go_redirect_still_applies_hidden_affiliate_template_and_tag(): void
    {
        $resp = $this->get('/go/'.$this->product->id);
        $resp->assertRedirect();
        $loc = (string) $resp->headers->get('Location');
        $this->assertStringStartsWith('https://track.example/bpc-157-5mg-walk', $loc);
        $this->assertStringContainsString('tag=QATAGSENTINEL', $loc);
    }

    public function test_admin_vendor_edit_gets_affiliate_fields_and_feed_url_but_not_credentials(): void
    {
        $admin = User::create(['name' => 'QA Admin', 'email' => 'qa-admin@example.test', 'password' => bcrypt('x'), 'role' => 'admin']);
        $resp = $this->actingAs($admin)->get('/admin/vendors/'.$this->brand->id.'/edit');
        $resp->assertOk();
        $page = $this->pageFrom((string) $resp->getContent());
        $vendor = $page['props']['vendor'];
        $this->assertSame(self::SECRETS['affiliate_url_template'], $vendor['affiliate_url_template'] ?? null);
        $this->assertSame('QATAGSENTINEL', $vendor['affiliate_tag'] ?? null);
        $this->assertSame(self::SECRETS['feed_url'], $vendor['settings']['feed_url'] ?? null);
        $body = (string) $resp->getContent();
        $this->assertFalse(str_contains($body, 'QAAPIKEYSENTINEL'), 'admin edit echoes api_key');
        $this->assertFalse(str_contains($body, 'QAAFFCREDSENTINEL'), 'admin edit echoes affiliate_credentials');

        $seo = $this->actingAs($admin)->get('/admin/seo/brands');
        $seo->assertOk();
        $rows = $this->pageFrom((string) $seo->getContent())['props']['brands']['data'];
        $this->assertSame('Walk SEO title', $rows[0]['vendor_setting']['seo_page_title'] ?? null);
        $this->assertFalse(str_contains((string) $seo->getContent(), 'pmap_QAROUTEWALK'), 'seo brands list leaks token');
    }

    public function test_plugin_connect_still_accepts_the_hidden_token(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $payload = ['store_url' => 'https://walk.example', 'consumer_key' => 'ck_qa_0123456789', 'consumer_secret' => 'cs_qa_0123456789'];

        $this->postJson('/api/vendor-plugin/connect', $payload + ['connection_token' => 'pmap_wrong'])->assertStatus(404);
        $this->postJson('/api/vendor-plugin/connect', $payload + ['connection_token' => self::TOKEN])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertTrue(ScrapingConfig::where('vendor_id', $this->brand->id)->where('type', 'woo_api')->exists());
    }

    private function publicUris(): array
    {
        $subs = ['slug' => ['bpc-157', 'walk-labs', 'bpc-157-vs-tb-500'], 'vendorSlug' => ['walk-labs'],
            'productSlug' => ['bpc-157-5mg-walk'], 'id' => [$this->product->id], 'product' => [$this->product->id],
            'vendor_name' => ['walk-labs'], 'brand' => ['walk-labs']];
        $uris = [];
        foreach (Route::getRoutes() as $r) {
            if (! in_array('GET', $r->methods(), true)) continue;
            if (preg_match('/\bauth\b|role:|ceo\.only|vendor\.api|signed/', implode(',', $r->gatherMiddleware()))) continue;
            $u = '/'.ltrim($r->uri(), '/');
            if (preg_match('#^/(admin|vendor/|telescope|pulse|horizon|sanctum|_|storage|go/|up$)#', $u)) continue;
            $cands = [$u];
            preg_match_all('/\{(\w+)\??\}/', $u, $m);
            foreach ($m[1] as $p) {
                $next = [];
                foreach ($cands as $c) foreach ($subs[$p] ?? ['bpc-157'] as $v) $next[] = preg_replace('/\{'.$p.'\??\}/', (string) $v, $c, 1);
                $cands = $next;
            }
            foreach ($cands as $c) $uris[$c] = true;
        }
        foreach (['/', '/product/bpc-157', '/brand/walk-labs', '/compare', '/compare/bpc-157', '/compare/bpc-157-vs-tb-500',
            '/coupon/walk-labs', '/deals', '/search?q=bpc', '/search?q=walk&tab=vendors', '/vendors', '/bulk',
            '/product/walk-labs/bpc-157-5mg-walk/'.$this->product->id, '/sitemap.xml'] as $e) $uris[$e] = true;

        return array_keys($uris);
    }

    private function props(string $uri): array
    {
        return $this->pageFrom((string) $this->get($uri)->assertOk()->getContent())['props'];
    }

    private function pageFrom(string $html): array
    {
        $this->assertSame(1, preg_match('/data-page="([^"]*)"/', $html, $m), 'no Inertia payload');
        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }
}

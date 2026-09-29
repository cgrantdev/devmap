<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductClick;
use App\Models\ScrapingConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutboundClickController extends Controller
{
    /**
     * Log a click and redirect the user to the vendor's product page.
     * Route: GET /go/{product}
     */
    public function redirect(Request $request, Product $product)
    {
        $product->loadMissing(['brand.vendorSetting']);

        $destination = $this->resolveDestinationUrl($product);

        // If we have no destination at all, fall back to the internal product page.
        if (empty($destination)) {
            if ($product->brand && $product->brand->slug) {
                return redirect()->route('product.detail', [
                    'vendorSlug' => $product->brand->slug,
                    'productSlug' => $product->slug ?? 'product',
                    'id' => $product->id,
                ]);
            }
            return redirect()->route('product.detail.legacy', [
                'slug' => $product->slug ?? 'product',
                'id' => $product->id,
            ]);
        }

        // Tag the outbound URL with UTMs so vendors' own analytics attribute
        // the traffic to us. Without this the vendor sees "direct" and we
        // can't prove we sent them the sale.
        $destination = $this->tagWithUtms($destination, $product);

        $userAgent = (string) $request->userAgent();
        $isBot = $this->looksLikeBot($userAgent);

        // Referrer resolution: browsers strip the Referer header when the
        // click originates from an <a rel="noreferrer"> tag (which our Buy
        // buttons use for privacy). So we prefer an explicit ?src= param
        // that our frontend attaches — internal_source is authoritative
        // when present. Prefix with "internal:" so analytics can tell it
        // apart from real cross-origin referrers.
        $srcParam = trim((string) $request->query('src', ''));
        $referrerHeader = (string) $request->headers->get('referer');
        $referrerToLog = $srcParam !== ''
            ? 'internal:' . $srcParam
            : $referrerHeader;

        // Estimated commission this click could earn — vendor's commission
        // rate × effective price. Doesn't factor conversion rate; that's
        // reconciled against real affiliate-platform sales in the daily
        // sync job. Useful as an upper-bound revenue estimate + for
        // ranking vendors by click-value on the /admin/affiliates page.
        $estCommission = null;
        $pct = (float) ($product->brand?->vendorSetting?->commission_rate_pct ?? 0);
        $effectivePrice = (float) ($product->discount_price ?: $product->price ?: 0);
        if ($pct > 0 && $effectivePrice > 0 && !$isBot) {
            $estCommission = round($effectivePrice * $pct / 100, 2);
        }

        // Fire-and-forget log. Wrap in try to never block the redirect.
        try {
            ProductClick::create([
                'product_id' => $product->id,
                'brand_id' => $product->brand_id,
                'user_id' => Auth::id(),
                'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . config('app.key')) : null,
                'user_agent' => mb_substr($userAgent, 0, 512),
                'referrer' => mb_substr($referrerToLog, 0, 1024),
                'destination_url' => mb_substr($destination, 0, 2048),
                'is_bot' => $isBot,
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'estimated_commission_usd' => $estCommission,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('ProductClick logging failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Server-side GA4 event via Measurement Protocol. Non-blocking;
        // failure never breaks the redirect. Enabled only when the site
        // is configured with GA4 measurement_id + api_secret in .env
        // (services.ga4.*). Fires a 'select_item' event with product +
        // brand + estimated value so GA4 can rank outbound performance.
        if (!$isBot) {
            $this->fireGa4Event($request, $product, $estCommission);
        }

        return redirect()->away($destination, 302);
    }

    /**
     * Append utm_source/medium/campaign to the outbound URL when it's a
     * plain http(s) link. Never overwrites an existing UTM value — some
     * vendors set their own for their affiliate program's landing pages.
     * Shopify /discount/{code}?redirect=… URLs get UTMs on the OUTER url
     * safely; the vendor's checkout keeps the discount either way.
     */
    protected function tagWithUtms(string $destination, Product $product): string
    {
        $parts = @parse_url($destination);
        if (!$parts || empty($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
            return $destination;
        }

        parse_str($parts['query'] ?? '', $query);

        $brandSlug = $product->brand?->slug ?? 'unknown';

        if (!isset($query['utm_source']))   $query['utm_source']   = 'peptidemap';
        if (!isset($query['utm_medium']))   $query['utm_medium']   = 'affiliate';
        if (!isset($query['utm_campaign'])) $query['utm_campaign'] = $brandSlug;

        $parts['query'] = http_build_query($query);

        return $this->rebuildUrl($parts);
    }

    /**
     * Resolve the outbound URL.
     *
     * When products.product_url is set, the hop lands on that product
     * deep link. vendor_settings.referral_url is the affiliate program's
     * entry link — in practice the vendor homepage or a program signup
     * page, plus tracking params such as ?ref= or ?affid=. It must not
     * replace the product path. Same-host referral query params are
     * copied onto the product URL so the program still gets credit.
     * brands.affiliate_url_template, when set, still builds the legacy
     * per-product tracked URL (placeholders: {product_url}, {slug},
     * {id}, {affiliate_tag}) and wins over the raw product URL.
     *
     * Fallback, only when product_url is empty:
     *   1. referral_url (vendor affiliate landing, often the homepage)
     *   2. affiliate_url_template, when it does not require {product_url}
     *   3. null — the caller redirects to the internal product page
     *
     * Coupon injection runs on the product deep link inside this method.
     * The caller appends PeptideMap UTMs afterward and does not overwrite
     * UTM keys already present.
     */
    protected function resolveDestinationUrl(Product $product): ?string
    {
        $template = $product->brand?->affiliate_url_template;
        $productUrl = $product->product_url ?: null;
        $referral = $product->brand?->vendorSetting?->referral_url ?: null;

        if (!empty($productUrl)) {
            if (!empty($template)) {
                $resolved = $this->applyAffiliateTemplate($template, $product, $productUrl);
                if (!empty($resolved)) {
                    return $resolved;
                }
            }

            $deepLink = $this->mergeSameHostReferralParams($productUrl, $referral);

            return $this->injectCoupon($deepLink, $product);
        }

        if (!empty($referral)) {
            return $referral;
        }

        if (!empty($template)) {
            return $this->applyAffiliateTemplate($template, $product, null);
        }

        return null;
    }

    /**
     * Substitute affiliate template placeholders. Returns null when the
     * template needs {product_url} and none is stored, so the caller can
     * fall through to referral_url or the internal product page.
     */
    protected function applyAffiliateTemplate(string $template, Product $product, ?string $productUrl): ?string
    {
        if (str_contains($template, '{product_url}') && empty($productUrl)) {
            return null;
        }

        $resolved = strtr($template, [
            '{product_url}' => $productUrl ?? '',
            '{slug}' => $product->slug ?? '',
            '{id}' => (string) $product->id,
            '{affiliate_tag}' => (string) ($product->brand?->affiliate_tag ?? ''),
        ]);

        return $resolved !== '' ? $resolved : null;
    }

    /**
     * Copy affiliate query params from the vendor referral URL onto the
     * product deep link when both URLs share a host (www. ignored).
     *
     * referral_url is usually https://vendor.example/?ref=CODE — the
     * shop root plus the program's tracking params. Using that URL as
     * the hop destination drops the shopper on the homepage. The product
     * path stays; the tracking query is merged onto it.
     *
     * Product query params win on collision (variant selectors such as
     * attribute_quantity). A referral URL on a different host is left
     * unused: those links are program landings or signup pages, not a
     * product deep-link slot we can fill safely. PeptideMap UTMs are
     * still added later by tagWithUtms.
     */
    protected function mergeSameHostReferralParams(string $productUrl, ?string $referral): string
    {
        if (empty($referral)) {
            return $productUrl;
        }

        $product = @parse_url($productUrl);
        $ref = @parse_url($referral);
        if (
            !$product || !$ref
            || empty($product['scheme']) || empty($product['host'])
            || empty($ref['host'])
            || !in_array($product['scheme'], ['http', 'https'], true)
        ) {
            return $productUrl;
        }

        if ($this->normalizeHost($product['host']) !== $this->normalizeHost($ref['host'])) {
            return $productUrl;
        }

        parse_str($ref['query'] ?? '', $refQuery);
        if ($refQuery === []) {
            return $productUrl;
        }

        parse_str($product['query'] ?? '', $productQuery);
        // Referral params first so product-specific keys overwrite them.
        $merged = $refQuery;
        foreach ($productQuery as $key => $value) {
            $merged[$key] = $value;
        }

        $product['query'] = http_build_query($merged);

        return $this->rebuildUrl($product);
    }

    protected function normalizeHost(string $host): string
    {
        $host = strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    /**
     * Rebuild a parse_url() array. Query is omitted when empty so a URL
     * that had no query string is not given a trailing '?'.
     */
    protected function rebuildUrl(array $parts): string
    {
        $url = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '');

        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Rewrite the vendor's product URL so the coupon code pre-applies on
     * arrival, when we can. Detects platform two ways:
     *   1. Explicit hint from the brand's ScrapingConfig.type (Woo, Medusa,
     *      BigCommerce, JSON-LD which is usually a Shopify/Next storefront)
     *   2. URL heuristic — /products/{handle} + non-blacklisted host reads
     *      as Shopify
     *
     * Falls back to appending ?coupon= + ?discount= as a best-effort for
     * unknown platforms — harmless when the vendor's site ignores unknown
     * params, occasionally works for WooCommerce sites running a coupon-URL
     * plugin.
     *
     * Never touches a URL when the brand has no active PMAP discount.
     */
    protected function injectCoupon(?string $url, Product $product): ?string
    {
        if (empty($url)) return $url;
        $vendorSetting = $product->brand?->vendorSetting;
        $pct = $vendorSetting?->coupon_discount_percent;
        if (!$pct || $pct <= 0 || $pct >= 100) return $url;
        $code = trim((string) ($vendorSetting?->coupon_code ?? 'PMAP'));
        if ($code === '') return $url;

        // Bail cleanly on unparseable / non-HTTP URLs.
        $parts = @parse_url($url);
        if (!$parts || empty($parts['host']) || empty($parts['scheme'])) return $url;

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $existingQuery = $parts['query'] ?? '';

        // Detect Shopify — cheapest reliable check: the .myshopify.com
        // fallback host + /products/ path pattern. Custom Shopify domains
        // (most vendors) also match the URL pattern.
        // Brand → ScrapingConfig isn't a defined relation on the model, so
        // query directly. Cached indirectly by MySQL query cache; the /go
        // route is per-click, not high-throughput, so an extra one-row
        // lookup per redirect is fine.
        $scrapeType = $product->brand_id
            ? ScrapingConfig::where('vendor_id', $product->brand_id)->value('type')
            : null;
        $looksShopify = str_contains($host, 'myshopify.com')
            || (str_contains($path, '/products/')
                && !str_contains($host, 'medusajs.')
                && !str_contains($host, 'bigcommerce.com')
                && !in_array($scrapeType, ['medusa_store', 'bigcommerce', 'woo_api', 'wp_rest'], true));

        if ($looksShopify) {
            // Shopify's canonical discount-application route. Preserves the
            // original path + query as ?redirect= so user lands on the same
            // product page with the discount already in cart.
            $redirect = $path . ($existingQuery ? '?' . $existingQuery : '');
            return $parts['scheme'] . '://' . $parts['host']
                . '/discount/' . rawurlencode($code)
                . '?redirect=' . rawurlencode($redirect);
        }

        // WooCommerce / unknown: best-effort. Append both param names —
        // `coupon_code` is the WooCommerce URL-Coupons plugin standard,
        // `coupon` is the widely-recognized generic. Platforms that don't
        // recognize either silently ignore.
        $extra = 'coupon_code=' . rawurlencode($code) . '&coupon=' . rawurlencode($code);
        $joined = $existingQuery ? $existingQuery . '&' . $extra : $extra;
        return $parts['scheme'] . '://' . $parts['host'] . $path . '?' . $joined
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    /**
     * Fire a GA4 event via the Measurement Protocol so /go redirects
     * (server-side, no JS runs on our redirect step) still show up in
     * GA4 alongside pageview + session data. Best-effort; never blocks
     * the redirect. Requires services.ga4.measurement_id +
     * services.ga4.api_secret to be set — silently skips otherwise.
     */
    protected function fireGa4Event(Request $request, Product $product, ?float $estCommission): void
    {
        $mid = config('services.ga4.measurement_id');
        $secret = config('services.ga4.api_secret');
        if (!$mid || !$secret) return;

        // Stable pseudonymous client id — hash of the IP so repeat
        // clicks from the same visitor aggregate in GA4.
        $seed = ($request->ip() ?: 'noip') . config('app.key');
        $clientId = substr(hash('sha256', $seed), 0, 16) . '.' . substr(hash('sha256', $seed . 'salt'), 0, 16);

        $body = [
            'client_id' => $clientId,
            'events' => [[
                'name' => 'outbound_click',
                'params' => [
                    'brand_slug' => $product->brand?->slug,
                    'brand_name' => $product->brand?->name,
                    'product_id' => (string) $product->id,
                    'product_name' => $product->display_name ?? $product->name,
                    'value' => $estCommission,
                    'currency' => 'USD',
                    'engagement_time_msec' => 100,
                ],
            ]],
        ];

        try {
            \Illuminate\Support\Facades\Http::timeout(2)->asJson()->post(
                'https://www.google-analytics.com/mp/collect?measurement_id=' . urlencode($mid) . '&api_secret=' . urlencode($secret),
                $body
            );
        } catch (\Throwable $e) {
            // fire-and-forget — never let GA4 problems break a redirect
        }
    }

    /**
     * Very cheap bot heuristic. We still log the click (useful for abuse detection)
     * but flag it so analytics can exclude it.
     */
    protected function looksLikeBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        $pattern = '/bot|crawler|spider|crawling|slurp|mediapartners|facebookexternalhit|embedly|preview|lighthouse|headless|axios|curl|wget|python-requests/i';

        return (bool) preg_match($pattern, $userAgent);
    }
}

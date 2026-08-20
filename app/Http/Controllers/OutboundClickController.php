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
        $product->loadMissing('brand');

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
            ]);
        } catch (\Throwable $e) {
            \Log::warning('ProductClick logging failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
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

        $rebuilt = $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '')
            . '?' . $parts['query']
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        return $rebuilt;
    }

    /**
     * Resolve the outbound URL. Priority order:
     *   1. vendor_settings.referral_url — a single full URL per vendor,
     *      typically their affiliate program's Referral Link. When set,
     *      every outbound click goes there (matches how the affiliate
     *      programs actually credit us — a single tracked entry point).
     *   2. brands.affiliate_url_template — legacy per-product templating.
     *   3. products.product_url — raw scraped URL, coupon-injected when
     *      the brand has a configured PMAP discount.
     */
    protected function resolveDestinationUrl(Product $product): ?string
    {
        // 1. Vendor-wide referral URL wins if configured. Don't touch it —
        //    affiliate landing pages carry their own tracking and can break
        //    when unknown query params are appended.
        $referral = $product->brand?->vendorSetting?->referral_url;
        if (!empty($referral)) {
            return $referral;
        }

        $template = $product->brand?->affiliate_url_template;
        $productUrl = $product->product_url;

        if (empty($template)) {
            // Path 3: raw scraped URL. Try to inject the vendor's coupon
            // via a platform-appropriate URL scheme so the discount
            // pre-applies at the vendor's checkout without the user having
            // to copy/paste PMAP manually.
            return $this->injectCoupon($productUrl, $product);
        }

        $replacements = [
            '{product_url}' => $productUrl ?? '',
            '{slug}' => $product->slug ?? '',
            '{id}' => (string) $product->id,
            '{affiliate_tag}' => (string) ($product->brand?->affiliate_tag ?? ''),
        ];

        $resolved = strtr($template, $replacements);

        // If the template required {product_url} but we had none, fall back.
        if (str_contains($template, '{product_url}') && empty($productUrl)) {
            return $productUrl;
        }

        return $resolved;
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

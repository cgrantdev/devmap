<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductClick;
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
            return redirect()->route('product.detail', [
                'slug' => $product->slug ?? 'product',
                'id' => $product->id,
            ]);
        }

        $userAgent = (string) $request->userAgent();
        $isBot = $this->looksLikeBot($userAgent);

        // Fire-and-forget log. Wrap in try to never block the redirect.
        try {
            ProductClick::create([
                'product_id' => $product->id,
                'brand_id' => $product->brand_id,
                'user_id' => Auth::id(),
                'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . config('app.key')) : null,
                'user_agent' => mb_substr($userAgent, 0, 512),
                'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 1024),
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
     * Resolve the outbound URL using the brand's affiliate template if set,
     * otherwise falling back to the raw product_url.
     */
    protected function resolveDestinationUrl(Product $product): ?string
    {
        $template = $product->brand?->affiliate_url_template;
        $productUrl = $product->product_url;

        if (empty($template)) {
            return $productUrl;
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

<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a single row into `page_views` for GET requests that render one of the
 * public marketing pages we care about (home, brands index, brand storefront,
 * product detail, compare, compound/category pages). Everything else — API
 * calls, admin/vendor dashboards, assets, non-HTML responses — is skipped.
 */
class LogPageView
{
    /**
     * Map route name → page_type. Only routes listed here are logged.
     */
    private const ROUTE_TYPE = [
        'home'                => 'home',
        'brands'              => 'brands_index',
        'brand.products'      => 'brand',
        'product.public'      => 'product',
        'compare'             => 'compare',
        'compare.show'        => 'compare',
        'encyclopedia'        => 'encyclopedia',
        'encyclopedia.show'   => 'compound',
        'category.show'       => 'category',
        'products'            => 'products_index',
        'products.index'      => 'products_index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($request->method() !== 'GET') return $response;
            if ($response->getStatusCode() >= 300) return $response;

            $route = $request->route();
            $routeName = $route?->getName();
            if (!$routeName || !isset(self::ROUTE_TYPE[$routeName])) return $response;

            $pageType = self::ROUTE_TYPE[$routeName];

            // Resolve brand_id / product_id from route params where possible.
            $brandId = null;
            $productId = null;
            if ($pageType === 'brand') {
                $slug = $route->parameter('vendor_name') ?? $route->parameter('slug');
                if ($slug) {
                    $brandId = Brand::where('slug', $slug)->value('id');
                }
            } elseif ($pageType === 'product') {
                $productId = $route->parameter('id');
                if (is_numeric($productId)) $productId = (int) $productId;
                else $productId = null;
            }

            $ua = (string) $request->userAgent();

            PageView::insert([[
                'page_type'  => $pageType,
                'path'       => mb_substr($request->path(), 0, 1024),
                'route_name' => $routeName,
                'brand_id'   => $brandId,
                'product_id' => $productId,
                'user_id'    => $request->user()?->id,
                'ip_hash'    => hash('sha256', $request->ip() . config('app.key')),
                'session_id' => substr(hash('sha256', $request->cookie('XSRF-TOKEN', '') . $request->ip() . $ua), 0, 32),
                'user_agent' => mb_substr($ua, 0, 512),
                'referrer'   => mb_substr((string) $request->headers->get('referer'), 0, 1024) ?: null,
                'is_bot'     => $this->looksLikeBot($ua),
                'created_at' => now(),
            ]]);
        } catch (\Throwable $e) {
            // Analytics must never break a page render.
            report($e);
        }

        return $response;
    }

    private function looksLikeBot(string $ua): bool
    {
        if ($ua === '') return true;
        return (bool) preg_match('/bot|crawl|spider|slurp|bing|google|yandex|duckduck|baidu|facebookexternal|preview|monitor|curl|wget|python-requests|headless|lighthouse/i', $ua);
    }
}

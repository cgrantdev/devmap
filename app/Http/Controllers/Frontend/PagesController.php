<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SeoPage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class PagesController extends Controller
{
    /**
     * Safely truncate a string to a given length
     * Works without mbstring extension
     */
    private function safeLimit($value, $limit = 100, $end = '...')
    {
        if (empty($value)) {
            return '';
        }

        $value = strip_tags($value);
        
        // If mbstring is available, use it
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value) <= $limit) {
                return $value;
            }
            return mb_substr($value, 0, $limit) . $end;
        }
        
        // Fallback to regular string functions
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, $limit) . $end;
    }

    public function show(Request $request, $slug = null)
    {
        // If slug is not provided, try to get from route parameter
        if (!$slug) {
            $slug = $request->route('slug');
        }
        
        // Special handling for calculator page
        if ($slug === 'calculator') {
            // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "calculator")
            $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
            $defaultTitle = 'Peptide Calculator';
            $defaultDescription = 'Calculate peptide dosages and reconstitution volumes. Easy-to-use calculator for research peptide preparation.';

            $seoPage = SeoPage::where('key', 'calculator')->first();
            $seo = [
                'key' => 'calculator',
                'title' => $seoPage?->title ?: $defaultTitle,
                'description' => $seoPage?->description ?: $defaultDescription,
                'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
                'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
                'og_image' => $seoPage?->og_image ?: null,
                // Backward-compatible field used by some pages
                'image' => $seoPage?->og_image ?: null,
                'url' => url('/calculator'),
            ];

            // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
            session(['page_seo_data' => $seo]);
            
            return Inertia::render('Frontend/Calculator', [
                'seo' => $seo,
            ]);
        }
        
        // Find page by slug
        $page = Page::where('slug', $slug)->first();
        
        // If page doesn't exist, return 404
        if (!$page) {
            abort(404, 'Page not found');
        }
        
        // Generate SEO data for dynamic page
        $pageTitle = $page->seo_title ?? $page->title;
        $pageDescription = $page->seo_description ?? ($page->content ? $this->safeLimit($page->content, 160) : '');
        $seoData = new SEOData(
            title: $pageTitle . ' | PeptideSync',
            description: $pageDescription ?: 'Learn more about ' . $page->title,
            url: url("/{$slug}"),
        );
        session(['page_seo_data' => $seoData]);
        
        // Render the unified Page component
        return Inertia::render('Frontend/Page', [
            'page' => $page,
        ]);
    }
}

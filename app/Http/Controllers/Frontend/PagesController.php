<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class PagesController extends Controller
{
    public function show(Request $request, $slug = null)
    {
        // If slug is not provided, try to get from route parameter
        if (!$slug) {
            $slug = $request->route('slug');
        }
        
        // Special handling for calculator page
        if ($slug === 'calculator') {
            $seoData = new SEOData(
                title: 'Peptide Calculator | PeptideSync',
                description: 'Calculate peptide dosages and reconstitution volumes. Easy-to-use calculator for research peptide preparation.',
                url: url('/calculator'),
            );
            session(['page_seo_data' => $seoData]);
            return Inertia::render('Frontend/Calculator');
        }
        
        // Find page by slug
        $page = Page::where('slug', $slug)->first();
        
        // If page doesn't exist, return 404
        if (!$page) {
            abort(404, 'Page not found');
        }
        
        // Generate SEO data for dynamic page
        $pageTitle = $page->seo_title ?? $page->title;
        $pageDescription = $page->seo_description ?? ($page->content ? Str::limit(strip_tags($page->content), 160) : '');
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

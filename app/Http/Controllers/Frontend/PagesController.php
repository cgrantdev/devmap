<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            return Inertia::render('Frontend/Calculator');
        }
        
        // Find page by slug
        $page = Page::where('slug', $slug)->first();
        
        // If page doesn't exist, return 404
        if (!$page) {
            abort(404, 'Page not found');
        }
        
        // Render the unified Page component
        return Inertia::render('Frontend/Page', [
            'page' => $page,
        ]);
    }
}

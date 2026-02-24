<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class BecomeVendorController extends Controller
{
    /**
     * Show the become a vendor page
     */
    public function index(Request $request)
    {
        $step = $request->get('step', 1);
        $step = max(1, min(4, (int) $step)); // Ensure step is between 1 and 4

        $locations = Location::orderBy('name')->get(['id', 'name']);

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Become a Vendor - Join PeptideSync Marketplace | PeptideSync',
            description: 'Join PeptideSync as a vendor and reach thousands of researchers. List your products, manage inventory, and grow your peptide business.',
            url: url('/become-a-vendor'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/BecomeVendor', [
            'step' => $step,
            'locations' => $locations,
        ]);
    }
}

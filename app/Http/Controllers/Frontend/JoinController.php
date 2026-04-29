<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class JoinController extends Controller
{
    /**
     * Standalone vendor invitation landing page (join.peptidemap.com).
     *
     * Renders a prestigious "you've been invited" hero plus the same
     * 4-step vendor signup form. Submits to BecomeVendorController@store.
     *
     * Optional query params:
     *   ?company=Acme  → personalized greeting + prefills companyName
     *   ?ref=2026q2    → tracking code (passed through, not used yet)
     */
    public function show(Request $request)
    {
        $step = max(1, min(4, (int) $request->get('step', 1)));

        $locations = Location::orderBy('name')->get(['id', 'name']);

        $invitation = [
            'company' => trim((string) $request->query('company', '')) ?: null,
            'ref' => trim((string) $request->query('ref', '')) ?: null,
        ];

        $seoData = new SEOData(
            title: 'Private Invitation | PeptideMap',
            description: 'You\'ve been invited to join PeptideMap as a verified vendor partner.',
            url: url('/join'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Join', [
            'step' => $step,
            'locations' => $locations,
            'invitation' => $invitation,
        ]);
    }
}

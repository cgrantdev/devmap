<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        return Inertia::render('Frontend/BecomeVendor', [
            'step' => $step,
            'locations' => $locations,
        ]);
    }
}

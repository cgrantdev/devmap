<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\VendorReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorReviewsController extends Controller
{
    /**
     * Store a new review for a vendor
     */
    public function store(Request $request, $brandId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
        ]);

        $brand = Brand::findOrFail($brandId);

        // Check if user is authenticated
        $userId = Auth::id();
        
        // If not authenticated, require name and email
        if (!$userId) {
            if (empty($validated['user_name']) || empty($validated['user_email'])) {
                return back()->withErrors([
                    'user_name' => 'Name and email are required for guest reviews.',
                    'user_email' => 'Name and email are required for guest reviews.',
                ])->withInput();
            }
        }

        // Check if user already reviewed this vendor
        if ($userId) {
            $existingReview = VendorReview::where('brand_id', $brandId)
                ->where('user_id', $userId)
                ->first();
            
            if ($existingReview) {
                return back()->withErrors([
                    'rating' => 'You have already reviewed this vendor.',
                ])->withInput();
            }
        }

        // Create review (pending approval by default)
        $review = VendorReview::create([
            'brand_id' => $brandId,
            'user_id' => $userId,
            'user_name' => $validated['user_name'] ?? Auth::user()->name ?? null,
            'user_email' => $validated['user_email'] ?? Auth::user()->email ?? null,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'is_approved' => false, // Require admin approval
        ]);

        return back()->with('success', 'Thank you for your review! It will be published after admin approval.');
    }

    /**
     * Get reviews for a vendor
     */
    public function index($brandId)
    {
        $brand = Brand::findOrFail($brandId);
        
        $reviews = $brand->approvedReviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }
}

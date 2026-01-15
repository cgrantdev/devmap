<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\VendorReview;
use App\Helpers\ActivityLogger;
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
            'rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'shipping_time' => 'required|integer|min:1|max:5',
            'customer_service' => 'required|integer|min:1|max:5',
            'quality' => 'required|integer|min:1|max:5',
            'cost' => 'required|integer|min:1|max:5',
            'packaging' => 'required|integer|min:1|max:5',
        ]);

        // Calculate overall rating from the 5 categories
        $overallRating = round(
            ($validated['shipping_time'] + 
             $validated['customer_service'] + 
             $validated['quality'] + 
             $validated['cost'] + 
             $validated['packaging']) / 5
        );

        $brand = Brand::findOrFail($brandId);

        // Get user ID if authenticated (optional)
        $userId = Auth::id();

        // Check if this email has already reviewed this vendor (prevent duplicate reviews)
        $existingReview = VendorReview::where('brand_id', $brandId)
            ->where('user_email', $validated['user_email'])
            ->first();
        
        if ($existingReview) {
            return back()->withErrors([
                'user_email' => 'You have already submitted a review for this vendor with this email address.',
            ])->withInput();
        }

        // Create review (auto-approved so rating updates immediately)
        $review = VendorReview::create([
            'brand_id' => $brandId,
            'user_id' => $userId, // null for guest reviews
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'rating' => $overallRating,
            'review' => $validated['review'] ?? null,
            'is_approved' => true, // Auto-approve so rating updates immediately
            'shipping_time' => $validated['shipping_time'],
            'customer_service' => $validated['customer_service'],
            'quality' => $validated['quality'],
            'cost' => $validated['cost'],
            'packaging' => $validated['packaging'],
        ]);

        // The updateBrandRating will be triggered automatically via the model's boot method
        // since is_approved is true

        // Log activity
        ActivityLogger::reviewSubmitted($brand->name, $review->id);

        return back()->with('success', 'Thank you for your review!');
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorReview;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class ReviewsController extends Controller
{
    public function index()
    {
        $reviews = VendorReview::with('brand')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'comment' => $review->review, // Model uses 'review' field
                    'rating' => $review->rating,
                    'status' => $review->status ?? ($review->is_approved ? 'approved' : 'pending'),
                    'verified' => $review->verified ?? false,
                    'created_at' => $review->created_at,
                    'brand' => $review->brand ? [
                        'id' => $review->brand->id,
                        'name' => $review->brand->name,
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Reviews', [
            'reviews' => $reviews
        ]);
    }

    public function approve($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->is_approved = true;
        
        // Only set status if column exists
        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $review->status = 'approved';
        }
        
        $review->save();

        return redirect()->back()->with('success', 'Review approved successfully.');
    }

    public function reject($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->is_approved = false;
        
        // Only set status if column exists
        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $review->status = 'rejected';
        }
        
        $review->save();

        return redirect()->back()->with('success', 'Review rejected successfully.');
    }

    public function destroy($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}


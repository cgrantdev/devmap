<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorReview;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReviewsController extends Controller
{
    public function index()
    {
        $reviews = VendorReview::with('brand')
            ->latest()
            ->get()
            ->map(function (VendorReview $review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'user_email' => $review->user_email,
                    'comment' => $review->review,
                    'rating' => $review->rating,
                    'status' => $review->status,
                    'verified' => (bool) ($review->verified ?? false),
                    'flagged' => (bool) ($review->flagged ?? false),
                    'flag_reason' => $review->flag_reason,
                    'vendor_reply' => $review->vendor_reply,
                    'vendor_replied_at' => $review->vendor_replied_at,
                    'shipping_time' => $review->shipping_time,
                    'customer_service' => $review->customer_service,
                    'quality' => $review->quality,
                    'cost' => $review->cost,
                    'packaging' => $review->packaging,
                    'created_at' => optional($review->created_at)->toIso8601String(),
                    'brand' => $review->brand ? [
                        'id' => $review->brand->id,
                        'name' => $review->brand->name,
                    ] : null,
                ];
            })
            ->values();

        $stats = [
            'total' => $reviews->count(),
            'flagged' => $reviews->where('flagged', true)->count(),
            'pending' => $reviews->where('status', 'pending')->count(),
            'approved' => $reviews->where('status', 'approved')->count(),
            'rejected' => $reviews->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Reviews', [
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    public function approve($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->is_approved = true;
        $review->flagged = false;
        $review->flag_reason = null;

        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $review->status = 'approved';
        }

        $review->save();

        return back()->with('success', 'Review approved successfully.');
    }

    public function reject($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->is_approved = false;
        $review->flagged = false;
        $review->flag_reason = null;

        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $review->status = 'rejected';
        }

        $review->save();

        return back()->with('success', 'Review rejected successfully.');
    }

    public function destroy($id)
    {
        $review = VendorReview::findOrFail($id);
        $review->delete();

        return back()->with('success', 'Review deleted successfully.');
    }
}

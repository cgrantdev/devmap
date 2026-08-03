<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\VendorReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountReviewsController extends Controller
{
    /**
     * List the current user's reviews (any status), grouped by vendor.
     */
    public function index()
    {
        $userId = Auth::id();

        $reviews = VendorReview::with('brand')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $verifiedMap = VendorReview::computeVerifiedMap($reviews);

        $grouped = $reviews
            ->map(function (VendorReview $review) use ($verifiedMap) {
                return [
                    'id' => $review->id,
                    'brand_id' => $review->brand_id,
                    'brand_name' => $review->brand->name ?? 'Unknown vendor',
                    'brand_slug' => $review->brand->slug ?? null,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'status' => $review->status,
                    'verified' => $verifiedMap[$review->id] ?? false,
                    'vendor_reply' => $review->vendor_reply,
                    'shipping_time' => $review->shipping_time,
                    'customer_service' => $review->customer_service,
                    'quality' => $review->quality,
                    'cost' => $review->cost,
                    'packaging' => $review->packaging,
                    'created_at' => optional($review->created_at)->toIso8601String(),
                ];
            })
            ->groupBy('brand_name')
            ->map(fn ($group) => $group->values())
            ->values();

        return Inertia::render('Frontend/Account/Reviews', [
            'reviewsByVendor' => $grouped,
        ]);
    }

    /**
     * Owner-only edit. Rejected reviews are frozen. Editing re-queues the
     * review for moderation (status -> pending) since the content changed.
     */
    public function update(Request $request, $id)
    {
        $review = VendorReview::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if ($review->status === 'rejected') {
            return back()->withErrors(['review' => 'This review was rejected and can no longer be edited.']);
        }

        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'shipping_time' => 'required|integer|min:1|max:5',
            'customer_service' => 'required|integer|min:1|max:5',
            'quality' => 'required|integer|min:1|max:5',
            'cost' => 'required|integer|min:1|max:5',
            'packaging' => 'required|integer|min:1|max:5',
        ]);

        $overallRating = round(
            ($validated['shipping_time'] +
             $validated['customer_service'] +
             $validated['quality'] +
             $validated['cost'] +
             $validated['packaging']) / 5
        );

        $review->update([
            'rating' => $overallRating,
            'review' => $validated['review'] ?? null,
            'shipping_time' => $validated['shipping_time'],
            'customer_service' => $validated['customer_service'],
            'quality' => $validated['quality'],
            'cost' => $validated['cost'],
            'packaging' => $validated['packaging'],
            'status' => 'pending',
            'is_approved' => false,
        ]);

        return back()->with('success', 'Review updated. It will re-appear once re-approved.');
    }

    /**
     * Owner-only delete. Rejected reviews are frozen (kept for audit).
     */
    public function destroy($id)
    {
        $review = VendorReview::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if ($review->status === 'rejected') {
            return back()->withErrors(['review' => 'This review was rejected and can no longer be modified.']);
        }

        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}

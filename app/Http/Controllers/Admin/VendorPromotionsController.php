<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\VendorPromotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CRUD for the four stackable promo shapes (Colin PMAP #3).
 * Nested under a specific vendor — Julia adds a promo from that
 * vendor's edit page. All endpoints redirect back to the vendor
 * edit page with a flash message so the panel state refreshes.
 */
class VendorPromotionsController extends Controller
{
    public function store(Request $request, int $brandId)
    {
        $brand = Brand::findOrFail($brandId);
        $validated = $this->validated($request);
        $validated = $this->normalizeTimestamps($validated);

        $validated['brand_id'] = $brand->id;
        VendorPromotion::create($validated);

        return back()->with('flash_success', 'Promotion created.');
    }

    public function update(Request $request, int $brandId, int $id)
    {
        $brand = Brand::findOrFail($brandId);
        $promo = VendorPromotion::where('brand_id', $brand->id)->findOrFail($id);
        $validated = $this->validated($request);
        $validated = $this->normalizeTimestamps($validated);

        $promo->update($validated);

        return back()->with('flash_success', 'Promotion updated.');
    }

    public function destroy(int $brandId, int $id)
    {
        $brand = Brand::findOrFail($brandId);
        $promo = VendorPromotion::where('brand_id', $brand->id)->findOrFail($id);
        $promo->delete();

        return back()->with('flash_success', 'Promotion removed.');
    }

    /**
     * The admin UI uses <input type="datetime-local">, which submits a
     * naive "YYYY-MM-DDTHH:MM" string with no zone. Laravel's `date`
     * validator + the model's datetime cast then treat that string as
     * the app timezone (UTC), so "11:59 PM" becomes 23:59 UTC = 7:59
     * PM EDT — the exact 4-hour shift Julia flagged on Oct 2.
     *
     * Julia enters times in ET (America/New_York handles the EDT/EST
     * swap). Re-parse in that zone, convert to UTC, hand Carbon
     * instances back so the cast stores them correctly. Mirrors
     * VendorsController::applyCouponBoost's handling.
     */
    private function normalizeTimestamps(array $validated): array
    {
        $tz = new \DateTimeZone('America/New_York');
        foreach (['starts_at', 'ends_at'] as $k) {
            if (!empty($validated[$k])) {
                $validated[$k] = \Carbon\Carbon::parse($validated[$k], $tz)->utc();
            }
        }
        return $validated;
    }

    private function validated(Request $request): array
    {
        $type = $request->input('promo_type');

        return $request->validate([
            'promo_type' => 'required|string|in:' . implode(',', array_keys(VendorPromotion::TYPES)),
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            // Percent required for non-BOGO shapes.
            'percent' => $type === VendorPromotion::TYPE_BOGO
                ? 'nullable|numeric|min:0|max:100'
                : 'required|numeric|min:0|max:100',
            // Code required for coupon_sitewide.
            'code' => $type === VendorPromotion::TYPE_COUPON_SITEWIDE
                ? 'required|string|max:64'
                : 'nullable|string|max:64',
            // Category required for category type.
            'product_category_id' => $type === VendorPromotion::TYPE_CATEGORY
                ? 'required|exists:product_categories,id'
                : 'nullable|exists:product_categories,id',
            'terms' => 'nullable|string|max:2000',
            'stacks_with_affiliate' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);
    }
}

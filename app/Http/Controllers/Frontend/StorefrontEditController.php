<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Inline "edit-my-own-storefront" endpoint.
 *
 * When a logged-in vendor visits their OWN /brand/{slug} page, the Vue
 * layer surfaces edit affordances on a whitelist of fields. Each save
 * fires a single POST here — one field at a time so the UX can be
 * save-on-blur without needing to gather the whole form.
 *
 * Auth: request user must own the brand (Brand.user_id === Auth::id()).
 * Anyone else — including admins hitting this by mistake — gets 403.
 * (Admins already have a proper vendor-edit form at /admin/vendors/{id}/edit.)
 */
class StorefrontEditController extends Controller
{
    // Whitelist of vendor-editable fields. Split between the Brand model
    // and its VendorSetting relation. Values arrive as strings; per-field
    // validation is applied below.
    private const BRAND_FIELDS = [
        'name',
    ];

    private const VENDOR_SETTING_FIELDS = [
        'description',
        'shipping_info',
        'return_policy',
        'business_hours',
        'coupon_code',
        'contact_email',
        'phone_number',
        'shop_url',
        'website',
        'trustpilot_url',
        'google_reviews_url',
        'reviews_io_url',
        'pepreviewpro_url',
    ];

    public function update(Request $request, string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $user = Auth::user();
        if (!$user || $user->id !== $brand->user_id) {
            return response()->json(['error' => 'Not your brand.'], 403);
        }

        $field = (string) $request->input('field');
        $value = $request->input('value');

        // Validate per-field. Simple whitelist + type check by field family.
        $rules = $this->rulesFor($field);
        if (!$rules) {
            return response()->json(['error' => "Field '{$field}' not editable inline."], 422);
        }

        $validated = $request->validate(['value' => $rules]);
        $clean = $validated['value'];

        // Apply to whichever table owns the field.
        if (in_array($field, self::BRAND_FIELDS, true)) {
            $brand->update([$field => $clean]);
        } elseif (in_array($field, self::VENDOR_SETTING_FIELDS, true)) {
            $vs = $brand->vendorSetting;
            if (!$vs) {
                return response()->json(['error' => 'No vendorSetting row on this brand.'], 500);
            }
            $vs->update([$field => $clean]);
        } else {
            return response()->json(['error' => "Field '{$field}' not editable inline."], 422);
        }

        return response()->json(['ok' => true, 'field' => $field, 'value' => $clean]);
    }

    private function rulesFor(string $field): ?array
    {
        return match ($field) {
            'name'               => ['required', 'string', 'max:191'],
            'description'        => ['nullable', 'string', 'max:5000'],
            'shipping_info'      => ['nullable', 'string', 'max:5000'],
            'return_policy'      => ['nullable', 'string', 'max:5000'],
            'business_hours'     => ['nullable', 'string', 'max:500'],
            'coupon_code'        => ['nullable', 'string', 'max:64'],
            'contact_email'      => ['nullable', 'email', 'max:191'],
            'phone_number'       => ['nullable', 'string', 'max:64'],
            'shop_url', 'website',
            'trustpilot_url', 'google_reviews_url', 'reviews_io_url', 'pepreviewpro_url'
                                 => ['nullable', 'url:http,https', 'max:512'],
            default              => null,
        };
    }
}

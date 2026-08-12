<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * Vendor Push API — Path 2 on /vendors/integration.
 *
 * Auth: Authorization: Bearer <token> (see VendorApiAuth middleware).
 * Idempotency: (brand_id, external_id) — vendors send their own SKU/id and
 * we upsert in place. auto_update is forced OFF on API-managed products so
 * our scrapers never overwrite what the vendor pushed.
 */
class VendorProductsController extends Controller
{
    private const SIZE_REGEX = '/^[0-9]+(?:\.[0-9]+)?\s?(?:mcg|mg|g|iu|ml)?(?:\/[0-9]+(?:\.[0-9]+)?\s?(?:mcg|mg|g|iu|ml)?)*$/i';
    private const ALLOWED_TYPES = ['Peptide', 'Capsule', 'Nasal Spray', 'Topical', 'Kit', 'Other'];

    // Cap so a runaway loop on the vendor's side can't push 100k rows in one
    // request. Anything larger should be paginated.
    private const BULK_MAX = 500;

    public function index(Request $request): JsonResponse
    {
        $brandId = $request->attributes->get('vendor_brand_id');
        $products = Product::where('brand_id', $brandId)
            ->orderBy('id')
            ->limit(1000)
            ->get(['id', 'external_id', 'name', 'slug', 'price', 'discount_price',
                   'size_mg', 'product_type', 'product_category_id', 'status',
                   'hidden', 'image_url', 'product_url', 'updated_at']);

        return response()->json([
            'count' => $products->count(),
            'products' => $products,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $brandId = $request->attributes->get('vendor_brand_id');
        $payload = $this->validated($request->all());

        if (isset($payload['errors'])) {
            return response()->json($payload, 422);
        }

        [$product, $created] = $this->upsertOne($brandId, $payload);

        return response()->json([
            'ok' => true,
            'created' => $created,
            'product' => [
                'id' => $product->id,
                'external_id' => $product->external_id,
                'url' => $product->brand?->slug
                    ? "/product/{$product->brand->slug}/{$product->slug}/{$product->id}"
                    : "/product/{$product->slug}/{$product->id}",
            ],
        ], $created ? 201 : 200);
    }

    public function bulk(Request $request): JsonResponse
    {
        $brandId = $request->attributes->get('vendor_brand_id');
        $items = $request->input('products', []);

        if (!is_array($items) || empty($items)) {
            return response()->json(['error' => 'products[] required'], 422);
        }
        if (count($items) > self::BULK_MAX) {
            return response()->json(['error' => 'Max ' . self::BULK_MAX . ' products per bulk call.'], 422);
        }

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];
        foreach ($items as $i => $raw) {
            $validated = $this->validated($raw);
            if (isset($validated['errors'])) {
                $results['errors'][] = ['index' => $i, 'external_id' => $raw['external_id'] ?? null, 'errors' => $validated['errors']];
                continue;
            }
            try {
                [, $created] = $this->upsertOne($brandId, $validated);
                $created ? $results['created']++ : $results['updated']++;
            } catch (\Throwable $e) {
                Log::warning('vendor api bulk upsert failed', [
                    'brand_id' => $brandId, 'external_id' => $validated['external_id'] ?? null, 'err' => $e->getMessage(),
                ]);
                $results['errors'][] = ['index' => $i, 'external_id' => $validated['external_id'] ?? null, 'errors' => ['server' => $e->getMessage()]];
            }
        }
        return response()->json($results);
    }

    public function destroy(Request $request, string $externalId): JsonResponse
    {
        $brandId = $request->attributes->get('vendor_brand_id');
        $product = Product::where('brand_id', $brandId)
            ->where('external_id', $externalId)
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Soft-hide rather than hard delete so historical clicks/wishlists
        // don't dangle. Vendor can send another POST to bring it back.
        $product->update(['hidden' => true, 'status' => 'inactive']);

        return response()->json(['ok' => true, 'hidden' => true]);
    }

    /**
     * Validate and normalize one payload. Returns the validated array, or
     * ['errors' => [...]] on failure.
     */
    private function validated(array $raw): array
    {
        $v = Validator::make($raw, [
            'external_id' => ['required', 'string', 'max:191'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'category_slug' => ['nullable', 'string', 'max:191'],
            'size_mg' => ['nullable', 'string', 'max:64', 'regex:' . self::SIZE_REGEX],
            'product_type' => ['nullable', 'string', 'in:' . implode(',', self::ALLOWED_TYPES)],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'product_url' => ['nullable', 'url', 'max:1000'],
            'purity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'in_stock' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($v->fails()) {
            return ['errors' => $v->errors()->toArray()];
        }
        return $v->validated();
    }

    private function upsertOne(int $brandId, array $data): array
    {
        // Resolve category by slug if provided. Case-insensitive lookup —
        // some legacy slugs are mixed case (CJC-1295) so lowercase match wins.
        $categoryId = null;
        if (!empty($data['category_slug'])) {
            $cat = ProductCategory::whereRaw('LOWER(slug) = ?', [strtolower($data['category_slug'])])->first();
            if ($cat) {
                $categoryId = $cat->id;
            }
        }

        // status: default 'active'. When in_stock is explicitly false, mark
        // 'inactive' so listings filter it out — vendors don't have to DELETE.
        $status = 'active';
        if (array_key_exists('in_stock', $data) && $data['in_stock'] === false) {
            $status = 'inactive';
        }

        $existing = Product::where('brand_id', $brandId)
            ->where('external_id', $data['external_id'])
            ->first();

        $attrs = [
            'brand_id' => $brandId,
            'external_id' => $data['external_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'size_mg' => $data['size_mg'] ?? null,
            'product_type' => $data['product_type'] ?? null,
            'product_category_id' => $categoryId,
            'image_url' => $data['image_url'] ?? null,
            'product_url' => $data['product_url'] ?? null,
            'purity' => $data['purity'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $status,
            'hidden' => false,
            // Vendor-managed: never let the scraper overwrite pushed data.
            'auto_update' => false,
            'auto_scraped' => false,
            'last_scraped_at' => now(),
        ];

        if ($existing) {
            $existing->update($attrs);
            return [$existing->fresh('brand'), false];
        }

        // Ensure slug — derived from name plus external_id suffix for
        // uniqueness. Frontend URL builder uses this.
        $attrs['slug'] = Str::slug($data['name']) . '-' . Str::slug($data['external_id']);
        $product = Product::create($attrs);
        return [$product->fresh('brand'), true];
    }
}

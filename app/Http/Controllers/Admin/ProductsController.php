<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function create()
    {
        $categories = ProductCategory::orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        $brands = \App\Models\Brand::orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

        return Inertia::render('Admin/ProductEdit', [
            'product' => null,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['category', 'brand'])->findOrFail($id);

        $categories = ProductCategory::orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        $brands = \App\Models\Brand::orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

        return Inertia::render('Admin/ProductEdit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'brand_id' => $product->brand_id,
                'brand_name' => $product->brand?->name,
                'product_category_id' => $product->product_category_id,
                'category_name' => $product->category?->name,
                'size_mg' => $product->size_mg,
                'purity' => $product->purity,
                'availability' => $product->availability,
                'status' => $product->status,
                'hidden' => (bool) $product->hidden,
                'featured' => (bool) $product->featured,
                'lab_tested' => (bool) $product->lab_tested,
                'first_timer_deals' => (bool) $product->first_timer_deals,
                'auto_update' => (bool) $product->auto_update,
                'verified' => (bool) $product->verified,
                'rating_average' => $product->rating_average,
                'rating_count' => $product->rating_count,
                'image_url' => $product->image_url,
                'product_url' => $product->product_url,
                'seo_page_title' => $product->seo_page_title,
                'seo_description' => $product->seo_description,
                'seo_og_title' => $product->seo_og_title,
                'seo_og_description' => $product->seo_og_description,
                'seo_og_image' => $product->seo_og_image,
            ],
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'size_mg' => 'nullable|string|max:255',
            'original_price' => 'nullable|numeric|min:0',
            'purity' => 'nullable|numeric|min:0|max:100',
            'featured' => 'sometimes|boolean',
            'hidden' => 'sometimes|boolean',
            'lab_tested' => 'sometimes|boolean',
            'first_timer_deals' => 'sometimes|boolean',
            'auto_update' => 'sometimes|boolean',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ]);
        
        // Handle pricing logic:
        // If original_price is provided and greater than price, product is on sale
        // Store: price = original price, discount_price = sale price (the lower price)
        if (isset($validated['original_price']) && $validated['original_price'] > $validated['price']) {
            // Product is on sale
            $originalPrice = $validated['original_price'];
            $salePrice = $validated['price'];
            $validated['price'] = $originalPrice;
            $validated['discount_price'] = $salePrice;
        } else {
            // Not on sale, clear discount_price
            $validated['discount_price'] = null;
        }
        
        // Remove original_price from validated as it's not a direct column
        unset($validated['original_price']);
        
        // Set default values for new products
        $validated['status'] = 'active';
        $validated['availability'] = 'in_stock';
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        
        $product = Product::create($validated);
        
        return redirect()->back()
            ->with('success', 'Product created successfully.');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_id' => 'required|exists:brands,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'size_mg' => 'nullable|string|max:255',
            'original_price' => 'nullable|numeric|min:0',
            'purity' => 'nullable|numeric|min:0|max:100',
            'availability' => 'nullable|string|in:in_stock,out_of_stock,pre_order',
            'featured' => 'sometimes|boolean',
            'hidden' => 'sometimes|boolean',
            'lab_tested' => 'sometimes|boolean',
            'first_timer_deals' => 'sometimes|boolean',
            'auto_update' => 'sometimes|boolean',
            'verified' => 'sometimes|boolean',
            'image_url' => 'nullable|string|max:2048',
            'product_url' => 'nullable|string|max:2048',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ]);

        // Convert size_mg from text (e.g. "10mL", "5mg") to numeric
        if (isset($validated['size_mg'])) {
            $numericSize = preg_replace('/[^0-9.]/', '', $validated['size_mg']);
            $validated['size_mg'] = $numericSize !== '' ? (float) $numericSize : null;
        }

        // Handle pricing logic
        if (isset($validated['original_price']) && $validated['original_price'] > $validated['price']) {
            $originalPrice = $validated['original_price'];
            $salePrice = $validated['price'];
            $validated['price'] = $originalPrice;
            $validated['discount_price'] = $salePrice;
        } elseif (!isset($validated['discount_price'])) {
            $validated['discount_price'] = null;
        }

        unset($validated['original_price']);
        
        $product->update($validated);
        
        return redirect()->back()
            ->with('success', 'Product updated successfully.');
    }

    public function setHidden(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'hidden' => 'required|boolean',
        ]);

        $product->update([
            'hidden' => $validated['hidden'],
        ]);

        return redirect()->back()->with('success', 'Product visibility updated.');
    }

    public function setAutoUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'auto_update' => 'required|boolean',
        ]);

        $product->update([
            'auto_update' => $validated['auto_update'],
        ]);

        return redirect()->back()->with('success', 'Product auto-update setting updated.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;

        \DB::transaction(function () use ($product) {
            // Drop related rows so nothing dangles. Reviews are vendor-scoped
            // (not per-product), so they stay.
            \DB::table('product_clicks')->where('product_id', $product->id)->delete();
            $product->delete();
        });

        return redirect()->back()->with('success', "Deleted: {$name}");
    }

    /**
     * Inline single-field updates from the admin Products list.
     * Used by the VA-friendly inline dropdowns for category + size.
     * Accepts product_category_id and/or size_mg.
     */
    public function quickUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'product_category_id' => 'sometimes|nullable|exists:product_categories,id',
            // Accepts numbers (10mg) or blend ratios (5mg/5mg, 50mg/10mg/10mg).
            // Pattern: one or more "Nmg" tokens optionally separated by /.
            'size_mg' => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^[0-9]+(?:\.[0-9]+)?(?:mcg|mg|g)?(?:\/[0-9]+(?:\.[0-9]+)?(?:mcg|mg|g)?)*$/i'],
            'hidden' => 'sometimes|boolean',
            'product_type' => ['sometimes', 'nullable', 'string', 'in:Peptide,Capsule,Nasal Spray,Kit,Other'],
            'is_encyclopedia_thumb' => 'sometimes|boolean',
            'is_peptide_thumb' => 'sometimes|boolean',
        ]);

        // Only update fields that were actually sent (sometimes rule above
        // makes them optional). This lets the frontend send just one field.
        $update = [];
        if ($request->has('product_category_id')) {
            $update['product_category_id'] = $validated['product_category_id'] ?? null;
        }
        if ($request->has('size_mg')) {
            $update['size_mg'] = $validated['size_mg'] ?? null;
        }
        if ($request->has('hidden')) {
            $update['hidden'] = (bool) $validated['hidden'];
        }
        if ($request->has('product_type')) {
            $update['product_type'] = $validated['product_type'] ?? null;
        }
        if ($request->has('is_encyclopedia_thumb')) {
            $update['is_encyclopedia_thumb'] = (bool) $validated['is_encyclopedia_thumb'];
        }
        if ($request->has('is_peptide_thumb')) {
            $update['is_peptide_thumb'] = (bool) $validated['is_peptide_thumb'];
        }

        // Mutual exclusion within a category: only one product per category
        // can carry each thumb flag. When turning a flag ON, clear it on
        // every other product in the same category in the same transaction.
        $targetCategoryId = array_key_exists('product_category_id', $update)
            ? $update['product_category_id']
            : $product->product_category_id;

        \DB::transaction(function () use ($product, $update, $targetCategoryId) {
            if ($targetCategoryId) {
                foreach (['is_encyclopedia_thumb', 'is_peptide_thumb'] as $flag) {
                    if (!empty($update[$flag])) {
                        Product::where('product_category_id', $targetCategoryId)
                            ->where('id', '!=', $product->id)
                            ->where($flag, true)
                            ->update([$flag => false]);
                    }
                }
            }
            if (!empty($update)) {
                $product->update($update);
            }
        });

        return redirect()->back()->with('success', 'Product updated.');
    }

    /**
     * Bulk update a set of products. Accepts the same shape as quickUpdate
     * (product_category_id, hidden) but applied to many ids at once.
     * VA workflow: filter to "Missing Category", select-all, set category.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:products,id',
            'product_category_id' => 'sometimes|nullable|exists:product_categories,id',
            'hidden' => 'sometimes|boolean',
            'product_type' => ['sometimes', 'nullable', 'string', 'in:Peptide,Capsule,Nasal Spray,Kit,Other'],
        ]);

        $update = [];
        if ($request->has('product_category_id')) {
            $update['product_category_id'] = $validated['product_category_id'] ?? null;
        }
        if ($request->has('hidden')) {
            $update['hidden'] = (bool) $validated['hidden'];
        }
        if ($request->has('product_type')) {
            $update['product_type'] = $validated['product_type'] ?? null;
        }

        if (empty($update)) {
            return redirect()->back()->with('error', 'No fields provided to update.');
        }

        $count = Product::whereIn('id', $validated['ids'])->update($update);

        $what = [];
        if (array_key_exists('product_category_id', $update)) {
            $what[] = $update['product_category_id'] ? 'category' : 'cleared category';
        }
        if (array_key_exists('hidden', $update)) {
            $what[] = $update['hidden'] ? 'hidden from site' : 'unhidden';
        }
        if (array_key_exists('product_type', $update)) {
            $what[] = $update['product_type'] ? "type: {$update['product_type']}" : 'cleared type';
        }
        $summary = implode(' + ', $what);

        return redirect()->back()->with('success', "Updated {$count} product" . ($count === 1 ? '' : 's') . " ({$summary}).");
    }

    /**
     * Bulk delete: same cascade as destroy() but applied to a list.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $count = 0;
        \DB::transaction(function () use ($validated, &$count) {
            \DB::table('product_clicks')->whereIn('product_id', $validated['ids'])->delete();
            $count = Product::whereIn('id', $validated['ids'])->delete();
        });

        return redirect()->back()->with('success', "Deleted {$count} product" . ($count === 1 ? '' : 's') . '.');
    }
}


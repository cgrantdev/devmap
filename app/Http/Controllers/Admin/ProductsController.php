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
        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
}


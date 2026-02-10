<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function edit($id)
    {
        $product = Product::with('category')->findOrFail($id);
        
        // Get all categories for the select box
        $categories = ProductCategory::orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });
        
        return Inertia::render('Admin/ProductEdit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'product_category_id' => $product->product_category_id,
                'hidden' => (bool) $product->hidden,
            ],
            'categories' => $categories,
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


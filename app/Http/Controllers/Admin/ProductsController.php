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
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
        ]);
        
        $product->update($validated);
        
        return redirect()->route('admin.products')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
}


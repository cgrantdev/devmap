<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::withCount('products');
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortType = $request->get('sort_type', 'asc');
        
        // Validate sortBy
        $allowedSortColumns = ['id', 'name', 'slug', 'is_active', 'created_at', 'products_count'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'desc' ? 'desc' : 'asc';
        
        if ($sortBy === 'products_count') {
            $query->orderBy('products_count', $sortType);
        } else {
            $query->orderBy($sortBy, $sortType);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $categories = $query->paginate($perPage)
            ->through(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'is_active' => $category->is_active,
                    'products_count' => $category->products_count,
                    'created_at' => $category->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
        ]);
    }

    public function edit($id)
    {
        $category = ProductCategory::withCount('products')->findOrFail($id);
        
        // Find similar categories (by name similarity)
        $similarCategories = $this->findSimilarCategories($category);
        
        return Inertia::render('Admin/CategoryEdit', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
            ],
            'similarCategories' => $similarCategories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,' . $id,
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Ensure unique slug
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (ProductCategory::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }
        
        $category->update($validated);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function merge(Request $request, $id)
    {
        $request->validate([
            'target_category_id' => 'required|exists:product_categories,id',
        ]);
        
        $sourceCategory = ProductCategory::findOrFail($id);
        $targetCategory = ProductCategory::findOrFail($request->target_category_id);
        
        if ($sourceCategory->id === $targetCategory->id) {
            return redirect()->back()->with('error', 'Cannot merge category with itself.');
        }
        
        DB::beginTransaction();
        try {
            // Update all products to use the target category
            Product::where('product_category_id', $sourceCategory->id)
                ->update(['product_category_id' => $targetCategory->id]);
            
            // Delete the source category
            $sourceCategory->delete();
            
            DB::commit();
            
            return redirect()->route('admin.categories.index')
                ->with('success', "Category '{$sourceCategory->name}' merged into '{$targetCategory->name}' successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error merging categories: ' . $e->getMessage());
        }
    }

    private function findSimilarCategories(ProductCategory $category)
    {
        // Find categories with similar names (case-insensitive, partial match)
        $similar = ProductCategory::where('id', '!=', $category->id)
            ->where(function ($query) use ($category) {
                // Exact match (case-insensitive)
                $query->whereRaw('LOWER(name) = ?', [strtolower($category->name)])
                    // Contains the category name
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($category->name) . '%'])
                    // Category name contains the other name
                    ->orWhereRaw('? LIKE CONCAT("%", LOWER(name), "%")', [strtolower($category->name)]);
            })
            ->withCount('products')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'products_count' => $cat->products_count,
                ];
            });
        
        return $similar;
    }
}

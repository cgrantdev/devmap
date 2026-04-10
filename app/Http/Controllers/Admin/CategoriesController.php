<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Helpers\ImageHelper;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::withCount('products')
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image_url' => $category->image_url ? Storage::url('categories/' . $category->image_url) : null,
                    'is_active' => $category->is_active,
                    'products_count' => $category->products_count,
                    'created_at' => $category->created_at->format('M j, Y'),
                    'research_area' => $category->research_area,
                ];
            });

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CategoryEdit', [
            'category' => null,
            'similarCategories' => [],
            'products' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'research_area' => 'nullable|string|max:255',
        ]);
        
        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Ensure unique slug
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (ProductCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }
        
        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            $validated['image_url'] = ImageHelper::convertToWebP($request->file('image'), 'categories');
        } else {
            unset($validated['image']);
        }
        
        $category = ProductCategory::create($validated);

        return redirect("/admin/categories/{$category->id}/edit")
            ->with('success', 'Category created successfully.');
    }

    public function edit($id)
    {
        $category = ProductCategory::withCount('products')->findOrFail($id);
        
        // Find similar categories (by name similarity)
        $similarCategories = $this->findSimilarCategories($category);
        
        // Get products for this category
        $products = $category->products()
            ->with('brand')
            ->latest()
            ->get()
            ->map(function ($product) {
                // Process image URL - ensure it's a valid absolute URL
                $imageUrl = $product->image_url;
                if ($imageUrl) {
                    // Trim whitespace
                    $imageUrl = trim($imageUrl);
                    
                    // If it's already a full URL (starts with http:// or https://), use it as-is
                    if (preg_match('/^https?:\/\//', $imageUrl)) {
                        // Valid absolute URL, use as-is
                        $imageUrl = $imageUrl;
                    } elseif (!empty($imageUrl)) {
                        // If it's a relative path starting with /, make it absolute using the app URL
                        if (strpos($imageUrl, '/') === 0) {
                            $imageUrl = url($imageUrl);
                        } else {
                            // For other relative paths, try to construct a full URL
                            // Most product image_urls should be external URLs, so this is a fallback
                            $imageUrl = $imageUrl;
                        }
                    }
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                    'image_url' => $imageUrl ?: null, // Ensure null instead of empty string
                    'brand_name' => $product->brand ? $product->brand->name : '-',
                    'product_url' => $product->product_url,
                ];
            });
        
        return Inertia::render('Admin/CategoryEdit', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url ? Storage::url('categories/' . $category->image_url) : null,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
                'research_area' => $category->research_area,
            ],
            'similarCategories' => $similarCategories,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'image_url' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean',
            'research_area' => 'nullable|string|max:255',
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
        
        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image_url) {
                ImageHelper::deleteImage($category->image_url, 'categories');
            }
            $validated['image_url'] = ImageHelper::convertToWebP($request->file('image'), 'categories');
        } else {
            // If no new image file uploaded, handle image_url string
            if (isset($validated['image_url']) && !empty($validated['image_url'])) {
                $imageUrl = trim($validated['image_url']);
                
                // If it's a storage URL (contains /storage/categories/), extract just the filename
                if (strpos($imageUrl, '/storage/categories/') !== false || strpos($imageUrl, 'storage/categories/') !== false) {
                    // Extract filename from storage path
                    $validated['image_url'] = basename($imageUrl);
                } elseif (preg_match('/^https?:\/\//', $imageUrl)) {
                    // External URL, keep as-is
                    $validated['image_url'] = $imageUrl;
                } else {
                    // Assume it's already just a filename or relative path, keep as-is
                    $validated['image_url'] = $imageUrl;
                }
            } else {
                // If image_url is empty/null, keep the existing value from database
                $validated['image_url'] = $category->image_url;
            }
            unset($validated['image']);
        }
        
        $category->update($validated);

        return redirect()->back()
            ->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = ProductCategory::withCount('products')->findOrFail($id);
        $categoryName = $category->name;
        $productsCount = $category->products_count;
        
        DB::beginTransaction();
        try {
            // Delete all products associated with this category
            Product::where('product_category_id', $category->id)->delete();
            
            // Delete the category
            $category->delete();
            
            DB::commit();
            
            $message = "Category '{$categoryName}' and {$productsCount} associated product(s) deleted successfully.";
            return redirect()->route('admin.categories.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting category: ' . $e->getMessage());
        }
    }

    public function search(Request $request, $id)
    {
        $currentCategory = ProductCategory::findOrFail($id);
        $query = $request->get('query', '');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json(['results' => []]);
        }
        
        $results = ProductCategory::where('id', '!=', $currentCategory->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('slug', 'like', "%{$query}%");
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
        
        return response()->json(['results' => $results]);
    }

    public function merge(Request $request, $id)
    {
        $request->validate([
            'source_category_id' => 'required|exists:product_categories,id',
        ]);
        
        $targetCategory = ProductCategory::findOrFail($id); // Current category (where products will be moved to)
        $sourceCategory = ProductCategory::findOrFail($request->source_category_id); // Category to be merged (will be deleted)
        
        if ($sourceCategory->id === $targetCategory->id) {
            return redirect()->back()->with('error', 'Cannot merge category with itself.');
        }
        
        DB::beginTransaction();
        try {
            // Update all products from source category to use the target category
            Product::where('product_category_id', $sourceCategory->id)
                ->update(['product_category_id' => $targetCategory->id]);
            
            // Delete the source category
            $sourceCategory->delete();
            
            DB::commit();
            
            return redirect()->route('admin.categories.edit', $targetCategory->id)
                ->with('success', "Category '{$sourceCategory->name}' merged into '{$targetCategory->name}' successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error merging categories: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:product_categories,id',
        ]);
        
        $categoryIds = $request->category_ids;
        
        DB::beginTransaction();
        try {
            $deletedCount = 0;
            $productsDeletedCount = 0;
            $deletedNames = [];
            
            foreach ($categoryIds as $categoryId) {
                $category = ProductCategory::withCount('products')->findOrFail($categoryId);
                
                // Count products before deletion
                $productsCount = $category->products_count;
                
                // Delete all products associated with this category
                Product::where('product_category_id', $category->id)->delete();
                
                $deletedNames[] = $category->name;
                $productsDeletedCount += $productsCount;
                $category->delete();
                $deletedCount++;
            }
            
            DB::commit();
            
            $message = "Successfully deleted {$deletedCount} category/categories and {$productsDeletedCount} associated product(s).";
            if ($deletedCount <= 5) {
                $message .= " Deleted: " . implode(', ', $deletedNames);
            }
            
            return redirect()->route('admin.categories.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting categories: ' . $e->getMessage());
        }
    }

    public function bulkMerge(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array|min:2',
            'category_ids.*' => 'exists:product_categories,id',
        ]);
        
        $categoryIds = $request->category_ids;
        
        // First category is the main category (target)
        $mainCategoryId = $categoryIds[0];
        $mainCategory = ProductCategory::findOrFail($mainCategoryId);
        
        // Other categories will be merged into the main category
        $categoriesToMerge = array_slice($categoryIds, 1);
        
        // Validate that main category is not in the merge list
        if (in_array($mainCategoryId, $categoriesToMerge)) {
            return redirect()->back()->with('error', 'Main category cannot be in the merge list.');
        }
        
        DB::beginTransaction();
        try {
            $mergedCount = 0;
            $mergedNames = [];
            
            foreach ($categoriesToMerge as $categoryId) {
                $categoryToMerge = ProductCategory::findOrFail($categoryId);
                
                // Skip if trying to merge main category with itself
                if ($categoryToMerge->id === $mainCategory->id) {
                    continue;
                }
                
                // Update all products from category to merge to use the main category
                Product::where('product_category_id', $categoryToMerge->id)
                    ->update(['product_category_id' => $mainCategory->id]);
                
                $mergedNames[] = $categoryToMerge->name;
                $categoryToMerge->delete();
                $mergedCount++;
            }
            
            DB::commit();
            
            $message = "Successfully merged {$mergedCount} category/categories into '{$mainCategory->name}': " . implode(', ', $mergedNames);
            return redirect()->route('admin.categories.index')
                ->with('success', $message);
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

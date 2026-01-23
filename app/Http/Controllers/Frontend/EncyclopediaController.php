<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EncyclopediaController extends Controller
{
    public function index(Request $request)
    {
        // Get all active product categories with additional data
        $query = ProductCategory::where('is_active', true)
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->with('educationPost');

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('educationPost', function ($epQuery) use ($search) {
                      $epQuery->where('title', 'like', "%{$search}%")
                              ->orWhere('description', 'like', "%{$search}%");
                  });
            });
        }

        // Apply category filter
        $selectedCategory = $request->get('category', 'all');
        if ($selectedCategory !== 'all') {
            // Filter by category tag (we'll determine this in the frontend)
            // For now, we'll get all and filter in frontend
        }

        $categories = $query->orderBy('name')->get()->map(function ($category) {
            $educationPost = $category->educationPost;
            
            // Get category image
            $image = null;
            if ($category->image_url) {
                $image = Storage::url('categories/' . $category->image_url);
            }

            // Determine category tag
            $categoryTag = $this->getCategoryTag($category->name, $category->description);

            // Get sample product for additional data
            $sampleProduct = Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $category->id)
                ->first();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'subtitle' => $this->getSubtitle($category->name),
                'description' => $educationPost ? $educationPost->description : $category->description,
                'image' => $image,
                'categoryTag' => $categoryTag,
                'safetyTag' => 'High Safety', // Default, can be enhanced later
                'popularity' => min(95, 70 + ($category->products_count * 2)), // Mock popularity
                'rating' => '4.7', // Mock rating, can be calculated from reviews
                'benefits' => $this->getBenefits($category->name, $category->description),
                'researchStudies' => rand(30, 60), // Mock research studies count
                'products_count' => $category->products_count,
            ];
        });

        // Filter by category tag if needed
        if ($selectedCategory !== 'all') {
            $categories = $categories->filter(function ($category) use ($selectedCategory) {
                return $category['categoryTag'] === $selectedCategory;
            })->values();
        }

        return Inertia::render('Frontend/Encyclopedia', [
            'peptides' => $categories,
            'search' => $request->get('search', ''),
            'category' => $selectedCategory,
        ]);
    }

    /**
     * Get category tag based on name/description
     */
    private function getCategoryTag($name, $description)
    {
        $nameLower = strtolower($name ?? '');
        $descLower = strtolower($description ?? '');
        $combined = $nameLower . ' ' . $descLower;

        if (strpos($combined, 'bpc') !== false || strpos($combined, 'tb-500') !== false || 
            strpos($combined, 'healing') !== false || strpos($combined, 'recovery') !== false) {
            return 'Healing & Recovery';
        }

        if (strpos($combined, 'cjc') !== false || strpos($combined, 'ipamorelin') !== false || 
            strpos($combined, 'ghrp') !== false || strpos($combined, 'growth') !== false) {
            return 'Growth & Recovery';
        }

        if (strpos($combined, 'performance') !== false || strpos($combined, 'pt-141') !== false) {
            return 'Performance';
        }

        if (strpos($combined, 'anti-aging') !== false || strpos($combined, 'aging') !== false) {
            return 'Anti-Aging';
        }

        return 'Healing & Recovery'; // Default
    }

    /**
     * Get subtitle for peptide
     */
    private function getSubtitle($name)
    {
        $subtitles = [
            'BPC-157' => 'Body Protection Compound-157',
            'TB-500' => 'Thymosin Beta-4',
            'CJC-1295' => 'CJC-1295 (No DAC)',
            'Ipamorelin' => 'Ipamorelin Acetate',
            'PT-141' => 'Bremelanotide',
        ];

        foreach ($subtitles as $key => $subtitle) {
            if (stripos($name, $key) !== false) {
                return $subtitle;
            }
        }

        return $name; // Fallback to name
    }

    /**
     * Get benefits for peptide
     */
    private function getBenefits($name, $description)
    {
        $nameLower = strtolower($name ?? '');
        $descLower = strtolower($description ?? '');

        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'Accelerates tendon and ligament healing',
                'Reduces inflammation',
                'Promotes gut health and repairs intestinal damage'
            ];
        }

        if (stripos($nameLower, 'tb-500') !== false) {
            return [
                'Accelerates healing of muscle injuries',
                'Promotes flexibility and reduces inflammation',
                'Improves hair growth (topical use)'
            ];
        }

        if (stripos($nameLower, 'ipamorelin') !== false) {
            return [
                'Increases lean muscle mass',
                'Promotes fat loss',
                'Improves sleep quality'
            ];
        }

        if (stripos($nameLower, 'cjc-1295') !== false) {
            return [
                'Increases growth hormone levels',
                'Boosts IGF-1 production',
                'Enhances muscle growth'
            ];
        }

        if (stripos($nameLower, 'pt-141') !== false) {
            return [
                'Increases sexual desire and arousal',
                'Improves erectile function',
                'Enhances sexual satisfaction'
            ];
        }

        // Default benefits
        return [
            'Supports research applications',
            'Well-documented in scientific literature',
            'Multiple potential benefits'
        ];
    }
}

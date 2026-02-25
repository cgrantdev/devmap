<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class EducationController extends Controller
{
    /**
     * Safely truncate a string to a given length
     * Works without mbstring extension
     */
    private function safeLimit($value, $limit = 100, $end = '...')
    {
        if (empty($value)) {
            return '';
        }

        $value = strip_tags($value);
        
        // If mbstring is available, use it
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value) <= $limit) {
                return $value;
            }
            return mb_substr($value, 0, $limit) . $end;
        }
        
        // Fallback to regular string functions
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, $limit) . $end;
    }

    public function index(Request $request)
    {
        // Get all active product categories (display all, not just ones with education posts)
        $categories = ProductCategory::where('is_active', true)
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->with('educationPost')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $educationPost = $category->educationPost;
                
                // Use only category image, no fallbacks
                $image = null;
                if ($category->image_url) {
                    $image = \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url);
                }
                
                return [
                    'id' => $category->id,
                    'name' => strtoupper($category->name),
                    'slug' => $category->slug,
                    'total_items' => $category->products_count,
                    'image' => $image,
                    'description' => $educationPost ? $educationPost->description : $category->description,
                ];
            });

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Peptide Education & Research Guides | PeptideSync',
            description: 'Comprehensive educational guides on research peptides. Learn about benefits, dosing, safety, and research applications for various peptides.',
            url: url('/education'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Education', [
            'productGroups' => $categories,
        ]);
    }

    public function show($slug)
    {
        // Find category by slug and get its education post
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with('educationPost')
            ->firstOrFail();

        $post = $category->educationPost;
        
        // If no education post exists or not published, show a basic page or create one
        // For now, we'll show the category info even if no education post exists
        if (!$post || $post->status !== 'published' || !$post->published_at || $post->published_at->isFuture()) {
            // Generate SEO data for basic category page
            $categoryImage = $category->image_url ? \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url) : null;
            $seoData = new SEOData(
                title: $category->name . ' - Education Guide | PeptideSync',
                description: $category->description ? $this->safeLimit($category->description, 160) : 'Learn about ' . $category->name . ' research peptides.',
                image: $categoryImage,
                url: url("/education/{$slug}"),
            );
            session(['page_seo_data' => $seoData]);
            
            // Show a basic education page with category info
            return Inertia::render('Frontend/EducationDetail', [
                'post' => [
                    'id' => null,
                    'title' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'overview' => $category->description, // Keep for backward compatibility with frontend
                    'image' => $category->image_url ? \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url) : null,
                    'rating' => '0.00',
                    'rating_count' => 0,
                    'key_effects' => [],
                    'accordion_sections' => [], // Keep for backward compatibility with frontend
                    'shop_url' => '/product/' . $category->slug, // Keep for backward compatibility with frontend
                ],
                'category' => [
                    'id' => $category->id,
                    'name' => strtoupper($category->name),
                    'slug' => $category->slug,
                ],
                'related' => [],
            ]);
        }

        // Get related posts from other categories
        $related = EducationPost::where('id', '!=', $post->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->category ? $p->category->slug : $p->slug,
                    'description' => $p->description,
                    'image' => $p->image ? \Illuminate\Support\Facades\Storage::url('education_posts/' . $p->image) : null,
                ];
            });

        // Generate SEO data for education post
        $postImage = $post->image ? \Illuminate\Support\Facades\Storage::url('education_posts/' . $post->image) : null;
        $seoData = new SEOData(
            title: $post->title . ' - Education Guide | PeptideSync',
            description: $post->description ? $this->safeLimit($post->description, 160) : 'Learn about ' . $post->title . ' research peptides.',
            image: $postImage,
            url: url("/education/{$slug}"),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/EducationDetail', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $category->slug,
                'description' => $post->description,
                'overview' => $post->description, // Use description as overview for backward compatibility
                'image' => $post->image ? \Illuminate\Support\Facades\Storage::url('education_posts/' . $post->image) : null,
                'rating' => number_format($post->rating, 2, '.', ''),
                'rating_count' => $post->rating_count,
                'key_effects' => $post->key_effects ?? [],
                'accordion_sections' => [], // Empty array for backward compatibility
                'shop_url' => '/product/' . $category->slug, // Link to product category page
                // New fields
                'education_tag' => $post->education_tag,
                'peptide_full_name' => $post->peptide_full_name,
                'half_life' => $post->half_life,
                'bioavailability' => $post->bioavailability,
                'storage' => $post->storage,
                'common_use_cases' => $post->common_use_cases ?? [],
                'how_it_works' => $post->how_it_works,
                'typical_dosage' => $post->typical_dosage,
                'frequency' => $post->frequency,
                'administration' => $post->administration,
                'cycle_duration' => $post->cycle_duration,
                'possible_side_effects' => $post->possible_side_effects ?? [],
                'contraindications' => $post->contraindications ?? [],
                'stacking_recommendations' => $post->stacking_recommendations ?? [],
                'faqs' => $post->faqs ?? [],
            ],
            'category' => [
                'id' => $category->id,
                'name' => strtoupper($category->name),
                'slug' => $category->slug,
            ],
            'related' => $related,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\EducationPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class EncyclopediaController extends Controller
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
        // Get all active product categories with additional data
        // Only include categories that have an education_post entry with research articles
        $query = ProductCategory::where('is_active', true)
            ->whereHas('educationPost', function ($epQuery) {
                $epQuery->where(function ($subQ) {
                    $subQ->whereNotNull('research_url')
                         ->where('research_url', '!=', '');
                })->orWhere(function ($subQ) {
                    $subQ->whereNotNull('research_title')
                         ->where('research_title', '!=', '');
                });
            })
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
            
            // Get category image - use category image if available, otherwise get a sample product image
            $image = null;
            if ($category->image_url) {
                $image = Storage::url('categories/' . $category->image_url);
            } else {
                // Get a sample product image for this category
                $sampleProduct = Product::visible()
                    ->where('status', 'active')
                    ->where('product_category_id', $category->id)
                    ->whereNotNull('image_url')
                    ->first();
                $image = $sampleProduct ? $sampleProduct->image_url : '/images/peptides/default.png';
            }

            // Determine category tag - use education_tag from database, fallback to computed
            $categoryTag = $educationPost && $educationPost->education_tag 
                ? $educationPost->education_tag 
                : $this->getCategoryTag($category->name, $category->description);

            // Get title - always use category name
            $title = $category->name;

            // Get peptide full name - use from database, fallback to computed
            $peptideFullName = $educationPost && $educationPost->peptide_full_name 
                ? $educationPost->peptide_full_name 
                : $this->getSubtitle($category->name);

            // Get sample product for additional data
            $sampleProduct = Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $category->id)
                ->first();

            // Get key effects from education post if available, otherwise use fallback method
            $keyEffects = [];
            if ($educationPost && !empty($educationPost->key_effects) && is_array($educationPost->key_effects)) {
                $keyEffects = $educationPost->key_effects;
            } else {
                $keyEffects = $this->getKeyBenefits($category->name);
            }

            return [
                'id' => $category->id,
                'name' => $title,
                'slug' => $category->slug,
                'subtitle' => $peptideFullName,
                'description' => $educationPost ? $educationPost->description : $category->description,
                'image' => $image,
                'categoryTag' => $categoryTag,
                'safetyTag' => 'High Safety', // Default, can be enhanced later
                'popularity' => min(95, 70 + ($category->products_count * 2)), // Mock popularity
                'rating' => '4.7', // Mock rating, can be calculated from reviews
                'benefits' => $keyEffects,
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

        // Get all published encyclopedia entries (EducationPost)
        $encyclopediaEntries = EducationPost::where('status', 'published')
            ->with('category')
            ->orderBy('title')
            ->get()
            ->filter(function ($entry) {
                // Only include entries that have a category (for proper routing)
                return $entry->category !== null;
            })
            ->map(function ($entry) {
                $category = $entry->category;
                
                // Get tags - use entry tags if available, otherwise use education_tag
                $tags = [];
                if ($entry->tags && is_array($entry->tags)) {
                    $tags = $entry->tags;
                } elseif ($entry->tags) {
                    $tags = json_decode($entry->tags, true) ?? [];
                }
                $categoryTag = !empty($tags) ? $tags[0] : ($entry->education_tag ?? 'Research Review');

                // Use category slug for the route
                $slug = $category ? $category->slug : ($entry->slug ?? '');

                return [
                    'id' => $entry->id,
                    'title' => $entry->title ?? 'Untitled',
                    'research_title' => $entry->research_title ?? ($entry->title ?? 'Untitled'),
                    'research_outline' => $entry->research_outline ?? '',
                    'slug' => $slug,
                    'subtitle' => $entry->peptide_full_name ?? '',
                    'description' => $entry->research_outline ?? $entry->overview ?? $entry->description ?? '',
                    'categoryTag' => $categoryTag,
                    'updated_at' => $entry->updated_at ? $entry->updated_at->format('M Y') : null,
                ];
            });

        // Generate SEO data
        $seoData = new SEOData(
            title: 'Peptide Encyclopedia - Comprehensive Research Guide | PeptideSync',
            description: 'Explore our comprehensive peptide encyclopedia. Detailed information on research peptides including benefits, dosing, safety, and research applications.',
            url: url('/encyclopedia'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Encyclopedia', [
            'peptides' => $categories,
            'encyclopediaEntries' => $encyclopediaEntries->values(),
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

    /**
     * Show encyclopedia detail page
     */
    public function show($slug)
    {
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with('educationPost')
            ->firstOrFail();

        $educationPost = $category->educationPost;
        
        // Get image - prioritize education post image, fallback to category image
        $image = null;
        if ($educationPost && $educationPost->image) {
            $image = Storage::url('education_posts/' . $educationPost->image);
        } elseif ($category->image_url) {
            $image = Storage::url('categories/' . $category->image_url);
        }

        // Get products for this category
        $products = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->with(['brand', 'category'])
            ->orderBy('price', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($product) use ($category) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image_url' => $product->image_url,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                    'size_mg' => $product->size_mg,
                    'availability' => $product->availability,
                    'rating_average' => (float) ($product->rating_average ?? 0),
                    'rating_count' => (int) ($product->rating_count ?? 0),
                    'brand' => $product->brand ? [
                        'name' => $product->brand->name,
                    ] : null,
                    'category' => $product->category ? [
                        'name' => $product->category->name,
                    ] : [
                        'name' => $category->name,
                    ],
                ];
            });

        // Determine category tag - use education_tag from database, fallback to computed
        $categoryTag = $educationPost && $educationPost->education_tag 
            ? $educationPost->education_tag 
            : $this->getCategoryTag($category->name, $category->description);

        // Get key effects from education post if available, otherwise use fallback method
        $keyBenefits = [];
        if ($educationPost && !empty($educationPost->key_effects) && is_array($educationPost->key_effects)) {
            $keyBenefits = $educationPost->key_effects;
        } else {
            $keyBenefits = $this->getKeyBenefits($category->name);
        }

        // Get peptide full name - use from database, fallback to computed
        $peptideFullName = $educationPost && $educationPost->peptide_full_name 
            ? $educationPost->peptide_full_name 
            : $this->getSubtitle($category->name);

        // Get quick facts from database
        $quickFacts = [
            'halfLife' => $educationPost && $educationPost->half_life ? $educationPost->half_life : 'Varies',
            'bioavailability' => $educationPost && $educationPost->bioavailability ? $educationPost->bioavailability : 'Varies by administration method',
            'storage' => $educationPost && $educationPost->storage ? $educationPost->storage : 'Refrigerate at 2-8°C',
            'researchLevel' => 'Moderate Research', // Can be enhanced later
        ];

        // Get common use cases from database
        $commonUseCases = [];
        if ($educationPost && !empty($educationPost->common_use_cases) && is_array($educationPost->common_use_cases)) {
            $commonUseCases = $educationPost->common_use_cases;
        } else {
            $commonUseCases = $this->getCommonUseCases($category->name);
        }

        // Get how it works from database (text field)
        $howItWorks = $educationPost && $educationPost->how_it_works 
            ? $educationPost->how_it_works 
            : 'Mechanism of action varies by peptide type. Research continues to uncover specific pathways.';

        // Get dosage information from database
        $dosage = [
            'typicalDosage' => $educationPost && $educationPost->typical_dosage ? $educationPost->typical_dosage : 'Varies',
            'frequency' => $educationPost && $educationPost->frequency ? $educationPost->frequency : 'Varies',
            'administration' => $educationPost && $educationPost->administration ? $educationPost->administration : 'Subcutaneous injection',
            'cycleDuration' => $educationPost && $educationPost->cycle_duration ? $educationPost->cycle_duration : '4-6 weeks',
        ];

        // Get safety information from database
        $safetyInfo = [
            'sideEffects' => $educationPost && !empty($educationPost->possible_side_effects) && is_array($educationPost->possible_side_effects)
                ? $educationPost->possible_side_effects
                : ['Generally well-tolerated'],
            'contraindications' => $educationPost && !empty($educationPost->contraindications) && is_array($educationPost->contraindications)
                ? $educationPost->contraindications
                : ['Consult doctor before use'],
        ];

        // Get stacking recommendations from database (array of category IDs)
        $stackingRecommendations = [];
        $hasStackingData = false;
        
        if ($educationPost && !empty($educationPost->stacking_recommendations) && is_array($educationPost->stacking_recommendations)) {
            $hasStackingData = true; // We have data from database (even if it's just [null])
            
            // Filter out null values
            $stackingCategoryIds = array_filter($educationPost->stacking_recommendations, function($id) {
                return $id !== null;
            });
            
            if (!empty($stackingCategoryIds)) {
                $stackingCategories = ProductCategory::whereIn('id', $stackingCategoryIds)
                    ->where('is_active', true)
                    ->get();
                
                $stackingRecommendations = $stackingCategories->map(function ($cat) {
                    return [
                        'name' => $cat->name,
                        'subtitle' => $cat->description ? substr($cat->description, 0, 100) : '',
                        'slug' => $cat->slug,
                    ];
                })->toArray();
            }
            // If $stackingCategoryIds is empty, it means "None" was selected, so $stackingRecommendations stays empty
        }

        // Only use fallback if we don't have any stacking data from database
        if (!$hasStackingData && empty($stackingRecommendations)) {
            $stackingRecommendations = $this->getStackingRecommendations($category->name);
        }

        // Get FAQs from database
        $faqs = [];
        if ($educationPost && !empty($educationPost->faqs) && is_array($educationPost->faqs)) {
            $faqs = $educationPost->faqs;
        } else {
            $faqs = $this->getFAQs($category->name);
        }

        // Get title - use education post title if available, fallback to category name
        $title = $educationPost && $educationPost->title 
            ? $educationPost->title 
            : $category->name;

        // Generate SEO data for encyclopedia detail
        $seoData = new SEOData(
            title: $title . ' - Peptide Encyclopedia | PeptideSync',
            description: ($educationPost ? $educationPost->description : $category->description) ? $this->safeLimit($educationPost ? $educationPost->description : $category->description, 160) : 'Comprehensive guide to ' . $title . ' research peptides.',
            image: $image,
            url: url("/encyclopedia/{$slug}"),
        );
        session(['page_seo_data' => $seoData]);

        // Get comprehensive data
        $peptideData = [
            'id' => $category->id,
            'name' => $title,
            'slug' => $category->slug,
            'subtitle' => $peptideFullName,
            'description' => $educationPost ? $educationPost->description : $category->description,
            'image' => $image,
            'categoryTag' => $categoryTag,
            'keyBenefits' => $keyBenefits,
            'quickFacts' => $quickFacts,
            'commonUseCases' => $commonUseCases,
            'howItWorks' => $howItWorks,
            'dosage' => $dosage,
            'safetyInfo' => $safetyInfo,
            'stackingRecommendations' => $stackingRecommendations,
            'faqs' => $faqs,
            'userExperiences' => $this->getUserExperiences($category->name), // Keep mock data for now
            'products' => $products,
            'researchStudies' => rand(30, 60), // Mock data
        ];

        return Inertia::render('Frontend/EncyclopediaDetail', $peptideData);
    }

    /**
     * Show encyclopedia article detail page (new format)
     */
    public function showArticle($slug)
    {
        // Reuse the same logic as show() but render the new article detail page
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with('educationPost')
            ->firstOrFail();

        $educationPost = $category->educationPost;
        
        // Get image - prioritize education post image, fallback to category image
        $image = null;
        if ($educationPost && $educationPost->image) {
            $image = Storage::url('education_posts/' . $educationPost->image);
        } elseif ($category->image_url) {
            $image = Storage::url('categories/' . $category->image_url);
        }

        // Get products for this category
        $products = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->with(['brand', 'category'])
            ->orderBy('price', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($product) use ($category) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image_url' => $product->image_url,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                    'size_mg' => $product->size_mg,
                    'availability' => $product->availability,
                    'rating_average' => (float) ($product->rating_average ?? 0),
                    'rating_count' => (int) ($product->rating_count ?? 0),
                    'brand' => $product->brand ? [
                        'name' => $product->brand->name,
                    ] : null,
                    'category' => $product->category ? [
                        'name' => $product->category->name,
                    ] : [
                        'name' => $category->name,
                    ],
                ];
            });

        // Determine category tag - use tags from education post, fallback to education_tag, then computed
        $tags = [];
        if ($educationPost && $educationPost->tags) {
            $tags = is_array($educationPost->tags) ? $educationPost->tags : json_decode($educationPost->tags, true) ?? [];
        }
        $categoryTag = !empty($tags) ? $tags[0] : ($category->education_tag ?? $this->getCategoryTag($category->name, $category->description));
        
        // Get title and full name
        $title = $educationPost && $educationPost->title ? $educationPost->title : $category->name;
        $peptideFullName = $educationPost && $educationPost->peptide_full_name ? $educationPost->peptide_full_name : ($category->description ?? '');

        // Generate SEO data
        $seoData = new SEOData(
            title: $title . ' - Peptide Encyclopedia | PeptideSync',
            description: ($educationPost && $educationPost->overview) ? $this->safeLimit($educationPost->overview, 160) : (($educationPost && $educationPost->description) ? $this->safeLimit($educationPost->description, 160) : 'Comprehensive guide to ' . $title . ' research peptides.'),
            image: $image,
            url: url("/encyclopedia/article/{$slug}"),
        );
        session(['page_seo_data' => $seoData]);

        // Get comprehensive data for article detail page from database
        $peptideData = [
            'id' => $category->id,
            'name' => $title,
            'categoryName' => $category->name,
            'slug' => $category->slug,
            'subtitle' => $peptideFullName,
            'tags' => $tags,
            'primaryResearch' => [
                'institution' => 'University of Zagreb (Croatia)',
                'url' => $educationPost->research_url ?? '#'
            ],
            'molecularInfo' => [
                'formula' => $educationPost->molecular_formula ?? '',
                'molecularWeight' => $educationPost->molecular_weight ?? '',
                'casNumber' => $educationPost->cas_registry_number ?? ''
            ],
            'aminoAcidSequence' => [
                'residueCount' => $educationPost->amino_acid_sequence ? count(explode('-', $educationPost->amino_acid_sequence)) : 0,
                'sequence' => $educationPost->amino_acid_sequence ?? '',
                'composition' => [],
                'properties' => [
                    'netCharge' => $educationPost->amino_acid_net_charge ?? '',
                    'hydrophobic' => $educationPost->amino_acid_hydrophobic ?? '',
                    'stability' => $educationPost->amino_acid_stability ?? '',
                    'solubility' => $educationPost->amino_acid_solubility ?? ''
                ]
            ],
            'keyPoints' => $educationPost && $educationPost->key_points ? (is_array($educationPost->key_points) ? $educationPost->key_points : json_decode($educationPost->key_points, true) ?? []) : [],
            'overview' => $educationPost->overview ?? '',
            'areasOfResearch' => $educationPost && $educationPost->areas_of_research ? (is_array($educationPost->areas_of_research) ? $educationPost->areas_of_research : json_decode($educationPost->areas_of_research, true) ?? []) : [],
            'areasOfResearchIntro' => $educationPost->areas_of_research_intro ?? '',
            'background' => $educationPost->background ?? '',
            'mechanismOfActionIntro' => $educationPost->mechanism_of_action_intro ?? '',
            'mechanismSubsections' => $educationPost && $educationPost->mechanism_subsections ? (is_array($educationPost->mechanism_subsections) ? $educationPost->mechanism_subsections : json_decode($educationPost->mechanism_subsections, true) ?? []) : [],
            'preclinicalIntro' => $educationPost->preclinical_intro ?? '',
            'preclinicalSubsections' => $educationPost && $educationPost->preclinical_subsections ? (is_array($educationPost->preclinical_subsections) ? $educationPost->preclinical_subsections : json_decode($educationPost->preclinical_subsections, true) ?? []) : [],
            'preclinicalDisclaimer' => $educationPost->preclinical_disclaimer ?? '',
            'humanUseIntro' => $educationPost->human_use_intro ?? '',
            'humanUseSubsections' => $educationPost && $educationPost->human_use_subsections ? (is_array($educationPost->human_use_subsections) ? $educationPost->human_use_subsections : json_decode($educationPost->human_use_subsections, true) ?? []) : [],
            'regulatorySubsections' => $educationPost && $educationPost->regulatory_subsections ? (is_array($educationPost->regulatory_subsections) ? $educationPost->regulatory_subsections : json_decode($educationPost->regulatory_subsections, true) ?? []) : [],
            'regulatoryImportantNote' => $educationPost->regulatory_important_note ?? '',
            'potentialApplicationsIntro' => $educationPost->potential_applications_intro ?? '',
            'potentialApplications' => $educationPost && $educationPost->potential_applications ? (is_array($educationPost->potential_applications) ? $educationPost->potential_applications : json_decode($educationPost->potential_applications, true) ?? []) : [],
            'potentialApplicationsImportantContext' => $educationPost->potential_applications_important_context ?? '',
            'conclusion' => $educationPost->conclusion ?? '',
            'references' => $educationPost && $educationPost->references ? (is_array($educationPost->references) ? $educationPost->references : json_decode($educationPost->references, true) ?? []) : [],
            'products' => $products,
        ];

        return Inertia::render('Frontend/EncyclopediaArticleDetail', $peptideData);
    }

    /**
     * Get key benefits for peptide
     */
    private function getKeyBenefits($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'Accelerates tendon and ligament healing',
                'Promotes gut health and repairs intestinal damage',
                'May improve joint health',
                'Neuroprotective properties',
                'Reduces inflammation',
                'Protects and heals muscle tissue',
                'Supports blood vessel formation',
            ];
        }

        // Default benefits
        return [
            'Supports research applications',
            'Well-documented in scientific literature',
            'Multiple potential benefits',
        ];
    }

    /**
     * Get quick facts
     */
    private function getQuickFacts($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'halfLife' => '~4 hours',
                'bioavailability' => 'High via injection, lower oral',
                'storage' => 'Refrigerate at 2-8°C, freeze for long-term storage',
                'researchLevel' => 'Moderate Research',
            ];
        }

        return [
            'halfLife' => 'Varies',
            'bioavailability' => 'Varies by administration method',
            'storage' => 'Refrigerate at 2-8°C',
            'researchLevel' => 'Moderate Research',
        ];
    }

    /**
     * Get common use cases
     */
    private function getCommonUseCases($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'Sports injuries (tendonitis, sprains, strains)',
                'Post-surgical recovery',
                'Chronic joint pain',
                'Inflammatory bowel disease (IBD)',
                'Leaky gut syndrome',
                'Muscle tears and damage',
                'General tissue repair',
            ];
        }

        return [
            'Research applications',
            'General use cases',
        ];
    }

    /**
     * Get how it works information
     */
    private function getHowItWorks($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'Promotes angiogenesis (formation of new blood vessels)',
                'Modulates growth factors including VEGF and FGF',
                'Activates the FAK-paxillin pathway',
                'Enhances cell migration and proliferation',
            ];
        }

        return [
            'Mechanism of action varies by peptide type',
            'Research continues to uncover specific pathways',
        ];
    }

    /**
     * Get dosage information
     */
    private function getDosage($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                'typicalDosage' => '250-500 mcg',
                'frequency' => 'Once or twice daily',
                'administration' => [
                    'Subcutaneous injection',
                    'Intramuscular injection',
                    'Oral - less effective',
                ],
                'cycleDuration' => '4-6 weeks, can be extended',
            ];
        }

        return [
            'typicalDosage' => 'Varies',
            'frequency' => 'Varies',
            'administration' => ['Subcutaneous injection'],
            'cycleDuration' => '4-6 weeks',
        ];
    }

    /**
     * Get safety information
     */
    private function getSafetyInfo($name)
    {
        return [
            'sideEffects' => [
                'Generally well-tolerated',
                'Mild headache (rare)',
                'Dizziness (rare)',
                'Nausea (rare)',
                'Injection site redness',
            ],
            'contraindications' => [
                'Pregnancy and breastfeeding',
                'Active cancer (theoretical concern)',
                'Consult doctor if on blood thinners',
            ],
        ];
    }

    /**
     * Get stacking recommendations
     */
    private function getStackingRecommendations($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                [
                    'name' => 'TB-500',
                    'subtitle' => 'Thymosin Beta-4',
                    'description' => 'Stacking BPC-157 with TB-500 can enhance healing effects',
                    'slug' => 'tb-500',
                ],
            ];
        }

        return [];
    }

    /**
     * Get FAQs
     */
    private function getFAQs($name)
    {
        $nameLower = strtolower($name ?? '');
        
        if (stripos($nameLower, 'bpc-157') !== false) {
            return [
                [
                    'question' => 'How long does it take to see results from BPC-157?',
                    'answer' => 'Most users report noticeable improvements within 2-4 weeks of consistent use. Full benefits may take 6-8 weeks or longer depending on the severity of the condition.',
                ],
                [
                    'question' => 'Should I inject BPC-157 near the injury site?',
                    'answer' => 'While systemic administration is effective, some users prefer to inject near the injury site for potentially faster localized effects. Both methods are valid.',
                ],
                [
                    'question' => 'Can I take BPC-157 orally?',
                    'answer' => 'Yes, BPC-157 can be taken orally, though bioavailability is lower than injection. Oral administration is more convenient but may require higher doses.',
                ],
            ];
        }

        return [
            [
                'question' => 'What is the recommended dosage?',
                'answer' => 'Dosage varies by individual and condition. Consult with a healthcare professional for personalized recommendations.',
            ],
        ];
    }

    /**
     * Get user experiences (mock data)
     */
    private function getUserExperiences($name)
    {
        return [
            [
                'rating' => 5,
                'review' => 'Excellent product! Helped with my tendonitis significantly. Shipping was fast and product quality is top-notch.',
                'verified' => true,
                'author' => 'John D.',
            ],
            [
                'rating' => 5,
                'review' => 'Great results after 3 weeks of use. Highly recommend for anyone dealing with joint issues.',
                'verified' => true,
                'author' => 'Sarah M.',
            ],
        ];
    }
}

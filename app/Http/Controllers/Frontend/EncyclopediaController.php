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

            // Get key effects from education post if available, otherwise use fallback method
            $keyEffects = [];
            if ($educationPost && !empty($educationPost->key_effects) && is_array($educationPost->key_effects)) {
                $keyEffects = $educationPost->key_effects;
            } else {
                $keyEffects = $this->getKeyBenefits($category->name);
            }

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

        // Determine category tag
        $categoryTag = $this->getCategoryTag($category->name, $category->description);

        // Get key effects from education post if available, otherwise use fallback method
        $keyBenefits = [];
        if ($educationPost && !empty($educationPost->key_effects) && is_array($educationPost->key_effects)) {
            $keyBenefits = $educationPost->key_effects;
        } else {
            $keyBenefits = $this->getKeyBenefits($category->name);
        }

        // Get comprehensive data
        $peptideData = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'subtitle' => $this->getSubtitle($category->name),
            'description' => $educationPost ? $educationPost->description : $category->description,
            'image' => $image,
            'categoryTag' => $categoryTag,
            'keyBenefits' => $keyBenefits,
            'quickFacts' => $this->getQuickFacts($category->name),
            'commonUseCases' => $this->getCommonUseCases($category->name),
            'howItWorks' => $this->getHowItWorks($category->name),
            'dosage' => $this->getDosage($category->name),
            'safetyInfo' => $this->getSafetyInfo($category->name),
            'stackingRecommendations' => $this->getStackingRecommendations($category->name),
            'faqs' => $this->getFAQs($category->name),
            'userExperiences' => $this->getUserExperiences($category->name),
            'products' => $products,
            'researchStudies' => rand(30, 60),
        ];

        return Inertia::render('Frontend/EncyclopediaDetail', $peptideData);
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

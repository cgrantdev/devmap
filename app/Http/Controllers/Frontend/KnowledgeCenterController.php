<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KnowledgeCenterController extends Controller
{
    public function index(Request $request)
    {
        // Get primary navigation selection
        $primaryNav = $request->get('nav', 'news');
        
        // Get secondary filter
        $filter = $request->get('filter', 'all');
        
        // Base query for published blogs
        $query = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Get featured blogs (3 for Featured Stories)
        $featuredBlogs = (clone $query)
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($blog) {
                return $this->formatBlog($blog);
            });

        // Get latest blogs (excluding featured, for Latest Articles)
        $latestQuery = (clone $query);
        if ($featuredBlogs->isNotEmpty()) {
            $latestQuery->whereNotIn('id', $featuredBlogs->pluck('id'));
        }

        // Apply filter
        if ($filter !== 'all') {
            $latestQuery->where(function ($q) use ($filter) {
                $this->applyCategoryFilter($q, $filter);
            });
        }

        $latestBlogs = $latestQuery
            ->orderBy('published_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($blog) {
                return $this->formatBlog($blog);
            });

        // Get research papers if Research Library is selected
        $researchPapers = [];
        if ($primaryNav === 'research') {
            $researchPapers = $this->getResearchPapers($request);
        }

        // Get educational guides if Educational Guides is selected
        $educationalGuides = [];
        if ($primaryNav === 'guides') {
            $educationalGuides = $this->getEducationalGuides($request);
        }

        return Inertia::render('Frontend/KnowledgeCenter', [
            'featuredBlogs' => $featuredBlogs,
            'latestBlogs' => $latestBlogs,
            'researchPapers' => $researchPapers,
            'educationalGuides' => $educationalGuides,
            'search' => $request->get('search', ''),
            'primaryNav' => $primaryNav,
            'filter' => $filter,
        ]);
    }

    /**
     * Format blog for frontend
     */
    private function formatBlog($blog)
    {
        $imageUrl = null;
        if ($blog->image) {
            $imageUrl = Storage::url('blogs/' . $blog->image);
        }

        // Determine category tag from content
        $categoryTag = $this->getCategoryTag($blog->title, $blog->description, $blog->content);
        
        // Extract tags from content or generate default
        $tags = $this->extractTags($blog->title, $blog->description);
        
        // Generate author (can be enhanced with actual author field later)
        $author = $this->getAuthor($blog->title, $categoryTag);

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'image' => $imageUrl,
            'date' => $blog->published_at ? $blog->published_at->format('m/d/Y') : null,
            'readTime' => $blog->read_time ?? '5 min',
            'categoryTag' => $categoryTag,
            'tags' => $tags,
            'author' => $author,
        ];
    }

    /**
     * Get category tag based on content
     */
    private function getCategoryTag($title, $description, $content = '')
    {
        $combined = strtolower($title . ' ' . $description . ' ' . $content);

        if (stripos($combined, 'fda') !== false || stripos($combined, 'regulation') !== false || 
            stripos($combined, 'regulatory') !== false || stripos($combined, 'compounding') !== false) {
            return 'Regulation';
        }

        if (stripos($combined, 'study') !== false || stripos($combined, 'research') !== false || 
            stripos($combined, 'clinical') !== false || stripos($combined, 'trial') !== false) {
            return 'Research';
        }

        if (stripos($combined, 'market') !== false || stripos($combined, 'industry') !== false || 
            stripos($combined, 'price') !== false || stripos($combined, 'pricing') !== false) {
            return 'Industry';
        }

        if (stripos($combined, 'guide') !== false || stripos($combined, 'how to') !== false || 
            stripos($combined, 'tutorial') !== false || stripos($combined, 'education') !== false ||
            stripos($combined, 'coa') !== false || stripos($combined, 'purity') !== false) {
            return 'Guides';
        }

        if (stripos($combined, 'success') !== false || stripos($combined, 'story') !== false || 
            stripos($combined, 'experience') !== false || stripos($combined, 'user') !== false ||
            stripos($combined, 'community') !== false) {
            return 'Community';
        }

        return 'Research'; // Default
    }

    /**
     * Extract tags from content
     */
    private function extractTags($title, $description)
    {
        $combined = strtolower($title . ' ' . $description);
        $tags = [];

        // Common peptide names
        $peptides = ['bpc-157', 'tb-500', 'cjc-1295', 'ipamorelin', 'pt-141', 'semaglutide', 'tirzepatide'];
        foreach ($peptides as $peptide) {
            if (stripos($combined, $peptide) !== false) {
                $tags[] = strtoupper($peptide);
            }
        }

        // Common topics
        if (stripos($combined, 'fda') !== false) {
            $tags[] = 'FDA';
        }
        if (stripos($combined, 'regulation') !== false) {
            $tags[] = 'Regulation';
        }
        if (stripos($combined, 'clinical trial') !== false || stripos($combined, 'trial') !== false) {
            $tags[] = 'Clinical Trial';
        }
        if (stripos($combined, 'healing') !== false) {
            $tags[] = 'Healing';
        }
        if (stripos($combined, 'lab testing') !== false || stripos($combined, 'coa') !== false) {
            $tags[] = 'Lab Testing';
            $tags[] = 'COA';
        }
        if (stripos($combined, 'education') !== false) {
            $tags[] = 'Education';
        }
        if (stripos($combined, 'market') !== false || stripos($combined, 'pricing') !== false) {
            $tags[] = 'Market';
            $tags[] = 'Pricing';
        }
        if (stripos($combined, 'industry') !== false) {
            $tags[] = 'Industry';
        }
        if (stripos($combined, 'success story') !== false) {
            $tags[] = 'Success Story';
        }

        return array_slice(array_unique($tags), 0, 3); // Limit to 3 tags
    }

    /**
     * Get author based on category
     */
    private function getAuthor($title, $categoryTag)
    {
        $authors = [
            'Regulation' => 'Dr. Sarah Chen • Regulatory Expert',
            'Research' => 'Dr. Michael Thompson • Research Scientist',
            'Industry' => 'James Rodriguez • Market Analyst',
            'Guides' => 'PeptideMap Editorial',
            'Community' => 'John M. • Community Member',
        ];

        return $authors[$categoryTag] ?? 'PeptideMap Editorial';
    }

    /**
     * Apply category filter to query
     */
    private function applyCategoryFilter($query, $filter)
    {
        $filterLower = strtolower($filter);
        
        if ($filterLower === 'research') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%research%')
                  ->orWhere('title', 'like', '%study%')
                  ->orWhere('title', 'like', '%clinical%')
                  ->orWhere('description', 'like', '%research%')
                  ->orWhere('description', 'like', '%study%');
            });
        } elseif ($filterLower === 'industry') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%market%')
                  ->orWhere('title', 'like', '%industry%')
                  ->orWhere('title', 'like', '%price%')
                  ->orWhere('description', 'like', '%market%')
                  ->orWhere('description', 'like', '%industry%');
            });
        } elseif ($filterLower === 'regulation') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%fda%')
                  ->orWhere('title', 'like', '%regulation%')
                  ->orWhere('title', 'like', '%regulatory%')
                  ->orWhere('description', 'like', '%fda%')
                  ->orWhere('description', 'like', '%regulation%');
            });
        } elseif ($filterLower === 'guides') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%guide%')
                  ->orWhere('title', 'like', '%how to%')
                  ->orWhere('title', 'like', '%tutorial%')
                  ->orWhere('description', 'like', '%guide%');
            });
        } elseif ($filterLower === 'community') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%success%')
                  ->orWhere('title', 'like', '%story%')
                  ->orWhere('title', 'like', '%experience%')
                  ->orWhere('description', 'like', '%success%')
                  ->orWhere('description', 'like', '%story%');
            });
        }
    }

    /**
     * Get research papers for Research Library
     */
    private function getResearchPapers($request)
    {
        // For now, return mock data. This can be replaced with actual database queries later
        $papers = [
            [
                'id' => 1,
                'peptide' => 'BPC-157',
                'evidenceLevel' => 'High Evidence',
                'title' => 'BPC-157 accelerates tendon-to-bone healing in a rat model',
                'description' => 'Study demonstrates significant improvement in tendon healing rates with BPC-157 treatment compared to control groups.',
                'source' => 'Journal of Orthopedic Research',
                'date' => '9/14/2024',
                'tags' => ['Healing', 'Tendons', 'Animal Study'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
            [
                'id' => 2,
                'peptide' => 'Ipamorelin',
                'evidenceLevel' => 'High Evidence',
                'title' => 'Growth hormone secretagogues improve body composition in elderly',
                'description' => 'Clinical trial shows improvements in lean muscle mass and reduction in body fat with ipamorelin supplementation.',
                'source' => 'Journal of Clinical Endocrinology',
                'date' => '8/21/2024',
                'tags' => ['Body Composition', 'Aging', 'Human Study'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
            [
                'id' => 3,
                'peptide' => 'PT-141',
                'evidenceLevel' => 'Medium Evidence',
                'title' => 'Melanocortin receptor activation and sexual function',
                'description' => 'Research explores mechanisms of action for melanocortin-based therapies in sexual dysfunction.',
                'source' => 'Sexual Medicine Reviews',
                'date' => '7/9/2024',
                'tags' => ['Libido', 'Mechanism', 'Review'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
            [
                'id' => 4,
                'peptide' => 'TB-500',
                'evidenceLevel' => 'High Evidence',
                'title' => 'Thymosin beta-4 promotes wound healing and reduces inflammation',
                'description' => 'Comprehensive study on the regenerative properties of TB-500 in various tissue repair models.',
                'source' => 'Nature Communications',
                'date' => '6/15/2024',
                'tags' => ['Wound Healing', 'Inflammation', 'Regeneration'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
            [
                'id' => 5,
                'peptide' => 'CJC-1295',
                'evidenceLevel' => 'High Evidence',
                'title' => 'Long-acting growth hormone releasing hormone analog effects on muscle growth',
                'description' => 'Clinical evaluation of CJC-1295 in promoting muscle growth and recovery in athletes.',
                'source' => 'Sports Medicine Journal',
                'date' => '5/22/2024',
                'tags' => ['Muscle Growth', 'Performance', 'Human Study'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
            [
                'id' => 6,
                'peptide' => 'Semaglutide',
                'evidenceLevel' => 'High Evidence',
                'title' => 'GLP-1 receptor agonists in weight management and metabolic health',
                'description' => 'Large-scale clinical trials demonstrating efficacy of semaglutide in obesity treatment.',
                'source' => 'New England Journal of Medicine',
                'date' => '4/10/2024',
                'tags' => ['Weight Loss', 'Metabolism', 'Clinical Trial'],
                'pubmedUrl' => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
        ];

        // Apply search filter if provided
        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $papers = array_filter($papers, function ($paper) use ($search) {
                return stripos(strtolower($paper['title']), $search) !== false ||
                       stripos(strtolower($paper['description']), $search) !== false ||
                       stripos(strtolower($paper['peptide']), $search) !== false;
            });
        }

        return array_values($papers);
    }

    /**
     * Get educational guides for Educational Guides section
     */
    private function getEducationalGuides($request)
    {
        // For now, return mock data. This can be replaced with actual database queries later
        $guides = [
            [
                'id' => 1,
                'tag' => 'Beginner',
                'readingTime' => '15 min',
                'title' => "Beginner's Guide to Peptides",
                'description' => 'Everything you need to know about peptides, from basics to first purchase.',
                'peptides' => ['BPC-157', 'TB-500', 'Ipamorelin'],
                'guideUrl' => '#',
            ],
            [
                'id' => 2,
                'tag' => 'Stacking',
                'readingTime' => '12 min',
                'title' => 'The Complete BPC-157 + TB-500 Healing Stack',
                'description' => 'Detailed protocol for combining BPC-157 and TB-500 for optimal healing results.',
                'peptides' => ['BPC-157', 'TB-500'],
                'guideUrl' => '#',
            ],
            [
                'id' => 3,
                'tag' => 'Dosage',
                'readingTime' => '10 min',
                'title' => 'Peptide Dosage Calculator & Safety Guidelines',
                'description' => 'Learn how to calculate proper dosages and follow safety protocols.',
                'peptides' => [],
                'guideUrl' => '#',
            ],
            [
                'id' => 4,
                'tag' => 'Advanced',
                'readingTime' => '20 min',
                'title' => 'Advanced: Growth Hormone Stack for Anti-Aging',
                'description' => 'Comprehensive guide to combining multiple GH secretagogues for anti-aging benefits.',
                'peptides' => ['Ipamorelin', 'CJC-1295', 'GHRP-6'],
                'guideUrl' => '#',
            ],
            [
                'id' => 5,
                'tag' => 'Beginner',
                'readingTime' => '8 min',
                'title' => 'Understanding Peptide Purity and COA',
                'description' => 'Learn how to read Certificate of Analysis and verify peptide quality.',
                'peptides' => [],
                'guideUrl' => '#',
            ],
            [
                'id' => 6,
                'tag' => 'Stacking',
                'readingTime' => '18 min',
                'title' => 'Weight Loss Peptide Stack Guide',
                'description' => 'Complete guide to combining GLP-1 agonists and other peptides for weight management.',
                'peptides' => ['Semaglutide', 'Tirzepatide'],
                'guideUrl' => '#',
            ],
        ];

        // Apply search filter if provided
        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $guides = array_filter($guides, function ($guide) use ($search) {
                return stripos(strtolower($guide['title']), $search) !== false ||
                       stripos(strtolower($guide['description']), $search) !== false ||
                       stripos(strtolower($guide['tag']), $search) !== false ||
                       (is_array($guide['peptides']) && 
                        array_filter($guide['peptides'], function ($peptide) use ($search) {
                            return stripos(strtolower($peptide), $search) !== false;
                        }));
            });
        }

        // Update guide URLs to use proper routes
        $guides = array_map(function ($guide) {
            $guide['guideUrl'] = route('guide.show', ['id' => $guide['id']]);
            return $guide;
        }, array_values($guides));

        return $guides;
    }

    /**
     * Show education guide detail page
     */
    public function showGuide($id)
    {
        // Get the guide data (for now using mock data, can be replaced with database query later)
        $allGuides = [
            1 => [
                'id' => 1,
                'tag' => 'Beginner',
                'readingTime' => '15 min',
                'title' => "Beginner's Guide to Peptides",
                'description' => 'Everything you need to know about peptides, from basics to first purchase.',
                'peptides' => ['BPC-157', 'TB-500', 'Ipamorelin'],
            ],
            2 => [
                'id' => 2,
                'tag' => 'Stacking',
                'readingTime' => '12 min',
                'title' => 'The Complete BPC-157 + TB-500 Healing Stack',
                'description' => 'Detailed protocol for combining BPC-157 and TB-500 for optimal healing results.',
                'peptides' => ['BPC-157', 'TB-500'],
            ],
            3 => [
                'id' => 3,
                'tag' => 'Dosage',
                'readingTime' => '10 min',
                'title' => 'Peptide Dosage Calculator & Safety Guidelines',
                'description' => 'Learn how to calculate proper dosages and follow safety protocols.',
                'peptides' => [],
            ],
            4 => [
                'id' => 4,
                'tag' => 'Advanced',
                'readingTime' => '20 min',
                'title' => 'Advanced: Growth Hormone Stack for Anti-Aging',
                'description' => 'Comprehensive guide to combining multiple GH secretagogues for anti-aging benefits.',
                'peptides' => ['Ipamorelin', 'CJC-1295', 'GHRP-6'],
            ],
            5 => [
                'id' => 5,
                'tag' => 'Beginner',
                'readingTime' => '8 min',
                'title' => 'Understanding Peptide Purity and COA',
                'description' => 'Learn how to read Certificate of Analysis and verify peptide quality.',
                'peptides' => [],
            ],
            6 => [
                'id' => 6,
                'tag' => 'Stacking',
                'readingTime' => '18 min',
                'title' => 'Weight Loss Peptide Stack Guide',
                'description' => 'Complete guide to combining GLP-1 agonists and other peptides for weight management.',
                'peptides' => ['Semaglutide', 'Tirzepatide'],
            ],
        ];

        if (!isset($allGuides[$id])) {
            abort(404, 'Guide not found');
        }

        $guide = $allGuides[$id];

        return Inertia::render('Frontend/EducationGuideDetail', [
            'id' => $guide['id'],
            'tag' => $guide['tag'],
            'readingTime' => $guide['readingTime'],
            'title' => $guide['title'],
            'description' => $guide['description'],
            'peptides' => $guide['peptides'],
            'guideUrl' => route('guide.show', ['id' => $guide['id']]),
        ]);
    }
}

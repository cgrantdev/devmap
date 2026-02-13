<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Research;
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

        // Apply filter to base query if needed
        $filteredQuery = (clone $query);
        if ($filter !== 'all') {
            $filteredQuery->where(function ($q) use ($filter) {
                $this->applyCategoryFilter($q, $filter);
            });
        }

        // Get featured blogs (3 for Featured Stories) - with filter applied
        $featuredBlogs = (clone $filteredQuery)
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')            
            ->get()
            ->map(function ($blog) {
                return $this->formatBlog($blog);
            });

        // Get latest blogs (excluding featured, for Latest Articles) - with filter applied
        $latestQuery = (clone $filteredQuery);
        if ($featuredBlogs->isNotEmpty()) {
            $latestQuery->whereNotIn('id', $featuredBlogs->pluck('id'));
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

        // Use blog_type from database if available, otherwise determine from content
        $categoryTag = $blog->blog_type ?: $this->getCategoryTag($blog->title, $blog->description, $blog->content);
        
        // Use blog's tags if available, otherwise extract from content
        $tags = !empty($blog->tags) ? $blog->tags : $this->extractTags($blog->title, $blog->description);
        
        // Use real author data if available, otherwise generate based on category
        $author = $blog->author_name ?: $this->getAuthor($blog->title, $categoryTag);
        $authorJob = $blog->author_job;

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->outline ?: $blog->description,
            'image' => $imageUrl,
            'date' => $blog->published_at ? $blog->published_at->format('m/d/Y') : null,
            'readTime' => $blog->read_time ?? '5 min',
            'categoryTag' => $categoryTag,
            'blogType' => $blog->blog_type,
            'tags' => $tags,
            'author' => $author,
            'authorJob' => $authorJob,
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

        return array_values(array_unique($tags)); // Return all unique tags
    }

    /**
     * Get author based on category (returns just the name, job title is handled separately)
     */
    private function getAuthor($title, $categoryTag)
    {
        $authors = [
            'Regulation' => 'Dr. Sarah Chen',
            'Research' => 'Dr. Michael Thompson',
            'Industry' => 'James Rodriguez',
            'Guides' => 'PeptideMap Editorial',
            'Community' => 'John M.',
        ];

        return $authors[$categoryTag] ?? 'PeptideMap Editorial';
    }

    /**
     * Apply category filter to query
     */
    private function applyCategoryFilter($query, $filter)
    {
        $filterLower = strtolower($filter);
        
        // Map filter values to blog_type values
        $filterToBlogType = [
            'research' => 'Research',
            'industry' => 'Industry',
            'regulation' => 'Regulation',
            'guides' => 'Guides',
            'community' => 'Community'
        ];
        
        if (isset($filterToBlogType[$filterLower])) {
            $query->where('blog_type', $filterToBlogType[$filterLower]);
        }
    }

    /**
     * Get research papers for Research Library
     */
    private function getResearchPapers($request)
    {
        $query = Research::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        // Apply search filter if provided
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('study_summary', 'like', "%{$search}%")
                  ->orWhere('peptide', 'like', "%{$search}%");
            });
        }

        $papers = $query->get()->map(function ($research) {
            // Format evidence level
            $evidenceLevel = $research->evidence_level ? $research->evidence_level . ' Evidence' : null;

            return [
                'id' => $research->id,
                'peptide' => $research->peptide,
                'evidenceLevel' => $evidenceLevel,
                'title' => $research->title,
                'description' => $research->study_summary ?: 'No summary available.',
                'source' => $research->journal_type ?: 'Not specified',
                'date' => $research->published_at ? $research->published_at->format('n/j/Y') : null,
                'tags' => $research->tags ?? [],
                'pubmedUrl' => $research->pubmed_url ?: '#',
            ];
        })->toArray();

        return $papers;
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

    /**
     * Show research study detail page
     */
    public function showResearch($id)
    {
        $research = Research::where('id', $id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Format evidence level
        $evidenceLevel = $research->evidence_level ? $research->evidence_level . ' Evidence' : null;

        return Inertia::render('Frontend/ResearchStudyDetail', [
            'id' => $research->id,
            'peptide' => $research->peptide,
            'evidenceLevel' => $evidenceLevel,
            'title' => $research->title,
            'studySummary' => $research->study_summary,
            'journalType' => $research->journal_type,
            'date' => $research->published_at ? $research->published_at->format('F j, Y') : null,
            'studyType' => $research->study_type,
            'background' => $research->background,
            'keyFindings' => $research->key_findings ?? [],
            'methodology' => $research->methodology,
            'clinicalImplications' => $research->clinical_implications,
            'limitations' => $research->limitations,
            'tags' => $research->tags ?? [],
            'pubmedUrl' => $research->pubmed_url ?: '#',
        ]);
    }
}

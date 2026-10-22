<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Setting;
use App\Support\FaqPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class BlogsController extends Controller
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

            return mb_substr($value, 0, $limit).$end;
        }

        // Fallback to regular string functions
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit).$end;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);

        $blogs = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Format blogs for frontend
        $formattedBlogs = $blogs->map(function ($blog) {
            $imageUrl = ImageHelper::listingImageUrl($blog->image);

            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'outline' => $blog->outline,
                'image' => $imageUrl,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('d M Y') : null,
            ];
        });

        $newest = $formattedBlogs->first();
        $seoData = new SEOData(
            title: 'Peptide Research News & Guides — Peptidemap',
            description: 'Stay updated with the latest peptide research, industry news, guides, and educational content. Expert insights and comprehensive information for researchers.',
            image: is_array($newest) ? ($newest['image'] ?? null) : null,
            url: url('/blogs'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/BlogListing', [
            'blogs' => [
                'data' => $formattedBlogs,
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'from' => $blogs->firstItem(),
                'to' => $blogs->lastItem(),
            ],
        ]);
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Get related blogs (exclude current)
        $related = Blog::where('id', '!=', $blog->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'slug' => $b->slug,
                    'description' => $b->description,
                    'image' => ImageHelper::listingImageUrl($b->image),
                    'readTime' => $b->read_time,
                    'date' => $b->published_at ? $b->published_at->format('M j, Y') : null,
                ];
            });

        $imageUrl = ImageHelper::listingImageUrl($blog->image);

        // Use blog_type for category tag, fallback to computed tag if not set
        $combinedContent = $blog->introduction.' '.$blog->detailed_analysis.' '.$blog->conclusion;
        $categoryTag = $blog->blog_type ?: $this->getCategoryTag($blog->title, $blog->description, $combinedContent);

        // Generate SEO data for blog detail
        // Priority: Use stored SEO data from database, fallback to auto-generated
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        $blogUrl = 'https://peptidemap.com/blog/'.$blog->slug;

        // Build blog image URL - handle both absolute URLs and relative paths
        $blogImage = null;
        if ($imageUrl) {
            if (str_starts_with($imageUrl, 'http')) {
                $blogImage = $imageUrl;
            } else {
                $blogImage = url($imageUrl);
            }
        }

        // Check if stored SEO data exists
        $hasStoredSeo = ! empty($blog->seo_page_title) || ! empty($blog->seo_description);

        if ($hasStoredSeo) {
            // Use stored SEO data from database
            $seoTitle = $blog->seo_page_title ?: ($blog->title.' - '.$siteName);
            $seoDescription = $blog->seo_description
                ?: ($blog->description
                    ? $this->safeLimit($blog->description, 160)
                    : 'Read the latest article about '.$blog->title.' on '.$siteName.'.');
            $seoOgTitle = $blog->seo_og_title ?: $seoTitle;
            $seoOgDescription = $blog->seo_og_description ?: $seoDescription;
            $storedOg = ImageHelper::listingImageUrl($blog->seo_og_image);
            $seoOgImage = $storedOg
                ? (str_starts_with($storedOg, 'http') ? $storedOg : url($storedOg))
                : $blogImage;
        } else {
            // Auto-generate SEO from blog fields
            $seoTitle = $blog->title.' - '.$siteName;
            $seoDescription = $blog->description
                ? $this->safeLimit($blog->description, 160)
                : 'Read the latest article about '.$blog->title.' on '.$siteName.'.';
            $seoOgTitle = $seoTitle;
            $seoOgDescription = $seoDescription;
            $seoOgImage = $blogImage;
        }

        // Article + BreadcrumbList JSON-LD (rendered by app.blade.php)
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $blog->title,
            'description' => $seoDescription,
            'image' => [$seoOgImage ?: $blogImage ?: 'https://peptidemap.com/images/og-default-v8.png'],
            'datePublished' => $blog->published_at ? $blog->published_at->toIso8601String() : null,
            'dateModified' => $blog->updated_at ? $blog->updated_at->toIso8601String() : ($blog->published_at ? $blog->published_at->toIso8601String() : null),
            'author' => [
                '@type' => ($blog->author_name && $blog->author_name !== 'Peptidemap') ? 'Person' : 'Organization',
                'name' => $blog->author_name ?: $siteName,
                'url' => 'https://peptidemap.com',
            ],
            'keywords' => $blog->tags ?: null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => 'https://peptidemap.com/images/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $blogUrl,
            ],
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://peptidemap.com/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'https://peptidemap.com/blogs'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $blog->title, 'item' => $blogUrl],
            ],
        ];

        // One normalised {question, answer} list. faqPageSchema() and the
        // visible FAQ prop both receive it. Other stored schema blocks stay.
        [$otherSchemas, $faqs, $faqId] = $this->partitionStoredSchema($blog->seo_schema);
        $schemas = array_merge([$articleSchema, $breadcrumbSchema], $otherSchemas);
        if ($faqSchema = $this->faqPageSchema($faqs, $faqId)) {
            $schemas[] = $faqSchema;
        }

        // Build SEO array (same format as products/brands pages)
        $seo = [
            'key' => 'blog',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoOgTitle,
            'og_description' => $seoOgDescription,
            'og_image' => $seoOgImage ?: 'https://peptidemap.com/images/og-default-v8.png',
            'og_type' => 'article',
            // Backward-compatible field used by some pages
            'image' => $seoOgImage,
            'url' => $blogUrl,
            'canonical' => $blogUrl,
            'h1' => $blog->title,
            'schema' => $schemas,
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/BlogDetail', [
            'faqs' => $faqs,
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'outline' => $blog->outline,
                'introduction' => $blog->introduction,
                'key_points' => $blog->key_points ?? [],
                'detailed_analysis' => $blog->detailed_analysis,
                'conclusion' => $blog->conclusion,
                // Markdown FAQ/FAQs heading blocks are the publisher's
                // second copy of seo_schema. Drop that block on the way
                // to the view so the section below is the only FAQ.
                'content' => $this->withoutMarkdownFaqHeading($blog->content, $faqs),
                'tags' => $blog->tags ?? [],
                'author_name' => $blog->author_name,
                'author_job' => $blog->author_job,
                'blog_type' => $blog->blog_type,
                'image' => $imageUrl,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('F d, Y') : null,
                'is_featured' => $blog->is_featured ?? false,
                'categoryTag' => $categoryTag,
            ],
            'related' => $related,
            'seo' => $seo,
        ]);
    }

    /**
     * Get category tag based on content
     */
    private function getCategoryTag($title, $description, $content = '')
    {
        $combined = strtolower($title.' '.$description.' '.$content);

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
     * FAQPage node for the same pairs BlogDetail renders.
     *
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>|null
     */
    private function faqPageSchema(array $faqs, ?string $id): ?array
    {
        return FaqPage::schema($faqs, $id);
    }

    /**
     * Split stored seo_schema into non-FAQ blocks plus one FAQ pair list.
     * Pair strings are the FAQPage name and acceptedAnswer.text, unchanged.
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array{question: string, answer: string}>, 2: ?string}
     */
    private function partitionStoredSchema(mixed $extra): array
    {
        $other = [];
        $faqs = [];
        $id = null;
        if (is_string($extra)) {
            $extra = json_decode($extra, true);
        }
        if (! is_array($extra)) {
            return [$other, $faqs, $id];
        }
        if (isset($extra['@type'])) {
            $extra = [$extra];
        }

        foreach ($extra as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['@type'] ?? null) !== 'FAQPage') {
                $other[] = $block;

                continue;
            }
            if ($id === null && is_string($block['@id'] ?? null) && $block['@id'] !== '') {
                $id = $block['@id'];
            }
            foreach ($block['mainEntity'] ?? [] as $entity) {
                if (! is_array($entity)) {
                    continue;
                }
                $name = $entity['name'] ?? null;
                $text = $entity['acceptedAnswer']['text'] ?? null;
                if (! is_string($name) || $name === '' || ! is_string($text) || $text === '') {
                    continue;
                }
                $faqs[] = ['question' => $name, 'answer' => $text];
            }
        }

        return [$other, $faqs, $id];
    }

    /**
     * Drop the markdown FAQ heading when this post already has FAQPage
     * entities. The visible section is those entities, so the heading
     * block (FAQ, FAQs, or Frequently Asked Questions, through the next
     * h2) is not rendered a second time. Stored content is not rewritten.
     * The block is removed only when its plain text already contains
     * every JSON-LD question and answer, which is the duplicate copy.
     *
     * @param  list<array{question: string, answer: string}>  $faqs
     */
    private function withoutMarkdownFaqHeading(?string $html, array $faqs): ?string
    {
        if ($html === null || $html === '' || $faqs === []) {
            return $html;
        }

        $pattern = '/<h2>\s*(?:FAQs?|Frequently Asked Questions)\s*<\/h2>.*?(?=<h2\b|\z)/is';
        if (! preg_match($pattern, $html, $match)) {
            return $html;
        }

        $plain = html_entity_decode(strip_tags($match[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach ($faqs as $faq) {
            if (! str_contains($plain, $faq['question']) || ! str_contains($plain, $faq['answer'])) {
                return $html;
            }
        }

        $stripped = preg_replace($pattern, '', $html, 1);

        return is_string($stripped) ? $stripped : $html;
    }
}

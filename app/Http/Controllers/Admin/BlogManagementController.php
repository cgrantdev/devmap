<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Redirect;
use App\Support\AgencyFields;
use App\Support\OrforglipronOralSemaglutideComparePage;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Helpers\ImageHelper;

class BlogManagementController extends Controller
{
    public function index(Request $request)
    {
        $blogs = Blog::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'blog_type' => $blog->blog_type,
                    'author_name' => $blog->author_name,
                    'description' => $blog->description,
                    'image' => ImageHelper::listingImageUrl($blog->image),
                    'read_time' => $blog->read_time,
                    'published_at' => $blog->published_at ? $blog->published_at->format('M j, Y') : null,
                    'is_featured' => $blog->is_featured,
                    'status' => $blog->status,
                    'created_at' => $blog->created_at->format('M j, Y'),
                ];
            });

        return Inertia::render('Admin/Blogs', [
            'blogs' => $blogs,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/BlogEdit', [
            'blog' => null,
            'faqsReady' => self::faqColumnReady(),
        ]);
    }

    /**
     * The post's URL slug (Jake, Nov 18 2026: the agency sets each post's
     * URL). A typed slug is normalised to lowercase and hyphens; a blank
     * one is built from the title. Editing the title alone no longer
     * changes the URL. The orforglipron post is fixed because
     * /compare/orforglipron-vs-oral-semaglutide renders it by slug.
     */
    private function resolveSlug(?string $typed, string $title, ?Blog $blog = null): string
    {
        if ($blog && OrforglipronOralSemaglutideComparePage::isBlogSlug((string) $blog->slug)) {
            return (string) $blog->slug;
        }

        $slug = Str::slug(trim((string) $typed) !== '' ? (string) $typed : $title);
        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'The URL needs at least one letter or number.']);
        }
        $taken = Blog::where('slug', $slug)
            ->when($blog, fn ($q) => $q->where('id', '!=', $blog->id))
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages(['slug' => "Another post already uses /blog/{$slug}. Pick a different URL."]);
        }

        return $slug;
    }

    /**
     * A published post's old URL 301s to its new one. Redirects that
     * pointed at the old URL now point at the new one (no chains), and a
     * redirect away from the new URL is removed (no loops).
     */
    private function redirectOldUrl(string $oldSlug, string $newSlug): void
    {
        $from = '/blog/'.$oldSlug;
        $to = '/blog/'.$newSlug;

        Redirect::where('from_path', $to)->delete();
        Redirect::where('to_path', $from)->update(['to_path' => $to]);
        Redirect::updateOrCreate(['from_path' => $from], [
            'to_path' => $to,
            'status_code' => 301,
            'note' => 'Blog URL changed in the blog editor',
            'created_by_user_id' => auth()->id(),
        ]);
        Cache::forget('redirects:map');
    }

    /** blogs.faqs exists (the 2026_11_19 migration has run). */
    private static function faqColumnReady(): bool
    {
        return Schema::hasColumn('blogs', 'faqs');
    }

    /**
     * Article body and FAQs from the editor (Jake, Nov 17 2026). The body
     * is cleaned to the tags the article styles support. FAQs are saved
     * only when the form sent its FAQ list (faqs_sent), so an empty list
     * clears them, and only once the column exists.
     */
    private function applyBodyAndFaqs(Request $request, array $validated): array
    {
        if (array_key_exists('content', $validated)) {
            $validated['content'] = SafeHtml::clean($validated['content']);
        }

        $faqsSent = $request->boolean('faqs_sent');
        unset($validated['faqs_sent']);
        if ($faqsSent && self::faqColumnReady()) {
            $validated['faqs'] = AgencyFields::faqs($validated['faqs'] ?? []);
        } else {
            unset($validated['faqs']);
        }

        return $validated;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_type' => 'nullable|string|in:Regulation,Research,Community,Guides,Industry',
            'author_name' => 'nullable|string|max:255',
            'author_job' => 'nullable|string|max:255',
            'outline' => 'nullable|string',
            'slug' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'introduction' => 'nullable|string',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'detailed_analysis' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'content' => 'nullable|string|max:200000',
            'faqs_sent' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ] + AgencyFields::faqRules(), AgencyFields::messages());
        $validated = $this->applyBodyAndFaqs($request, $validated);

        // URL: the typed slug, or one built from the title.
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['title']);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'blogs');
        } else {
            unset($validated['image']);
        }

        $blog = Blog::create($validated);

        return redirect("/admin/blogs/{$blog->id}/edit")
            ->with('success', 'Blog post created successfully.');
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);

        return Inertia::render('Admin/BlogEdit', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'blog_type' => $blog->blog_type,
                'author_name' => $blog->author_name,
                'author_job' => $blog->author_job,
                'outline' => $blog->outline,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'introduction' => $blog->introduction,
                'key_points' => $blog->key_points ?? [],
                'detailed_analysis' => $blog->detailed_analysis,
                'conclusion' => $blog->conclusion,
                'tags' => $blog->tags ?? [],
                'content' => $blog->content,
                // Saved list once the post has one; before that, the
                // FAQs its stored FAQPage schema already shows.
                'faqs' => $blog->hasFaqColumn() && $blog->faqs !== null
                    ? AgencyFields::faqs($blog->faqs)
                    : $blog->schemaFaqs(),
                'faqs_saved' => $blog->hasFaqColumn() && $blog->faqs !== null,
                // /compare/orforglipron-vs-oral-semaglutide renders this
                // post by slug, so its URL cannot change.
                'slug_locked' => OrforglipronOralSemaglutideComparePage::isBlogSlug((string) $blog->slug),
                'image' => ImageHelper::listingImageUrl($blog->image),
                'read_time' => $blog->read_time,
                'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d') : null,
                'is_featured' => $blog->is_featured,
                'status' => $blog->status,
                'seo_page_title' => $blog->seo_page_title,
                'seo_description' => $blog->seo_description,
                'seo_og_title' => $blog->seo_og_title,
                'seo_og_description' => $blog->seo_og_description,
                'seo_og_image' => $blog->seo_og_image,
            ],
            'faqsReady' => self::faqColumnReady(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_type' => 'nullable|string|in:Regulation,Research,Community,Guides,Industry',
            'author_name' => 'nullable|string|max:255',
            'author_job' => 'nullable|string|max:255',
            'outline' => 'nullable|string',
            'slug' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'introduction' => 'nullable|string',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'detailed_analysis' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'content' => 'nullable|string|max:200000',
            'faqs_sent' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ] + AgencyFields::faqRules(), AgencyFields::messages());
        $validated = $this->applyBodyAndFaqs($request, $validated);

        // URL: the typed slug, or one built from the title when blank.
        // The title alone no longer moves the URL.
        $oldSlug = (string) $blog->slug;
        $wasPublished = $blog->status === 'published';
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['title'], $blog);

        // Handle status change and published_at
        $oldStatus = $blog->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            // Status changed to published - set published_at to now
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            // Status is draft - clear published_at
            $validated['published_at'] = null;
        } else {
            // Status remains published - keep existing published_at
            unset($validated['published_at']);
        }

        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($blog->image) {
                ImageHelper::deleteImage($blog->image, 'blogs');
            }
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'blogs');
        } else {
            unset($validated['image']);
        }

        $blog->update($validated);

        if ($wasPublished && $blog->status === 'published' && $oldSlug !== '' && $oldSlug !== $blog->slug) {
            $this->redirectOldUrl($oldSlug, $blog->slug);
        }

        return redirect()->back()
            ->with('success', 'Blog post updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // The URL stays put when the title changes; it is set in the
        // blog editor's URL field (Nov 2026).

        // Handle status change and published_at
        $oldStatus = $blog->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            // Status changed to published - set published_at to now
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            // Status is draft - clear published_at
            $validated['published_at'] = null;
        } else {
            // Status remains published - keep existing published_at
            unset($validated['published_at']);
        }

        $blog->update($validated);

        return redirect()->back()->with('success', 'Blog post updated successfully.');
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post deleted successfully.');
    }
}

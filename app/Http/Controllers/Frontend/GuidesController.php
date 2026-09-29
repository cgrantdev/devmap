<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\EducationalGuide;
use App\Models\Setting;
use Inertia\Inertia;

class GuidesController extends Controller
{
    public function index()
    {
        $guides = EducationalGuide::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (EducationalGuide $guide) => [
                'title' => $guide->title,
                'slug' => $guide->slug,
                'description' => $guide->description,
                'readingTime' => $guide->reading_time,
                'tag' => $guide->tag ?: $guide->guide_type,
                'date' => $guide->published_at?->format('F j, Y'),
                'cover' => ImageHelper::listingImageUrl($guide->cover),
            ]);

        $seo = $this->seo(
            key: 'guides',
            title: 'Research Peptide Guides',
            description: 'Educational guides to research peptides: definitions, COAs, vendor checks, and FDA, RUO, and compounding literacy. Not medical or legal advice.',
            path: '/guides',
            h1: 'Research Peptide Guides',
            ogType: 'website',
        );

        return Inertia::render('Frontend/GuideListing', [
            'guides' => $guides,
            'seo' => $seo,
        ]);
    }

    public function show(string $slug)
    {
        $guide = EducationalGuide::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->firstOrFail();

        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        $path = '/guides/'.$guide->slug;
        $url = 'https://peptidemap.com'.$path;
        $seoTitle = $guide->seo_page_title ?: $guide->title;
        $seoDescription = $guide->seo_description ?: ($guide->description ?: $guide->title);
        $image = $guide->seo_og_image ?: 'https://peptidemap.com/images/og-default-v7.png';

        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $guide->title,
            'name' => $seoTitle,
            'description' => $seoDescription,
            'image' => [$image],
            'datePublished' => $guide->published_at?->toDateString(),
            'dateModified' => $guide->updated_at?->toIso8601String() ?: $guide->published_at?->toDateString(),
            'inLanguage' => 'en-US',
            'author' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => 'https://peptidemap.com',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => 'https://peptidemap.com',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => 'https://peptidemap.com/images/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://peptidemap.com/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guides', 'item' => 'https://peptidemap.com/guides'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $guide->title, 'item' => $url],
            ],
        ];

        $seo = $this->seo(
            key: 'guide',
            title: $seoTitle,
            description: $seoDescription,
            path: $path,
            h1: $guide->title,
            ogType: 'article',
            ogTitle: $guide->seo_og_title ?: $seoTitle,
            ogDescription: $guide->seo_og_description ?: $seoDescription,
            ogImage: $image,
            schema: array_merge([$articleSchema, $breadcrumbSchema], $this->extraSchema($guide->seo_schema)),
        );

        return Inertia::render('Frontend/GuideArticle', [
            'guide' => [
                'title' => $guide->title,
                'slug' => $guide->slug,
                'description' => $guide->description,
                'content' => $guide->content,
                'readingTime' => $guide->reading_time,
                'tag' => $guide->tag ?: $guide->guide_type,
                'date' => $guide->published_at?->format('F j, Y'),
            ],
            'seo' => $seo,
        ]);
    }

    private function seo(
        string $key,
        string $title,
        string $description,
        string $path,
        string $h1,
        string $ogType,
        ?string $ogTitle = null,
        ?string $ogDescription = null,
        ?string $ogImage = null,
        array $schema = [],
    ): array {
        $url = 'https://peptidemap.com'.$path;
        $seo = [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'og_title' => $ogTitle ?: $title,
            'og_description' => $ogDescription ?: $description,
            'og_image' => $ogImage,
            'og_type' => $ogType,
            'image' => $ogImage,
            'url' => $url,
            'canonical' => $url,
            'h1' => $h1,
        ];
        if ($schema) {
            $seo['schema'] = $schema;
        }
        session(['page_seo_data' => $seo]);

        return $seo;
    }

    private function extraSchema(mixed $schema): array
    {
        if (is_string($schema)) {
            $schema = json_decode($schema, true);
        }
        if (! is_array($schema)) {
            return [];
        }
        if (isset($schema['@type'])) {
            return [$schema];
        }

        return array_values(array_filter($schema, 'is_array'));
    }
}

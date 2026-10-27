<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Scan every published long-form content field for internal-note markers
 * bleeding into public pages. Advantage Collective Oct 2026 audit, Part 5:
 * ~12 internal notes like "Refused API access - manual updates only" showing
 * on public pages. SEO agency can click through to the admin edit page for
 * each hit and clean it inline.
 */
class ContentScannerController extends Controller
{
    private const MARKERS = [
        'Refused API',
        'manual updates only',
        'TODO:',
        'TODO ',
        'FIXME',
        'XXX ',
        '[internal]',
        '(internal)',
        'INTERNAL NOTE',
        'placeholder',
        'Lorem ipsum',
        'test test test',
    ];

    public function index()
    {
        $hits = collect();

        // Products — vendor + generated descriptions
        if (Schema::hasTable('products')) {
            $prodQ = Product::query()->select('id', 'name', 'slug', 'brand_id', 'description');
            if (Schema::hasColumn('products', 'generated_description')) {
                $prodQ->addSelect('generated_description');
            }
            if (Schema::hasColumn('products', 'vendor_description')) {
                $prodQ->addSelect('vendor_description');
            }
            foreach ($prodQ->cursor() as $p) {
                foreach (['description', 'generated_description', 'vendor_description'] as $field) {
                    if (!isset($p->$field)) continue;
                    $marker = $this->findMarker((string) $p->$field);
                    if ($marker) {
                        $hits->push([
                            'type' => 'Product',
                            'field' => $field,
                            'marker' => $marker,
                            'excerpt' => $this->excerpt($p->$field, $marker),
                            'label' => $p->name,
                            'edit_url' => "/admin/products/{$p->id}/edit",
                        ]);
                    }
                }
            }
        }

        // Education / encyclopedia posts
        if (Schema::hasTable('education_posts')) {
            foreach (EducationPost::select('id', 'title', 'slug', 'content')->cursor() as $e) {
                $marker = $this->findMarker((string) $e->content);
                if ($marker) {
                    $hits->push([
                        'type' => 'Encyclopedia',
                        'field' => 'content',
                        'marker' => $marker,
                        'excerpt' => $this->excerpt($e->content, $marker),
                        'label' => $e->title,
                        'edit_url' => "/admin/encyclopedia/{$e->id}/edit",
                    ]);
                }
            }
        }

        // Blogs
        if (Schema::hasTable('blogs')) {
            foreach (Blog::select('id', 'title', 'slug', 'content')->cursor() as $b) {
                $marker = $this->findMarker((string) $b->content);
                if ($marker) {
                    $hits->push([
                        'type' => 'Blog',
                        'field' => 'content',
                        'marker' => $marker,
                        'excerpt' => $this->excerpt($b->content, $marker),
                        'label' => $b->title,
                        'edit_url' => "/admin/blogs/{$b->id}/edit",
                    ]);
                }
            }
        }

        // Brand descriptions
        if (Schema::hasTable('brands')) {
            foreach (Brand::select('id', 'name', 'slug', 'description')->cursor() as $b) {
                $marker = $this->findMarker((string) $b->description);
                if ($marker) {
                    $hits->push([
                        'type' => 'Brand',
                        'field' => 'description',
                        'marker' => $marker,
                        'excerpt' => $this->excerpt($b->description, $marker),
                        'label' => $b->name,
                        'edit_url' => "/admin/vendors/{$b->id}/edit",
                    ]);
                }
            }
        }

        return Inertia::render('Admin/ContentScanner', [
            'hits' => $hits->values(),
            'markers' => self::MARKERS,
            'summary' => [
                'total' => $hits->count(),
                'by_type' => $hits->groupBy('type')->map->count(),
            ],
        ]);
    }

    private function findMarker(string $content): ?string
    {
        foreach (self::MARKERS as $m) {
            if (stripos($content, $m) !== false) return $m;
        }
        return null;
    }

    private function excerpt(string $content, string $marker, int $pad = 60): string
    {
        $pos = stripos($content, $marker);
        if ($pos === false) return mb_substr(strip_tags($content), 0, 160);
        $start = max(0, $pos - $pad);
        $len = mb_strlen($marker) + $pad * 2;
        $snippet = mb_substr(strip_tags($content), $start, $len);
        return ($start > 0 ? '…' : '') . trim($snippet) . '…';
    }
}

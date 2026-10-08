<?php

namespace App\Models;

use App\Support\CompareSlug;
use App\Support\EncyclopediaSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'meta_title',
        'meta_description',
        'is_active',
        'research_area',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug;
                $counter = 1;
                
                // Ensure unique slug
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $category->slug = $slug;
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function aliases()
    {
        return $this->hasMany(CategoryAlias::class, 'product_category_id');
    }

    public function educationPost()
    {
        return $this->hasOne(EducationPost::class, 'product_category_id');
    }

    /**
     * Resolve the category a /compare/{slug} URL should serve.
     *
     * Prefers an exact stored slug, then a case-insensitive match
     * (MySQL's collation already does this; SQLite and binary collations
     * do not), then a unique normalized match so "Vitamin B12" is reachable
     * at /compare/vitamin-b12. Ambiguous normalizations return null.
     */
    public static function findForCompareSlug(string $routeSlug): ?self
    {
        $routeSlug = trim($routeSlug);
        if ($routeSlug === '') {
            return null;
        }

        $exact = static::query()->where('is_active', true)->where('slug', $routeSlug)->first();
        if ($exact) {
            return $exact;
        }

        $lower = static::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(slug) = ?', [strtolower($routeSlug)])
            ->orderBy('id')
            ->first();
        if ($lower) {
            return $lower;
        }

        $canonical = CompareSlug::canonical($routeSlug);
        if ($canonical === null) {
            return null;
        }

        $matches = static::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (self $category) => CompareSlug::canonical($category->slug) === $canonical)
            ->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return $matches->first(fn (self $category) => $category->slug === $routeSlug)
            ?? $matches->first(fn (self $category) => strtolower((string) $category->slug) === strtolower($routeSlug));
    }

    /**
     * Category row for a forced public encyclopedia slug (hyphen form),
     * including short aliases such as PBS.
     */
    public static function findForPublicSlug(string $publicSlug): ?self
    {
        $found = static::findForCompareSlug($publicSlug);
        if ($found) {
            return $found;
        }

        $aliases = EncyclopediaSlug::aliasesFor($publicSlug);

        return static::query()
            ->where('is_active', true)
            ->where(function ($query) use ($aliases) {
                foreach ($aliases as $alias) {
                    $query->orWhereRaw('LOWER(slug) = ?', [$alias])
                        ->orWhereRaw('LOWER(name) = ?', [$alias]);
                }
            })
            ->first();
    }

    /**
     * Bare /Name/Name (and three-segment) blend paths have no route of
     * their own. Reuse the encyclopedia redirect so a known slash blend
     * 301s to its compare or encyclopedia URL, and unknown pairs 404.
     *
     * @param  list<string>  $segments
     */
    public static function bareBlendRedirectPath(array $segments): ?string
    {
        if (count($segments) < 2) {
            return null;
        }

        return static::encyclopediaRedirectPath(implode('/', $segments));
    }

    /**
     * Where a non-canonical encyclopedia request should 301, if anywhere.
     *
     * Forced families (Vitamin B12, HGH 191AA, PBS, sterile water, and
     * the other hyphen canonicals) render only at the exact hyphen URL.
     * Letter case counts: /encyclopedia/VITAMIN-B12 is not that URL.
     * Every other resolvable entry renders only at its stored slug.
     * MySQL's case-insensitive collation must not treat a different case
     * as an exact match — the comparison is in PHP. Slash blends have
     * no encyclopedia page; their live URL is the compare page.
     */
    public static function encyclopediaRedirectPath(string $requestedSlug): ?string
    {
        $public = EncyclopediaSlug::publicSlug($requestedSlug);
        if ($public !== null) {
            if (! static::findForPublicSlug($public)) {
                return null;
            }
            if ($requestedSlug === $public) {
                return null;
            }

            return '/encyclopedia/'.$public;
        }

        if (EncyclopediaSlug::isResolvable($requestedSlug)) {
            $matches = static::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(slug) = ?', [strtolower($requestedSlug)])
                ->orderBy('id')
                ->get();

            if ($matches->contains(fn (self $category) => (string) $category->slug === $requestedSlug)) {
                return null;
            }

            if ($matches->count() === 1) {
                $path = EncyclopediaSlug::path((string) $matches->first()->slug);
                if ($path !== null && $path !== '/encyclopedia/'.$requestedSlug) {
                    return $path;
                }
            }
        }

        $canonical = CompareSlug::canonical($requestedSlug);
        if ($canonical === null) {
            return null;
        }

        $category = static::findForCompareSlug($canonical);
        if (! $category) {
            return null;
        }

        $stored = (string) $category->slug;
        $path = EncyclopediaSlug::path($stored);
        if ($path !== null && $path !== '/encyclopedia/'.$requestedSlug) {
            return $path;
        }

        if (! EncyclopediaSlug::isResolvable($stored) && CompareSlug::canonical($stored) === $canonical) {
            return '/compare/'.$canonical;
        }

        return null;
    }

    /**
     * Get the decoded category name (HTML entities decoded)
     */
    public function getNameAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

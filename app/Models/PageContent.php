<?php

namespace App\Models;

use App\Support\AgencyFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Copy the SEO agency writes for one public page that has no other home
 * for it: the H1, an intro under it, the main body, and a FAQ list that
 * shows on the page and is the page's FAQPage JSON-LD.
 *
 * Keys: vendor (/brand) and coupon pages use the brand id; calculator
 * pages use the preset slug, or "index" for /calculator. Coupon pages
 * also keep their title and meta description here.
 *
 * Agency copy replaces the page's default copy; it never sits beside it.
 */
class PageContent extends Model
{
    public const BRAND = 'brand';

    public const COUPON = 'coupon';

    public const CALCULATOR = 'calculator';

    /** page_key for /calculator itself. */
    public const CALCULATOR_INDEX = 'index';

    public const READY_CACHE_KEY = 'schema.page_contents.ready';

    protected $fillable = [
        'page_type',
        'page_key',
        'title',
        'meta_description',
        'h1',
        'intro_html',
        'body_html',
        'faqs',
    ];

    protected $casts = [
        'faqs' => 'array',
    ];

    /**
     * False until the 2026_11_19 migration has run. Migrations are run by
     * hand after a deploy, so pages render their defaults until then
     * instead of failing on a missing table. Only "true" is cached.
     */
    public static function ready(): bool
    {
        if (Cache::get(self::READY_CACHE_KEY)) {
            return true;
        }
        $ready = Schema::hasTable('page_contents');
        if ($ready) {
            Cache::forever(self::READY_CACHE_KEY, true);
        }

        return $ready;
    }

    /** The stored copy for one page, or null. */
    public static function forPage(string $type, string|int $key): ?self
    {
        if (! self::ready()) {
            return null;
        }

        return self::query()
            ->where('page_type', $type)
            ->where('page_key', (string) $key)
            ->first();
    }

    /** page_key values that have copy, for editor list badges. */
    public static function keysWithCopy(string $type): array
    {
        if (! self::ready()) {
            return [];
        }

        return self::query()->where('page_type', $type)->pluck('page_key')->map(fn ($k) => (string) $k)->all();
    }

    /** @return list<array{question: string, answer: string}> */
    public function faqPairs(): array
    {
        return AgencyFields::faqs($this->faqs);
    }
}

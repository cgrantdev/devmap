<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Clears the lab-test values DiscoverProductsJob used to invent.
 *
 * That job wrote purity = 99.00 and lab_tested = true on every product it
 * created. Peptidemap does not test products and products have no COA,
 * certificate, or lab-report column, so those two flags are not evidence.
 *
 * A row is cleared only when it still matches that exact scraper write and
 * nothing on the row says a person or another importer supplied the values.
 * See matchingQuery() for the rule. Running this twice is a no-op.
 */
class ScraperDefaultLabClaims
{
    /**
     * The only purity DiscoverProductsJob ever wrote.
     */
    public const DEFAULT_PURITY = 99.0;

    /**
     * Rows that still carry the untouched scraper default.
     *
     * Kept (not this bug):
     * - auto_scraped is false. Vendor API pushes, admin creates, XML/feed
     *   imports, and the demo seeder never set auto_scraped, so a purity
     *   on those rows was supplied by that other path.
     * - purity is anything other than 99.00, including null. Only the
     *   discovery job hardcoded 99.0. A different number was typed later.
     * - lab_tested is false. The discovery job always wrote the pair. If
     *   one half was changed, the pair is no longer the default.
     * - manual_override is true. An admin locked the row.
     * - verified is true. The discovery job never sets products.verified.
     *   A true value is an admin checkbox, so the row is no longer an
     *   untouched scraper insert.
     * - is_demo is true. Demo catalog rows are synthetic and are not
     *   auto_scraped; excluded explicitly so a demo row cannot match.
     *
     * Vendor independent_testing_notes and product descriptions are not
     * treated as COA evidence. They are free text, and the discovery job
     * copied descriptions without reading a purity out of them.
     */
    public static function matchingQuery(): Builder
    {
        return DB::table('products')
            ->where('auto_scraped', true)
            ->where('lab_tested', true)
            // Match the decimal the discovery job wrote (99.00). Compare as
            // an integer and as the decimal strings MySQL returns so SQLite
            // and MySQL both hit the same rows. A bound float misses on
            // SQLite (99.0 bound as REAL does not equal stored 99).
            ->where(function ($query) {
                $query->where('purity', (int) self::DEFAULT_PURITY)
                    ->orWhere('purity', number_format(self::DEFAULT_PURITY, 2, '.', ''))
                    ->orWhere('purity', number_format(self::DEFAULT_PURITY, 1, '.', ''));
            })
            ->where(function ($query) {
                $query->where('manual_override', false)->orWhereNull('manual_override');
            })
            ->where(function ($query) {
                $query->where('verified', false)->orWhereNull('verified');
            })
            ->where(function ($query) {
                $query->where('is_demo', false)->orWhereNull('is_demo');
            });
    }

    /**
     * Null purity and clear lab_tested on matching rows. Returns how many
     * rows changed. Safe to run more than once.
     */
    public static function clear(): int
    {
        return self::matchingQuery()->update([
            'purity' => null,
            'lab_tested' => false,
            'updated_at' => now(),
        ]);
    }
}

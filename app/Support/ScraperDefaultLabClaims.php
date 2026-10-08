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
     *   imports, and the demo seeder leave auto_scraped false, so a purity
     *   on those rows was supplied by that other path.
     *   IngestionService::promote also sets auto_scraped true on an existing
     *   product when auto_update is on, or when the staged row is
     *   manual_override. That update refreshes price, image, and description
     *   and leaves purity and lab_tested alone. A later ingest that flips
     *   auto_scraped true on a row that already has the 99.00 / lab_tested
     *   pair still matches here and is cleared. A row whose purity is
     *   anything else, or whose lab_tested flag is already false, stays.
     *   Clearing the invented pair is the safe direction.
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
        return self::pairQuery()
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
     * Rows that still have the scraper pair but are left alone because
     * verified or manual_override is set. Null on either flag counts as
     * unset and is not included here.
     */
    public static function skippedCount(): int
    {
        return self::pairQuery()
            ->where(function ($query) {
                $query->where('verified', true)
                    ->orWhere('manual_override', true);
            })
            ->count();
    }

    /**
     * auto_scraped rows that still carry purity 99.00 and lab_tested.
     */
    private static function pairQuery(): Builder
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

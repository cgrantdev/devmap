<?php

use Database\Seeders\EncyclopediaHealthCanadaNovember16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Encyclopedia freshness, 16 November 2026.
 *
 *  - Health Canada public advisories RA-82930 (6 Nov 2026) and RA-82944
 *    (12 Nov 2026): one dated "Health Canada advisories (November 2026)"
 *    regulatory subsection and the advisory reference(s) on 8 entries.
 *    No seller is named. RA-82930 is cited "(not linked)" because its page
 *    links to seller websites; RA-82944 is linked.
 *
 * Guarded, all-or-nothing per entry: every target column must hold its
 * live 16 Nov 2026 value (or already the target), and the entry's sibling
 * columns must be unchanged, otherwise the entry is logged as a WARNING
 * and nothing is written. A second migrate writes nothing. down() restores
 * nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaHealthCanadaNovember16Seeder)->run();
    }

    public function down(): void
    {
        // Previous field values are not restored.
    }
};

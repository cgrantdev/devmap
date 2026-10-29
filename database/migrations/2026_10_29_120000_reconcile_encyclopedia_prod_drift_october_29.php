<?php

use Database\Seeders\EncyclopediaProdDriftReconcileOctober29Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Reconcile production encyclopedia columns that 2026_10_22 migrations
 * skipped (29 October 2026), and catch up the two columns 2026_10_29_100000
 * skipped on production because of MySQL JSON key order (bpc-157 faqs, the
 * peptide-legality-fda-ruo-compounding guide's seo_schema).
 *
 * Writes origin/main's final value only where the stored value equals a
 * known live or older value; every column logs "updated", "already
 * current" or a "guard mismatch — nothing written" WARNING through
 * App\Support\GuardedFieldWriter. A second migrate writes nothing.
 * down() does not restore the previous copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaProdDriftReconcileOctober29Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};

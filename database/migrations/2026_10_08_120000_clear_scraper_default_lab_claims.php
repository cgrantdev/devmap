<?php

use App\Support\ScraperDefaultLabClaims;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Drop invented purity / lab_tested values on auto-scraped products.
     *
     * Idempotent: ScraperDefaultLabClaims only matches the discovery-job
     * default (auto_scraped, purity 99.00, lab_tested), so a second migrate
     * updates zero rows. Does not change columns. Do not run this by hand
     * against production from a laptop; it runs with the normal migrate.
     */
    public function up(): void
    {
        ScraperDefaultLabClaims::clear();
    }

    /**
     * The previous values were the scraper default, not stored evidence.
     * Putting 99.00 / lab_tested back would republish the false claim.
     */
    public function down(): void
    {
        //
    }
};

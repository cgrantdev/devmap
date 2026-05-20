<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product flags that pick which product image represents each category
 * on the two main category-grid pages:
 *
 *   is_encyclopedia_thumb → the thumbnail shown on /encyclopedia/
 *   is_peptide_thumb      → the thumbnail shown on /products
 *
 * Each category has at most one product flagged per type (enforced by the
 * admin controller, not a DB constraint, so a brief overlap during edits
 * doesn't error). Frontend controllers prefer the flagged product when
 * resolving a category's sample image.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_encyclopedia_thumb')->default(false)->after('first_timer_deals')->index();
            $table->boolean('is_peptide_thumb')->default(false)->after('is_encyclopedia_thumb')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_encyclopedia_thumb']);
            $table->dropIndex(['is_peptide_thumb']);
            $table->dropColumn(['is_encyclopedia_thumb', 'is_peptide_thumb']);
        });
    }
};

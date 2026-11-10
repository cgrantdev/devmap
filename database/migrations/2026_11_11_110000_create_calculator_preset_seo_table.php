<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO-override rows for /calculator/{compound} pages. The 55+ presets
 * live in App\Support\CalculatorPresets as source-of-truth; this table
 * only carries the title/meta description the agency has rewritten per
 * preset. A row's `slug` matches the preset's slug exactly.
 *
 * The route reads this table and prefers the override when present;
 * otherwise falls back to the compound-generated default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_preset_seo', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_preset_seo');
    }
};

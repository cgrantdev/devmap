<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colin Sep 15 — PMAP #3. Julia asked for four new promotion shapes
 * that all STACK on top of the vendor's affiliate coupon code:
 *
 *  - nocode_sitewide  → 20% off everything, no code needed
 *                       (affiliate discount stacks: they still enter
 *                        their affiliate code at checkout for referral
 *                        credit + any extra %)
 *  - coupon_sitewide  → 20% off everything with code X (also stacks)
 *  - bogo             → buy one get one style, free-text conditions
 *  - category         → 50% off GLPs (or any product_category)
 *
 * All four are additive to the existing Limited-Time Promo (coupon
 * boost) flow — that's the vendor's baseline % which we temporarily
 * bump. These are separate marketing offers that Julia surfaces on
 * the vendor's card + storefront while they're live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();

            // nocode_sitewide | coupon_sitewide | bogo | category
            $table->string('promo_type', 32);

            // Marketing headline shown on the vendor card & storefront
            // (e.g. "Labor Day Sale — 20% Off Sitewide").
            $table->string('title', 191);
            // Short body copy for the vendor card / storefront banner.
            $table->text('description')->nullable();

            // % off (for nocode_sitewide, coupon_sitewide, category).
            // Null for bogo — that shape uses `terms` for its rules.
            $table->decimal('percent', 5, 2)->nullable();

            // Code the customer enters at checkout. Required for
            // coupon_sitewide, nullable everywhere else.
            $table->string('code', 64)->nullable();

            // Optional category scoping (category type). Nullable so
            // sitewide types leave it empty.
            $table->foreignId('product_category_id')->nullable()
                ->constrained('product_categories')->nullOnDelete();

            // Freeform terms (BOGO conditions, exclusions, notes).
            $table->text('terms')->nullable();

            // Julia's default per Colin: everything stacks with the
            // affiliate coupon. Keeping this configurable so we can
            // opt individual promos out later without a schema change.
            $table->boolean('stacks_with_affiliate')->default(true);

            // Nullable start/end. Null start = live immediately,
            // null end = runs until manually deactivated.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Manual on/off toggle so Julia can pause a promo without
            // deleting the row + losing its history.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['brand_id', 'is_active']);
            $table->index(['is_active', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_promotions');
    }
};

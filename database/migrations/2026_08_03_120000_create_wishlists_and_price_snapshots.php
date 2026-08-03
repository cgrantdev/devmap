<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // 'product' or 'category'
            $table->string('wishable_type', 16);
            // Exactly one of the two FKs is populated per row (matching wishable_type).
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
            $table->enum('alert_frequency', ['off', 'weekly'])->default('weekly');
            // Snapshot of the price the user first saw. Used to compute
            // "price change since added" on /account/wishlist and to
            // decide whether to send a drop-alert digest.
            $table->decimal('last_seen_price', 10, 2)->nullable();
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();

            // Composite index for lookups by user + type.
            $table->index(['user_id', 'wishable_type']);
            // Prevent double-adds. The nullable side of the union is included
            // in the unique tuple so (user, 'product', p1, NULL) and
            // (user, 'category', NULL, c1) coexist for the same user.
            $table->unique(['user_id', 'wishable_type', 'product_id', 'category_id'], 'wishlists_unique_row');
        });

        Schema::create('product_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->timestamp('snapshot_at')->useCurrent()->index();
            // No created_at/updated_at — snapshot_at is the only timestamp.
            $table->unique(['product_id', 'snapshot_at']);
            $table->index(['product_id', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_snapshots');
        Schema::dropIfExists('wishlists');
    }
};

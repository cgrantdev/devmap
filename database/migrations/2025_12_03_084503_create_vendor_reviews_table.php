<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Nullable for guest reviews
            $table->string('user_name')->nullable(); // For guest reviews
            $table->string('user_email')->nullable(); // For guest reviews
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->text('review')->nullable(); // Review text
            $table->boolean('is_approved')->default(false); // Admin approval
            $table->timestamps();
            
            // Index for faster queries
            $table->index('brand_id');
            $table->index('user_id');
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_reviews');
    }
};

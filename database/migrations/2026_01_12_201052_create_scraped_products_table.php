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
        if (!Schema::hasTable('scraped_products')) {
            Schema::create('scraped_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraping_config_id')->constrained('scraping_configs')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('dosage')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->string('stock_status')->nullable();
            $table->json('raw_data')->nullable(); // Store any additional scraped data
            $table->timestamp('last_scraped_at')->nullable();
            $table->boolean('manual_override')->default(false);
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('scraping_config_id');
            $table->index('last_scraped_at');
            $table->index('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_products');
    }
};

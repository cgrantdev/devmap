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
        Schema::create('scraping_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->string('vendor_name'); // Denormalized for easier access
            $table->text('products_url'); // URL to scrape products from
            $table->boolean('enabled')->default(true);
            $table->enum('frequency', ['hourly', 'daily', 'weekly'])->default('daily');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->text('last_error')->nullable();
            
            // CSS Selectors stored as JSON
            $table->json('selectors')->nullable(); // {product_container, name, price, dosage, image, link, stock}
            
            $table->timestamps();
            
            $table->index('vendor_id');
            $table->index('enabled');
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_configs');
    }
};

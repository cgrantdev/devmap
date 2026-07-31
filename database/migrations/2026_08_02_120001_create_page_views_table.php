<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('page_type', 32);                 // 'home' | 'brand' | 'product' | 'brands_index' | 'compound' | 'compare' | 'category' | 'other'
            $table->string('path', 1024);
            $table->string('route_name', 128)->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referrer', 1024)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['page_type', 'created_at']);
            $table->index(['brand_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};

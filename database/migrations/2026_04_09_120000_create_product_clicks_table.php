<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referrer', 1024)->nullable();
            $table->string('destination_url', 2048)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['brand_id', 'created_at']);
            $table->index(['is_bot', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_clicks');
    }
};

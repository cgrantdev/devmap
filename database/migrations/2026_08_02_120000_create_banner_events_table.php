<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 16);              // 'impression' | 'click'
            $table->string('slot', 64);                    // e.g. 'homepage_hero', 'homepage_vendor_cta'
            $table->string('banner_key', 128)->nullable(); // stable per-slide id (e.g. hero index or slide title-slug)
            $table->unsignedBigInteger('banner_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referrer', 1024)->nullable();
            $table->string('destination_url', 2048)->nullable();
            $table->string('page_url', 1024)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['slot', 'event_type', 'created_at']);
            $table->index(['banner_key', 'event_type', 'created_at']);
            $table->index(['brand_id', 'event_type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_events');
    }
};

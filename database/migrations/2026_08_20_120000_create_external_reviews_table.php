<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);              // 'trustpilot', 'reviews_io', 'google'
            $table->string('source_review_id', 191);   // whatever the source uses as its id
            $table->string('author', 191)->nullable();
            $table->string('author_location', 191)->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1..5
            $table->string('title', 512)->nullable();
            $table->text('body')->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->unique(['source', 'source_review_id']);
            $table->index(['brand_id', 'published_at']);
        });

        Schema::table('vendor_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_settings', 'trustpilot_url')) {
                $table->string('trustpilot_url', 512)->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_reviews');
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_settings', 'trustpilot_url')) {
                $table->dropColumn('trustpilot_url');
            }
        });
    }
};

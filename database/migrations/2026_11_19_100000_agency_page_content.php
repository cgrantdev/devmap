<?php

use App\Models\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Agency page copy (Jake, Advantage Collective, Nov 17 2026): the H1,
 * intro, body and FAQs the agency writes for vendor, coupon and
 * calculator pages, one row per page. Coupon pages had no SEO storage at
 * all, so their title and meta description live here too. Blog posts get
 * a visible FAQ list in blogs.faqs.
 *
 * Every page that reads this checks PageContent::ready() first, so the
 * site keeps rendering its defaults until this has run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_contents')) {
            Schema::create('page_contents', function (Blueprint $table) {
                $table->id();
                $table->string('page_type', 32);
                $table->string('page_key', 120);
                $table->string('title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('h1', 255)->nullable();
                $table->text('intro_html')->nullable();
                $table->longText('body_html')->nullable();
                $table->json('faqs')->nullable();
                $table->timestamps();
                $table->unique(['page_type', 'page_key']);
            });
        }

        if (Schema::hasTable('blogs') && ! Schema::hasColumn('blogs', 'faqs')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->json('faqs')->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'faqs')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropColumn('faqs');
            });
        }
        Schema::dropIfExists('page_contents');
        Cache::forget(PageContent::READY_CACHE_KEY);
    }
};

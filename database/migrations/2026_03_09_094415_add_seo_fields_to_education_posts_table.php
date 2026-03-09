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
        Schema::table('education_posts', function (Blueprint $table) {
            $table->string('seo_page_title')->nullable()->after('references');
            $table->text('seo_description')->nullable()->after('seo_page_title');
            $table->string('seo_og_title')->nullable()->after('seo_description');
            $table->text('seo_og_description')->nullable()->after('seo_og_title');
            $table->string('seo_og_image')->nullable()->after('seo_og_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropColumn([
                'seo_page_title',
                'seo_description',
                'seo_og_title',
                'seo_og_description',
                'seo_og_image',
            ]);
        });
    }
};

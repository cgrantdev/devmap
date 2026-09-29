<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blogs', 'seo_schema')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->json('seo_schema')->nullable();
            });
        }

        if (! Schema::hasColumn('educational_guides', 'content')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->longText('content')->nullable();
            });
        }

        if (! Schema::hasColumn('educational_guides', 'seo_schema')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->json('seo_schema')->nullable();
            });
        }

        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'bpc-157-vs-tb-500-evidence')->delete();
        EducationalGuide::whereIn('slug', [
            'beginners-guide-to-research-peptides',
            'peptide-legality-fda-ruo-compounding',
        ])->delete();

        // The FDA reclassification post is an in-place correction of a live row.
        // Rolling back does not restore the earlier Category 2 → Category 1 wording.

        if (Schema::hasColumn('blogs', 'seo_schema')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropColumn('seo_schema');
            });
        }
        if (Schema::hasColumn('educational_guides', 'content')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->dropColumn('content');
            });
        }
        if (Schema::hasColumn('educational_guides', 'seo_schema')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->dropColumn('seo_schema');
            });
        }
    }
};

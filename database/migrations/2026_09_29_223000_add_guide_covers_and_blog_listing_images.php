<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('educational_guides', 'cover')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->string('cover')->nullable();
            });
        }

        // Re-sync so listing cards pick up article covers. An existing blog
        // image (the live FDA Unsplash file) is preserved by the publisher.
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('image', '/images/educational/bpc-157-vs-tb-500-evidence.png')
            ->update(['image' => null]);
        Blog::where('image', '/images/educational/fda-peptide-reclassification-2026.png')
            ->update(['image' => null]);

        if (Schema::hasColumn('educational_guides', 'cover')) {
            Schema::table('educational_guides', function (Blueprint $table) {
                $table->dropColumn('cover');
            });
        }
    }
};

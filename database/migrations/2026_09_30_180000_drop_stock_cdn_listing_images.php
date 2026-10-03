<?php

use App\Content\EducationalContentPublisher;
use App\Helpers\ImageHelper;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Managed posts (FDA correction, evidence article, guides) pick up
        // site-owned covers. A previously kept Unsplash file is replaced
        // because stock CDN URLs now count as generic placeholders.
        EducationalContentPublisher::sync();

        if (! Schema::hasTable('blogs')) {
            return;
        }

        Blog::query()->orderBy('id')->each(function (Blog $blog) {
            $image = ImageHelper::isStockPlaceholder($blog->image) ? null : $blog->image;
            $og = ImageHelper::isStockPlaceholder($blog->seo_og_image) ? null : $blog->seo_og_image;
            if ($image !== $blog->image || $og !== $blog->seo_og_image) {
                $blog->image = $image;
                $blog->seo_og_image = $og;
                $blog->save();
            }
        });
    }

    public function down(): void
    {
        // Stock CDN URLs are not restored.
    }
};

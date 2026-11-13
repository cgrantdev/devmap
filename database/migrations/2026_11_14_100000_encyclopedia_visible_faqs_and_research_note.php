<?php

use App\Support\EncyclopediaSlug;
use App\Support\FaqPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encyclopedia FAQs become one visible list (Advantage Collective, Nov 2026).
 *
 * Only six allowlisted entries showed their stored FAQs. The rest sent them
 * as FAQPage JSON-LD that was not on the page (68 live entries, among them
 * tirzepatide, whose three answers contradict the rewritten entry). This:
 *
 *   1. adds education_posts.legacy_faqs and moves those schema-only FAQs
 *      there. They are kept for reference (shown read-only in the admin
 *      editor) and are neither rendered nor sent as JSON-LD;
 *   2. adds education_posts.areas_of_research_note, the optional per-entry
 *      note under Areas of Research that replaces the fixed "preclinical
 *      animal and cell studies" line.
 *
 * After this, education_posts.faqs is the visible FAQ field for every entry
 * and FAQPage JSON-LD is built from it alone. Rows are updated with the query
 * builder so updated_at, now shown as "Last updated", does not move.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('education_posts', 'legacy_faqs')) {
                $table->json('legacy_faqs')->nullable()->after('faqs');
            }
            if (! Schema::hasColumn('education_posts', 'areas_of_research_note')) {
                $table->text('areas_of_research_note')->nullable()->after('areas_of_research_intro');
            }
        });

        $categorySlugs = DB::table('product_categories')->pluck('slug', 'id');

        DB::table('education_posts')
            ->select(['id', 'product_category_id', 'faqs'])
            ->whereNotNull('faqs')
            ->orderBy('id')
            ->chunkById(100, function ($posts) use ($categorySlugs) {
                foreach ($posts as $post) {
                    $faqs = json_decode((string) $post->faqs, true);
                    if (! is_array($faqs) || $faqs === []) {
                        continue;
                    }

                    $slug = $post->product_category_id !== null
                        ? ($categorySlugs[$post->product_category_id] ?? null)
                        : null;
                    $public = $slug !== null ? (EncyclopediaSlug::publicSlug($slug) ?? $slug) : null;
                    if ($public !== null && FaqPage::renderAllowed('encyclopedia', $public)) {
                        // Already visible on the page; stays the visible list.
                        continue;
                    }

                    DB::table('education_posts')->where('id', $post->id)->update([
                        'legacy_faqs' => $post->faqs,
                        'faqs' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('education_posts', 'legacy_faqs')) {
            DB::table('education_posts')
                ->whereNotNull('legacy_faqs')
                ->whereNull('faqs')
                ->update(['faqs' => DB::raw('legacy_faqs')]);
        }

        Schema::table('education_posts', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['legacy_faqs', 'areas_of_research_note'],
                fn (string $column) => Schema::hasColumn('education_posts', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

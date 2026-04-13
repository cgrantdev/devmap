<?php

namespace App\Console\Commands;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateEncyclopediaArticle extends Command
{
    protected $signature = 'encyclopedia:generate
        {--category= : Category ID or slug to generate for}
        {--all : Generate for all categories missing articles}
        {--dry-run : Show what would be generated without saving}';

    protected $description = 'Generate or scaffold encyclopedia article content for product categories';

    public function handle(): int
    {
        if ($this->option('all')) {
            $categories = ProductCategory::where('is_active', true)
                ->where('name', 'NOT LIKE', '%/%')
                ->where('name', 'NOT LIKE', '%GLOW%')
                ->where('name', 'NOT LIKE', '%KLOW%')
                ->where('name', '!=', 'Uncategorized')
                ->where('name', '!=', 'Bacteriostatic Water')
                ->whereDoesntHave('educationPost', fn($q) => $q->where('status', 'published'))
                ->withCount('products')
                ->having('products_count', '>', 0)
                ->orderByDesc('products_count')
                ->get();
        } elseif ($this->option('category')) {
            $cat = $this->option('category');
            $category = is_numeric($cat)
                ? ProductCategory::find($cat)
                : ProductCategory::where('slug', $cat)->first();

            if (!$category) {
                $this->error("Category not found: {$cat}");
                return 1;
            }
            $categories = collect([$category]);
        } else {
            $this->error('Specify --category=<id|slug> or --all');
            return 1;
        }

        $this->info("Found {$categories->count()} categories to process.");

        foreach ($categories as $category) {
            $this->line("  → {$category->name} ({$category->slug})");

            if ($this->option('dry-run')) {
                continue;
            }

            $post = EducationPost::updateOrCreate(
                ['product_category_id' => $category->id],
                $this->buildArticleData($category)
            );

            $this->info("    ✓ Created/updated education post #{$post->id}");
        }

        $this->info('Done.');
        return 0;
    }

    private function buildArticleData(ProductCategory $category): array
    {
        $name = $category->name;
        $slug = $category->slug;

        return [
            'title' => $name,
            'slug' => $slug,
            'status' => 'draft', // Start as draft — review before publishing
            'published_at' => now(),
            'research_title' => "{$name}: A Comprehensive Research Overview",
            'research_outline' => "An in-depth analysis of {$name}, covering mechanisms of action, preclinical findings, safety considerations, and potential research applications.",
            'education_tag' => 'Research Compound',
            'peptide_full_name' => $name,
            'description' => "Research overview of {$name}. This article covers the compound's background, proposed mechanisms of action, preclinical research findings, and current regulatory status. For research use only.",
            'background' => "<!-- NEEDS CONTENT: Write 150-300 words about the discovery, origin, and classification of {$name}. Include amino acid count, molecular classification, and why researchers became interested in it. -->",
            'mechanism_of_action_intro' => "<!-- NEEDS CONTENT: Write 100-200 words introducing the proposed molecular mechanisms of {$name}. -->",
            'mechanism_subsections' => json_encode([
                [
                    'intro' => "The molecular pathways through which {$name} exerts its effects are still being elucidated in preclinical research.",
                    'points' => [
                        "<!-- Add specific mechanism point 1 -->",
                        "<!-- Add specific mechanism point 2 -->",
                    ],
                ],
            ]),
            'preclinical_intro' => "Preclinical research on {$name} has been conducted primarily in animal models and cell culture systems. The following summarizes key findings to date.",
            'preclinical_subsections' => json_encode([
                [
                    'title' => 'Key Research Findings',
                    'findings' => [
                        [
                            'title' => "Primary Research Area",
                            'description' => "<!-- NEEDS CONTENT: Describe the main preclinical findings for {$name} -->",
                        ],
                    ],
                ],
            ]),
            'preclinical_disclaimer' => "All preclinical findings are from laboratory and animal studies. Results may not translate to human outcomes. Further research, including controlled clinical trials, is needed.",
            'human_use_intro' => "<!-- NEEDS CONTENT: Describe the current state of human research for {$name}, if any. If none exists, state this clearly. -->",
            'human_use_subsections' => json_encode([
                [
                    'title' => 'Clinical Evidence Status',
                    'entries' => [
                        ['type' => 'content', 'value' => "Clinical data for {$name} remains limited. No large-scale human trials have been published as of the current date."],
                    ],
                ],
            ]),
            'regulatory_subsections' => json_encode([
                [
                    'title' => 'Regulatory Status',
                    'entries' => [
                        ['type' => 'content', 'value' => "{$name} is currently classified as a research compound. It has not been approved by the FDA, EMA, or any other regulatory body for therapeutic use in humans."],
                        ['type' => 'content', 'value' => "All products containing {$name} are sold strictly for laboratory and research purposes only (RUO)."],
                    ],
                ],
            ]),
            'regulatory_important_note' => "{$name} is an experimental research compound. It is not approved for human consumption, therapeutic use, or self-administration. Researchers should comply with all applicable local, state, and federal regulations.",
            'potential_applications_intro' => "Based on preclinical evidence, several potential research applications have been identified for {$name}. These remain theoretical and require clinical validation.",
            'potential_applications' => json_encode([
                [
                    'title' => 'Research Applications Under Investigation',
                    'description' => "<!-- NEEDS CONTENT: List 3-5 potential applications based on preclinical evidence -->",
                ],
            ]),
            'potential_applications_important_context' => "All potential applications are speculative and based on preclinical data. No therapeutic claims are made or implied.",
            'conclusion' => "<!-- NEEDS CONTENT: Write a 200-300 word balanced conclusion summarizing the current research landscape for {$name}, emphasizing that it remains an experimental compound requiring further study. -->",
            'references' => json_encode([]),
            'key_points' => json_encode([
                "{$name} is a research compound currently under preclinical investigation.",
                "Not approved for human therapeutic use by any regulatory agency.",
                "All findings are from laboratory and animal studies.",
            ]),
            'overview' => "Research overview of {$name}. This compound is currently under investigation in preclinical settings.",
            'areas_of_research_intro' => "{$name} is being studied across several research domains in preclinical models.",
            'areas_of_research' => json_encode([]),
            'key_effects' => json_encode(["Research compound — effects under investigation"]),
            'common_use_cases' => json_encode(["Laboratory research", "In-vitro studies"]),
            'how_it_works' => "The mechanism of action of {$name} is currently being investigated in preclinical research settings.",
            'half_life' => 'Under investigation',
            'bioavailability' => 'Under investigation',
            'storage' => 'Refrigerate at 2-8°C (36-46°F). Freeze for long-term storage.',
            'rating' => '0.00',
            'rating_count' => 0,
        ];
    }
}

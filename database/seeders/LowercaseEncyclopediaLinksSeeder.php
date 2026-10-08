<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;

/**
 * Lowercases encyclopedia links that 301 after the stored slug is the
 * canonical URL. Production slugs are lowercase. Does not create posts
 * and does not change ProductCategory.slug or EducationPost.slug.
 * A second run writes nothing.
 */
class LowercaseEncyclopediaLinksSeeder extends Seeder
{
    /** @var array<string, bool> */
    public array $report = [
        'updated' => false,
        'updated_posts' => 0,
    ];

    public function run(): void
    {
        EducationPost::query()->orderBy('id')->each(function (EducationPost $post): void {
            $changed = false;
            foreach ($this->fields() as $field) {
                $value = $post->{$field};
                $next = $this->rewrite($value);
                if ($next !== $value) {
                    $post->{$field} = $next;
                    $changed = true;
                }
            }
            if (! $changed) {
                return;
            }
            $post->save();
            $this->report['updated'] = true;
            $this->report['updated_posts']++;
        });
    }

    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return [
            'description',
            'overview',
            'background',
            'conclusion',
            'human_use_intro',
            'regulatory_important_note',
            'potential_applications_intro',
            'potential_applications_important_context',
            'seo_description',
            'faqs',
            'key_points',
            'human_use_subsections',
            'regulatory_subsections',
            'references',
            'areas_of_research',
            'potential_applications',
        ];
    }

    private function rewrite(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace(
                [
                    '/encyclopedia/MOTS-c',
                    '/encyclopedia/Elamipretide',
                    '/encyclopedia/Retatrutide',
                    '/encyclopedia/Tirzepatide',
                    '/encyclopedia/Orforglipron',
                    '/encyclopedia/Cagrilintide',
                ],
                [
                    '/encyclopedia/mots-c',
                    '/encyclopedia/elamipretide',
                    '/encyclopedia/retatrutide',
                    '/encyclopedia/tirzepatide',
                    '/encyclopedia/orforglipron',
                    '/encyclopedia/cagrilintide',
                ],
                $value
            );

            return preg_replace('#/encyclopedia/NAD(?![A-Za-z0-9+])#', '/encyclopedia/nad', $value) ?? $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->rewrite($item);
        }

        return $value;
    }
}

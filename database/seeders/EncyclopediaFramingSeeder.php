<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaFraming;
use App\Support\FramingProfile;
use Illuminate\Database\Seeder;

/**
 * Publishes corrected encyclopedia framing for compounds whose live stubs
 * mislabel the chemical class. Safe to re-run. KPV is not in the registry
 * and is left untouched. NAD+ is phrase-patched rather than replaced.
 */
class EncyclopediaFramingSeeder extends Seeder
{
    public function run(): void
    {
        ProductCategory::query()->orderBy('id')->each(function (ProductCategory $category) {
            $profile = EncyclopediaFraming::match($category->slug, $category->name);
            if (!$profile) {
                return;
            }
            $this->syncCategory($category, $profile);
            $this->syncPost($category, $profile);
        });
    }

    public function rollback(): void
    {
        ProductCategory::query()->orderBy('id')->each(function (ProductCategory $category) {
            $profile = EncyclopediaFraming::match($category->slug, $category->name);
            if (!$profile) {
                return;
            }
            if ($profile->preservesExistingArticle()) {
                $this->reversePhrases($category, $profile);

                return;
            }
            EducationPost::query()
                ->where('product_category_id', $category->id)
                ->where('seo_page_title', $profile->seoTitle())
                ->delete();
        });
    }

    private function syncCategory(ProductCategory $category, FramingProfile $profile): void
    {
        $description = strtolower(trim((string) $category->description));
        $replace = $description === ''
            || str_contains($description, 'peptide encyclopedia')
            || str_contains($description, ' peptides.')
            || ($profile->rejectsPeptideIdentity() && $this->framesAsPeptide($description));

        if ($replace) {
            $category->description = $profile->cardDescription();
            $category->save();
        }
    }

    private function syncPost(ProductCategory $category, FramingProfile $profile): void
    {
        $post = EducationPost::query()->where('product_category_id', $category->id)->first();

        if ($profile->preservesExistingArticle() && $post) {
            $this->patchPreserved($post, $profile);

            return;
        }

        if ($post && !$this->needsWrite($post, $profile)) {
            return;
        }

        $attributes = array_merge($profile->articleAttributes(), [
            'product_category_id' => $category->id,
            'slug' => $post?->slug ?: $category->slug,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'published_at' => $post?->published_at ?? now(),
        ]);

        if ($post) {
            $post->fill($attributes);
            $post->save();

            return;
        }

        EducationPost::create($attributes);
    }

    private function patchPreserved(EducationPost $post, FramingProfile $profile): void
    {
        $map = $profile->phraseReplacements();
        foreach (['overview', 'conclusion', 'background', 'description', 'peptide_full_name'] as $field) {
            if (is_string($post->{$field}) && $map !== []) {
                $post->{$field} = strtr($post->{$field}, $map);
            }
        }
        if (is_array($post->faqs) && $map !== []) {
            $faqs = $post->faqs;
            foreach ($faqs as &$faq) {
                if (isset($faq['answer']) && is_string($faq['answer'])) {
                    $faq['answer'] = strtr($faq['answer'], $map);
                }
            }
            unset($faq);
            $post->faqs = $faqs;
        }
        if ($this->isStub((string) $post->seo_page_title)) {
            $post->seo_page_title = $profile->seoTitle();
            $post->seo_og_title = $profile->seoTitle();
        }
        if ($this->isStub((string) $post->seo_description) || str_contains(strtolower((string) $post->seo_description), 'peptide')) {
            $post->seo_description = $profile->seoDescription();
            $post->seo_og_description = $profile->seoDescription();
        }
        $post->save();
    }

    private function reversePhrases(ProductCategory $category, FramingProfile $profile): void
    {
        $post = EducationPost::query()->where('product_category_id', $category->id)->first();
        if (!$post) {
            return;
        }
        $reverse = array_flip($profile->phraseReplacements());
        foreach (['overview', 'conclusion', 'background', 'description'] as $field) {
            if (is_string($post->{$field})) {
                $post->{$field} = strtr($post->{$field}, $reverse);
            }
        }
        if ($post->seo_page_title === $profile->seoTitle()) {
            $post->seo_page_title = null;
            $post->seo_og_title = null;
        }
        if ($post->seo_description === $profile->seoDescription()) {
            $post->seo_description = null;
            $post->seo_og_description = null;
        }
        $post->save();
    }

    private function needsWrite(EducationPost $post, FramingProfile $profile): bool
    {
        if ($post->status !== 'published' || $post->show_in_encyclopedia === false) {
            return true;
        }
        if (trim((string) $post->overview) === '') {
            return true;
        }

        $blob = strtolower(implode("\n", array_filter([
            $post->title,
            $post->peptide_full_name,
            $post->description,
            $post->overview,
            $post->background,
            $post->conclusion,
            $post->seo_page_title,
            $post->seo_description,
            is_array($post->faqs) ? json_encode($post->faqs) : '',
        ])));

        if ($this->isStub($blob)) {
            return true;
        }
        if ($profile->rejectsPeptideIdentity() && $this->framesAsPeptide($blob)) {
            return true;
        }
        foreach ($profile->requiredMarkers() as $marker) {
            if (!str_contains($blob, strtolower($marker))) {
                return true;
            }
        }

        return false;
    }

    private function isStub(string $text): bool
    {
        $text = strtolower($text);

        return str_contains($text, 'peptide encyclopedia') || str_contains($text, ' peptides.');
    }

    private function framesAsPeptide(string $text): bool
    {
        if (!preg_match('/\bpeptides?\b/', $text)) {
            return false;
        }

        foreach (['not a peptide', 'not one peptide', 'not a short peptide'] as $negative) {
            if (str_contains($text, $negative)) {
                return false;
            }
        }

        return true;
    }
}

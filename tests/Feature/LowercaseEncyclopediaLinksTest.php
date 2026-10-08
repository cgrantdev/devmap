<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\LowercaseEncyclopediaLinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowercaseEncyclopediaLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_encyclopedia_links_lowercase_once(): void
    {
        $category = ProductCategory::create([
            'name' => 'Epitalon',
            'slug' => 'epitalon',
            'is_active' => true,
        ]);
        $post = EducationPost::create([
            'title' => 'Epitalon',
            'slug' => 'epitalon',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'conclusion' => 'See the live [MOTS-c](https://peptidemap.com/encyclopedia/MOTS-c) entry.',
            'overview' => 'Internal notes point at /encyclopedia/NAD and leave /encyclopedia/NAD+ alone.',
            'references' => [[
                'url' => 'https://peptidemap.com/encyclopedia/Elamipretide',
            ]],
        ]);

        $seeder = new LowercaseEncyclopediaLinksSeeder;
        $seeder->run();
        $this->assertTrue($seeder->report['updated']);
        $this->assertSame(1, $seeder->report['updated_posts']);

        $post->refresh();
        $this->assertSame('epitalon', $post->slug);
        $this->assertSame('epitalon', $category->fresh()->slug);
        $this->assertStringContainsString('/encyclopedia/mots-c', $post->conclusion);
        $this->assertStringNotContainsString('/encyclopedia/MOTS-c', $post->conclusion);
        $this->assertStringContainsString('/encyclopedia/nad', $post->overview);
        $this->assertStringContainsString('/encyclopedia/NAD+', $post->overview);
        $this->assertSame('https://peptidemap.com/encyclopedia/elamipretide', $post->references[0]['url']);

        EducationPost::query()->where('id', $post->id)->update(['updated_at' => '2020-01-01 00:00:00']);
        $again = new LowercaseEncyclopediaLinksSeeder;
        $again->run();
        $this->assertFalse($again->report['updated']);
        $this->assertSame(0, $again->report['updated_posts']);
        $this->assertSame('2020-01-01 00:00:00', $post->fresh()->updated_at->format('Y-m-d H:i:s'));
    }
}

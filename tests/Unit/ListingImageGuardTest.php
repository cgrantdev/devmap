<?php

namespace Tests\Unit;

use App\Content\ListingImageGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ListingImageGuardTest extends TestCase
{
    public function test_empty_blog_image_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('blogs.image');

        ListingImageGuard::assertPresent('blog', 'bpc-157-vs-tb-500-evidence', null);
    }

    public function test_empty_guide_cover_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cover');

        ListingImageGuard::assertPresent('guide', 'beginners-guide-to-research-peptides', '  ');
    }
}

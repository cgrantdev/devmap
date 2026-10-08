<?php

namespace App\Support;

/**
 * Bump when generated OG blade copy changes. Appended to PNG cache keys
 * and to public ?v= URLs so stored images and downstream scrapers refetch.
 */
class OgImageRevision
{
    public const COPY = '20261008';
}

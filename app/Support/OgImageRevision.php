<?php

namespace App\Support;

/**
 * Bump when generated OG blade copy changes. Appended to PNG cache keys
 * and to public ?v= URLs so stored images and downstream scrapers refetch.
 */
class OgImageRevision
{
    public const COPY = '20261022';

    /**
     * Product-card copy only. Compound and brand cards stay on COPY so
     * a product-card wording change does not refetch those images.
     */
    public const PRODUCT = '20261014';
}

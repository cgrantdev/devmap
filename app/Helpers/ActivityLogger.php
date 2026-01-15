<?php

namespace App\Helpers;

use App\Models\Activity;

class ActivityLogger
{
    /**
     * Log vendor registration
     */
    public static function vendorRegistered(string $vendorName, int $vendorId): void
    {
        Activity::log(
            'vendor_registered',
            'New vendor registered',
            $vendorName,
            'vendor',
            $vendorId
        );
    }

    /**
     * Log product addition
     */
    public static function productAdded(string $productName, ?string $vendorName = null, ?int $productId = null): void
    {
        $entityName = $vendorName ?: 'Unknown Vendor';
        Activity::log(
            'product_added',
            'Product added',
            $entityName,
            'product',
            $productId
        );
    }

    /**
     * Log review submission
     */
    public static function reviewSubmitted(string $vendorName, ?int $reviewId = null): void
    {
        Activity::log(
            'review_submitted',
            'Review submitted',
            $vendorName,
            'review',
            $reviewId
        );
    }

    /**
     * Log deal creation
     */
    public static function dealCreated(string $vendorName, ?int $dealId = null): void
    {
        Activity::log(
            'deal_created',
            'Deal created',
            $vendorName,
            'deal',
            $dealId
        );
    }

    /**
     * Log banner activation
     */
    public static function bannerActivated(string $vendorName, ?int $bannerId = null): void
    {
        Activity::log(
            'banner_activated',
            'Banner ad activated',
            $vendorName,
            'banner',
            $bannerId
        );
    }
}

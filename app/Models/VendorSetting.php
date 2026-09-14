<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VendorSetting extends Model
{
    use HasFactory;

    // Any create/update/delete that could change which countries have
    // vendors invalidates the header CountrySelector's cache. Key matches
    // HandleInertiaRequests::share().
    protected static function booted(): void
    {
        $bust = fn () => Cache::forget('site_locations_v2');
        static::saved($bust);
        static::deleted($bust);
    }

    protected $fillable = [
        'brand_id',
        'location_id',
        'banner',
        'logo',
        'description',
        'tagline',
        'contact_email',
        'phone_number',
        'status',
        'approval_status',
        'api_route',
        'shop_url',
        'website',
        'founded_year',
        'coupon_code',
        'coupon_discount_percent',
        'coupon_discount_previous_percent',
        'coupon_boost_expires_at',
        'coupon_boost_starts_at',
        'coupon_boost_percent',
        'referral_url',
        'trustpilot_url',
        'google_reviews_url',
        'reviews_io_url',
        'pepreviewpro_url',
        'external_rating_avg',
        'external_rating_count',
        'external_ratings_json',
        'usps',
        'why_choose_bullets',
        'business_hours_json',
        'shipping_info',
        'return_policy',
        'business_hours',
        'banner_image_url',
        'top_vendor',
        'featured',
        'is_partner',
        'payment_methods',
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
        'api_platform',
        'api_key',
        'affiliate_platform',
        'affiliate_credentials',
        'affiliate_stats_json',
        'affiliate_stats_updated_at',
        'commission_rate_pct',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'top_vendor' => 'boolean',
        'featured' => 'boolean',
        'is_partner' => 'boolean',
        'status' => 'integer',
        'payment_methods' => 'array',
        'api_key' => 'encrypted',
        'external_ratings_json' => 'array',
        'usps' => 'array',
        'why_choose_bullets' => 'array',
        'business_hours_json' => 'array',
        'coupon_boost_expires_at' => 'datetime',
        'coupon_boost_starts_at' => 'datetime',
        'coupon_boost_percent' => 'float',
        'external_rating_avg' => 'float',
        'affiliate_credentials' => 'encrypted',
        'affiliate_stats_json' => 'array',
        'affiliate_stats_updated_at' => 'datetime',
        'commission_rate_pct' => 'float',
    ];

    /**
     * True while a temporary coupon boost is currently running —
     * starts_at (if set) has passed and expires_at is still in the
     * future. A boost whose starts_at is still ahead reads as
     * "scheduled" (couponBoostScheduled) rather than active.
     */
    public function couponBoostActive(): bool
    {
        if (!$this->coupon_boost_expires_at || $this->coupon_boost_expires_at->isPast()) {
            return false;
        }
        if ($this->coupon_discount_previous_percent === null) {
            return false;
        }
        if ($this->coupon_boost_starts_at && $this->coupon_boost_starts_at->isFuture()) {
            return false;
        }
        return true;
    }

    /**
     * True when a boost is scheduled for the future — Julia set it up
     * ahead of a launch and the start time hasn't hit yet. The nightly
     * ActivateScheduledCouponBoosts command flips these to active.
     */
    public function couponBoostScheduled(): bool
    {
        return $this->coupon_boost_percent !== null
            && $this->coupon_boost_starts_at
            && $this->coupon_boost_starts_at->isFuture()
            && $this->coupon_boost_expires_at
            && $this->coupon_boost_expires_at->isFuture();
    }

    /**
     * Apply a temporary coupon boost. If $startsAt is null or in the
     * past, the discount swap happens immediately and a Discord
     * announcement fires. Otherwise the boost is stored as "scheduled"
     * (coupon_boost_percent + coupon_boost_starts_at + expires_at) and
     * ActivateScheduledCouponBoosts promotes it when the start time
     * passes. Either way RevertExpiredCouponBoosts handles the end.
     *
     * Idempotent: re-applying while a boost is active or scheduled
     * updates the new percentage + timing without losing the original
     * previous_percent snapshot.
     */
    public function applyCouponBoost(float $newPercent, ?\DateTimeInterface $startsAt, \DateTimeInterface $expiresAt): void
    {
        $startsImmediately = !$startsAt || $startsAt <= new \DateTimeImmutable();
        $isNewBoost = !$this->couponBoostActive() && !$this->couponBoostScheduled();

        if ($isNewBoost) {
            // Snapshot current standard % — this is where we revert to.
            $this->coupon_discount_previous_percent = $this->coupon_discount_percent;
        }

        $this->coupon_boost_percent = $newPercent;
        $this->coupon_boost_starts_at = $startsImmediately ? null : $startsAt;
        $this->coupon_boost_expires_at = $expiresAt;

        if ($startsImmediately) {
            // Live now — swap the discount % so vendor cards display
            // the boosted rate immediately.
            $this->coupon_discount_percent = $newPercent;
        }
        $this->save();

        if ($isNewBoost && $startsImmediately) {
            $this->postDiscordBoostStart($newPercent, $expiresAt);
        }
    }

    private function postDiscordBoostStart(float $newPct, \DateTimeInterface $expiresAt): void
    {
        $token = config('services.discord.bot_token');
        $channel = config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        $brandName = $this->brand?->name ?? 'A vendor';
        $slug = $this->brand?->slug;
        $link = $slug ? "https://peptidemap.com/brand/{$slug}" : 'https://peptidemap.com/deals';
        $until = \Carbon\Carbon::parse($expiresAt)->format('M j g:i A T');

        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bot ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                'content' => "🔥 **{$brandName}** is running a limited-time **{$newPct}% off** promo until {$until}. → {$link}",
            ]);
        } catch (\Throwable $e) {
            \Log::warning('coupon boost start Discord post failed', ['err' => $e->getMessage()]);
        }
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Countries a vendor ships to (may include or extend beyond their
     * physical HQ location). Used by the /brands location filter and
     * the storefront "Ships to" chip row. Backfilled from location_id
     * on migration so no vendor loses filter hits.
     */
    public function shipsToLocations()
    {
        return $this->belongsToMany(
            Location::class,
            'vendor_ships_to_locations',
            'vendor_setting_id',
            'location_id'
        )->withTimestamps();
    }
}

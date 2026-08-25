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
        $bust = fn () => Cache::forget('site_locations_v1');
        static::saved($bust);
        static::deleted($bust);
    }

    protected $fillable = [
        'brand_id',
        'location_id',
        'banner',
        'logo',
        'description',
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
        'referral_url',
        'trustpilot_url',
        'google_reviews_url',
        'reviews_io_url',
        'pepreviewpro_url',
        'external_rating_avg',
        'external_rating_count',
        'external_ratings_json',
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
        'coupon_boost_expires_at' => 'datetime',
        'external_rating_avg' => 'float',
    ];

    /**
     * True while a temporary coupon boost is active. Used by the auto-revert
     * scheduler + Discord post to detect state transitions.
     */
    public function couponBoostActive(): bool
    {
        return $this->coupon_boost_expires_at
            && $this->coupon_boost_expires_at->isFuture()
            && $this->coupon_discount_previous_percent !== null;
    }

    /**
     * Apply a temporary coupon boost. Snapshots the current % as
     * previous_percent so RevertExpiredCouponBoosts can put it back.
     * Idempotent: re-applying while a boost is active updates the new
     * percentage + expiry without losing the original previous_percent.
     * Also posts to Discord when a NEW boost begins (not on re-apply).
     */
    public function applyCouponBoost(float $newPercent, \DateTimeInterface $expiresAt): void
    {
        $isNewBoost = !$this->couponBoostActive();

        if ($isNewBoost) {
            // Snapshot current standard % — this is where we revert to.
            $this->coupon_discount_previous_percent = $this->coupon_discount_percent;
        }
        $this->coupon_discount_percent = $newPercent;
        $this->coupon_boost_expires_at = $expiresAt;
        $this->save();

        if ($isNewBoost) {
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
}

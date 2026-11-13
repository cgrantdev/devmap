<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
        'feed_url',
        'feed_last_synced_at',
        'website',
        'founded_year',
        'coupon_code',
        'coupon_discount_percent',
        'coupon_discount_previous_percent',
        'coupon_boost_expires_at',
        'coupon_boost_starts_at',
        'coupon_boost_percent',
        'coupon_boost_code',
        'coupon_code_previous',
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
        'manufacturing_notes',
        'independent_testing_notes',
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
        'feed_last_synced_at' => 'datetime',
        'coupon_boost_percent' => 'float',
        'external_rating_avg' => 'float',
        'affiliate_credentials' => 'encrypted',
        'affiliate_stats_json' => 'array',
        'affiliate_stats_updated_at' => 'datetime',
        'commission_rate_pct' => 'float',
    ];

    /**
     * Secrets and integration material. None of these are storefront
     * copy. connection_token is a brands column; it is listed here so a
     * value set on this model still cannot be serialized.
     *
     * api_key and affiliate_credentials are encrypted at rest. feed_url
     * and api_route are ingestion endpoints and can carry query tokens.
     * affiliate_stats_json and commission_rate_pct are commercial terms,
     * not a public price.
     *
     * Admin and vendor code reads these in PHP. makeVisible() is for the
     * feed URL on the admin edit form only. The API key and affiliate
     * credentials stay hidden (write-only). Do not makeVisible() on a
     * model that is about to be rendered to a public page.
     */
    public const SECRET_COLUMNS = [
        'connection_token',
        'api_key',
        'api_route',
        'feed_url',
        'affiliate_credentials',
        'affiliate_stats_json',
        'commission_rate_pct',
    ];

    /**
     * Columns a public response may serialize. Relation keys (id,
     * brand_id, location_id) are required for eager loads. Everything
     * else is rendered on a storefront, coupon, deal, or brand page,
     * or is the public meta for that page.
     */
    public const PUBLIC_COLUMNS = [
        'id',
        'brand_id',
        'location_id',
        'banner',
        'logo',
        'banner_image_url',
        'description',
        'tagline',
        'contact_email',
        'phone_number',
        'status',
        'approval_status',
        'shop_url',
        'website',
        'founded_year',
        'coupon_code',
        'coupon_discount_percent',
        'coupon_boost_expires_at',
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
        'business_hours',
        'business_hours_json',
        'shipping_info',
        'return_policy',
        'manufacturing_notes',
        'independent_testing_notes',
        'payment_methods',
        'top_vendor',
        'featured',
        'is_partner',
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
        'updated_at',
    ];

    /**
     * Product grids serialize the whole product, including this relation.
     * Cards only need the coupon fields behind brand_coupon_code and
     * brand_discount_percent. id and brand_id hydrate the eager load.
     */
    public const CARD_COLUMNS = [
        'id',
        'brand_id',
        // Needed for ProductRowCurrency so a non-US vendor's product
        // cards render in the vendor's own currency symbol instead of
        // defaulting to '$'. Eager loads of brand.vendorSetting.location
        // join on this column — omitting it leaves Location null and
        // every price falls back to USD.
        'location_id',
        'coupon_code',
        'coupon_discount_percent',
    ];

    /**
     * Read by couponBoostActive() so a live boost still flags on the
     * homepage and deals page (the end time is in PUBLIC_COLUMNS). Not a
     * storefront field, so it is not part of PUBLIC_COLUMNS. Load it only
     * for queries that map the result into a boolean and do not
     * serialize the model.
     */
    public const BOOST_STATE_COLUMNS = [
        'coupon_boost_starts_at',
    ];

    protected $hidden = self::SECRET_COLUMNS;

    public function scopeForPublic($query, bool $withBoostState = false)
    {
        $columns = self::PUBLIC_COLUMNS;
        if ($withBoostState) {
            $columns = array_merge($columns, self::BOOST_STATE_COLUMNS);
        }

        return $query->select($columns);
    }

    public function scopeForCard($query)
    {
        return $query->select(self::CARD_COLUMNS);
    }

    /**
     * True while a temporary coupon boost is currently running —
     * starts_at (if set) has passed and expires_at is still in the
     * future. A boost whose starts_at is still ahead reads as
     * "scheduled" (couponBoostScheduled) rather than active.
     */
    public function couponBoostActive(): bool
    {
        // forPublic() without the boost state leaves the start time
        // unloaded, and a scheduled boost would then read as live.
        if (! array_key_exists('coupon_boost_starts_at', $this->getAttributes())) {
            return false;
        }
        // This used to also require a saved previous %. A vendor with no
        // discount of its own has none, so its boosts never counted as
        // live and never reverted (Certified Pep, WEEKEND35, Nov 2026).
        if (! $this->coupon_boost_expires_at || $this->coupon_boost_expires_at->isPast()) {
            return false;
        }
        if ($this->coupon_boost_starts_at && $this->coupon_boost_starts_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * A boost has been launched or scheduled and not yet reverted or
     * cancelled. Every boost has an end time, and ending one clears it.
     */
    public function couponBoostInFlight(): bool
    {
        return $this->coupon_boost_expires_at !== null;
    }

    /**
     * The boost's % (and its code, if it has one) is the live coupon. A
     * scheduled boost keeps its start time until it is swapped in.
     */
    private function couponBoostSwappedIn(): bool
    {
        return $this->couponBoostInFlight() && $this->coupon_boost_starts_at === null;
    }

    /**
     * The vendor's own coupon as [percent, code]: what shows whenever no
     * boost is live. Either can be null, and a vendor with no coupon of
     * its own goes back to having none.
     *
     * While a boost is live the previous_* columns hold it. Before Nov 14
     * a null there meant "nothing to restore", so a vendor without a code
     * kept the boost's code as its own after the boost ended.
     */
    public function standardCoupon(): array
    {
        if (! $this->couponBoostSwappedIn()) {
            return [$this->coupon_discount_percent, $this->coupon_code];
        }

        return [
            $this->coupon_discount_previous_percent,
            $this->coupon_boost_code !== null ? $this->coupon_code_previous : $this->coupon_code,
        ];
    }

    /**
     * Make the boost's % and code the live coupon, saving the vendor's
     * own coupon for when the boost ends. Runs when a boost launches and
     * when a scheduled one starts (ActivateScheduledCouponBoosts). Does
     * not save.
     */
    public function swapInCouponBoost(): void
    {
        [$percent, $code] = $this->standardCoupon();

        $this->coupon_discount_previous_percent = $percent;
        $this->coupon_code_previous = $this->coupon_boost_code !== null ? $code : null;
        $this->coupon_discount_percent = $this->coupon_boost_percent;
        if ($this->coupon_boost_code !== null) {
            $this->coupon_code = $this->coupon_boost_code;
        }
        $this->coupon_boost_starts_at = null;
    }

    /**
     * End the boost and put the vendor's own coupon back. A boost that
     * never started only loses its schedule. Does not save.
     *
     * With $keepHandEdits, a live % or code that no longer matches the
     * boost stays as it is, because someone set it by hand after the
     * boost went live. The revert job uses this for boosts that ended
     * more than a day ago: boosts from before the Nov 14 fix that never
     * reverted.
     */
    public function endCouponBoost(bool $keepHandEdits = false): void
    {
        if ($this->couponBoostSwappedIn()) {
            [$percent, $code] = $this->standardCoupon();

            $percentIsTheBoosts = $this->coupon_boost_percent !== null
                && $this->coupon_discount_percent !== null
                && abs((float) $this->coupon_discount_percent - (float) $this->coupon_boost_percent) < 0.01;
            $codeIsTheBoosts = $this->coupon_boost_code !== null
                && strcasecmp(trim((string) $this->coupon_code), trim($this->coupon_boost_code)) === 0;

            if (! $keepHandEdits || $percentIsTheBoosts) {
                $this->coupon_discount_percent = $percent;
            }
            if ($this->coupon_boost_code !== null && (! $keepHandEdits || $codeIsTheBoosts)) {
                $this->coupon_code = $code;
            }
        }

        $this->coupon_discount_previous_percent = null;
        $this->coupon_code_previous = null;
        $this->coupon_boost_percent = null;
        $this->coupon_boost_code = null;
        $this->coupon_boost_starts_at = null;
        $this->coupon_boost_expires_at = null;
    }

    /**
     * End a boost whose end time has passed. False when there is none.
     * Does not save.
     */
    public function revertExpiredCouponBoost(): bool
    {
        if (! $this->couponBoostInFlight() || $this->coupon_boost_expires_at->isFuture()) {
            return false;
        }

        $this->endCouponBoost(keepHandEdits: $this->coupon_boost_expires_at->lt(now()->subDay()));

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
     * past, the discount swap happens immediately (and a Discord post
     * fires if DISCORD_PROMOTION_POSTS is on). Otherwise the boost is stored as "scheduled"
     * (coupon_boost_percent + coupon_boost_starts_at + expires_at) and
     * ActivateScheduledCouponBoosts promotes it when the start time
     * passes. Either way RevertExpiredCouponBoosts handles the end.
     *
     * Re-applying while a boost is running or scheduled replaces it and
     * keeps the vendor's own coupon as the one to restore.
     */
    public function applyCouponBoost(float $newPercent, ?\DateTimeInterface $startsAt, \DateTimeInterface $expiresAt, ?string $boostCode = null): void
    {
        $boostCode = $boostCode !== null && trim($boostCode) !== '' ? trim($boostCode) : null;
        $startsImmediately = ! $startsAt || $startsAt <= now();

        // A boost that has ended but the five-minute job has not reverted.
        $this->revertExpiredCouponBoost();
        $isNewBoost = ! $this->couponBoostInFlight();

        // The vendor's own coupon shows until the new boost starts.
        [$percent, $code] = $this->standardCoupon();
        $this->coupon_discount_percent = $percent;
        $this->coupon_code = $code;
        // Read by the admin panel and dashboard while the boost is
        // scheduled; swapInCouponBoost() sets them again at the start.
        $this->coupon_discount_previous_percent = $percent;
        $this->coupon_code_previous = $boostCode !== null ? $code : null;

        $this->coupon_boost_percent = $newPercent;
        $this->coupon_boost_code = $boostCode;
        // Stored as UTC. The admin form sends ET times, and a datetime
        // cast saves a zoned time's clock reading without converting it,
        // so "11:59 PM" was read back as 11:59 PM UTC and boosts ended
        // four or five hours early.
        $this->coupon_boost_starts_at = $startsImmediately ? null : Carbon::instance($startsAt)->utc();
        $this->coupon_boost_expires_at = Carbon::instance($expiresAt)->utc();

        if ($startsImmediately) {
            $this->swapInCouponBoost();
        }
        $this->save();

        if ($isNewBoost && $startsImmediately) {
            $this->postDiscordBoostStart($newPercent, $expiresAt);
        }
    }

    private function postDiscordBoostStart(float $newPct, \DateTimeInterface $expiresAt): void
    {
        if (! config('services.discord.promotion_posts')) {
            return;
        }
        $token = config('services.discord.bot_token');
        // Colin Sep 23 — boost-live posts land in the deals channel
        // (consumer-facing) instead of the admin growth channel.
        // Falls back to growth if deals channel isn't configured yet.
        $channel = config('services.discord.deals_channel_id')
            ?: config('services.discord.growth_channel_id');
        if (!$token || !$channel) return;

        // Colin Sep 30 — dedup within 6h so Julia editing/re-applying
        // a boost doesn't chain-fire the same "X% off through DATE"
        // post repeatedly. Also blocks noisy retries.
        $dedupKey = 'discord.boost-start.' . ($this->brand_id ?? 'x') . '.' . (int) $newPct;
        if (\Illuminate\Support\Facades\Cache::has($dedupKey)) return;
        \Illuminate\Support\Facades\Cache::put($dedupKey, 1, now()->addHours(6));

        $brandName = $this->brand?->name ?? 'A vendor';
        $slug = $this->brand?->slug;
        $link = $slug ? "https://peptidemap.com/brand/{$slug}" : 'https://peptidemap.com/deals';
        $until = Carbon::instance($expiresAt)->setTimezone('America/New_York')->format('M j');

        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bot ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/v10/channels/{$channel}/messages", [
                // Colin Sep 21 — softened tone. Was "🔥 X is running
                // a limited-time N% off promo until DATE." Now a plain
                // one-liner. Suppresses the link preview embed too
                // (flag 4 = SUPPRESS_EMBEDS) so the growth channel
                // doesn't wall of card-preview noise.
                'content' => "{$brandName} — {$newPct}% off through {$until}. <{$link}>",
                'flags' => 4,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('coupon boost start Discord post failed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Public URL for the vendor logo, or null when no logo is stored or the
     * file is missing on disk. Callers render an initials fallback on null;
     * never substitute an external placeholder service.
     */
    public function publicLogoUrl(): ?string
    {
        return \App\Helpers\ImageHelper::resolveVendorLogo($this->logo);
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

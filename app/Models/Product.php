<?php

namespace App\Models;

use App\Models\Scopes\DemoScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new DemoScope());
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'second_price',
        'brand_id',
        'location_id',
        'product_category_id',
        'product_type',
        'size_mg',
        'purity',
        'availability',
        'status',
        'hidden',
        'is_demo',
        'featured',
        'lab_tested',
        'first_timer_deals',
        'auto_update',
        'verified',
        'rating_average',
        'rating_count',
        'image_url',
        'product_url',
        'auto_scraped',
        'last_scraped_at',
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'product_types');
    }

    public function puses()
    {
        return $this->belongsToMany(Puse::class, 'product_uses');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function clicks()
    {
        return $this->hasMany(ProductClick::class);
    }

    protected $casts = [
        'last_scraped_at' => 'datetime',
        'hidden' => 'boolean',
        'is_demo' => 'boolean',
        'featured' => 'boolean',
        'lab_tested' => 'boolean',
        'first_timer_deals' => 'boolean',
        'auto_update' => 'boolean',
    ];

    // Always include the derived display_name when the model is serialized
    // (toArray, toJson, Inertia payloads, etc.) so the frontend gets it
    // alongside the original `name`.
    protected $appends = ['display_name', 'brand_discount_percent'];

    public function scopeVisible($query)
    {
        return $query->where('hidden', false);
    }

    /**
     * Get the decoded product name (HTML entities decoded)
     */
    public function getNameAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get the original price (before discount)
     * When discount_price exists, price is the original and discount_price is the sale price
     */
    public function getOriginalPriceAttribute()
    {
        if ($this->discount_price && $this->discount_price < $this->price) {
            return $this->price; // price is original, discount_price is sale
        }
        return null;
    }

    /**
     * Derived display name based on the curated category + type + size.
     *
     * Format:
     *   {Category Name} ({size})                 — Peptide (default) or unset
     *   {Category Name} ({size}) | Capsule       — Capsule type
     *   {Category Name} ({size}) | Nasal Spray   — Nasal Spray type
     *
     * Falls back to the imported `name` if category or size is missing,
     * so half-triaged rows don't render as nameless garbage.
     *
     * Examples:
     *   DSIP + 5mg + Peptide       → "DSIP (5mg)"
     *   BPC-157 + 10mg + Peptide   → "BPC-157 (10mg)"
     *   Semax + 10mg + Nasal Spray → "Semax (10mg) | Nasal Spray"
     *   NAD+ + 500mg + Capsule     → "NAD+ (500mg) | Capsule"
     */
    public function getDisplayNameAttribute(): string
    {
        $category = $this->relationLoaded('category') ? $this->category : $this->category()->first();
        $categoryName = $category ? trim($category->name) : null;
        $size = $this->size_mg ? trim((string) $this->size_mg) : null;

        // Fall back to the imported name if we don't have enough to build a
        // clean display name. Better to show the raw import than something
        // like "(5mg)" with no peptide name.
        if (!$categoryName || !$size) {
            return $this->name ?? '';
        }

        // Normalize size: if it's a bare number, append "mg"
        if (preg_match('/^\d+(\.\d+)?$/', $size)) {
            $size .= 'mg';
        }

        // Always just "{Category} ({size})". When the product type matters
        // (Capsule / Nasal Spray), the frontend renders a colored chip next
        // to the name rather than appending it to the string.
        return "{$categoryName} ({$size})";
    }

    /**
     * The vendor's PeptideMap discount percentage, pulled through the brand's
     * vendor_settings.coupon_discount_percent. Surfaced on every serialized
     * product so cards / listing pages can compute the "PeptideMap Price"
     * without an extra controller round-trip.
     *
     * Returns null when the brand has no discount configured.
     * Requires brand.vendorSetting to be eager-loaded; otherwise returns null
     * rather than firing an N+1 query.
     */
    public function getBrandDiscountPercentAttribute(): ?float
    {
        if (!$this->relationLoaded('brand') || !$this->brand) {
            return null;
        }
        if (!$this->brand->relationLoaded('vendorSetting') || !$this->brand->vendorSetting) {
            return null;
        }
        $pct = $this->brand->vendorSetting->coupon_discount_percent;
        return $pct !== null ? (float) $pct : null;
    }
}

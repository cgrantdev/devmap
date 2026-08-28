<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vendor's submitted claim for a verification badge (cGMP-manufacturing
 * or 7+-compound independent testing). Julia reviews the doc + approves
 * or rejects. Approved claims flip the badge on the vendor's storefront
 * + card. See docs/vendor-certifications.md.
 */
class VendorCertificationClaim extends Model
{
    // Whitelisted claim types. Add here + backfill labels in TYPE_LABELS.
    public const TYPE_CGMP = 'cgmp';
    public const TYPE_TESTING_7X = 'testing_7x';

    public const TYPES = [self::TYPE_CGMP, self::TYPE_TESTING_7X];

    public const TYPE_LABELS = [
        self::TYPE_CGMP => 'cGMP Compliant Manufacturing',
        self::TYPE_TESTING_7X => '7+ Compound Independent Testing',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'brand_id', 'submitted_by_user_id', 'type',
        'document_path', 'document_original_name', 'notes',
        'status', 'admin_notes', 'verified_at', 'verified_by_user_id',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function label(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}

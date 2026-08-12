<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VendorApiToken extends Model
{
    use HasFactory;

    protected $fillable = ['brand_id', 'name', 'token_hash', 'last_used_at', 'revoked_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Mint a new token for a brand. Returns [model, plaintext]. Plaintext is
     * shown to the vendor ONCE at creation; we only persist the SHA-256 hash.
     * Prefix "pmap_v_" makes leaked tokens searchable in git/logs.
     */
    public static function mint(int $brandId, string $name): array
    {
        $plain = 'pmap_v_' . Str::random(48);
        $token = static::create([
            'brand_id' => $brandId,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
        ]);
        return [$token, $plain];
    }

    public static function findByPlaintext(string $plain): ?self
    {
        return static::where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();
    }
}

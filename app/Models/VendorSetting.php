<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'banner',
        'logo',
        'company_name',
        'company_detail',
        'url',
        'contact_email',
        'phone_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

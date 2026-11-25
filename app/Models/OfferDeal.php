<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferDeal extends Model
{
    protected $table = 'offer_deals';

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'tag',
        'img',
        'link',
        'status',
        'type',
        'holder_type',
        'color_code'
    ];

    // 1 = active, 2 = inactive (per column comment)
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    public $timestamps = false; // only created_at exists, no updated_at

    protected $casts = [
        'merchant_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
    ];

    // Relation to merchant, if you have a Merchant model
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
<?php

namespace App\Models\HandymanStore;

use App\Models\User;
use App\Models\Driver;
use App\Models\HandymanOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HandymanStoreRating extends Model
{
    use HasFactory;

    protected $table   = 'handyman_store_ratings';
    protected $guarded = [];

    /* ── Relationships ── */
    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class, 'handyman_store_id');
    }

    public function HandymanOrder()
    {
        return $this->belongsTo(HandymanOrder::class, 'handyman_order_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /* ── Scopes ── */
    public function scopeForStore($query, $handyman_store_id)
    {
        return $query->where('handyman_store_id', $handyman_store_id);
    }

    public function scopeForMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id);
    }
}
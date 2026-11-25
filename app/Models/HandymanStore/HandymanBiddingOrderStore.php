<?php

namespace App\Models\HandymanStore;

use App\Models\User;
use App\Models\HandymanBiddingOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HandymanBiddingOrderStore extends Model
{
    use HasFactory;

    protected $guarded = [];

    /* ── status constants ── */
    const STATUS_PENDING  = 0;
    const STATUS_ACCEPT   = 1;
    const STATUS_COUNTER  = 2;
    const STATUS_REJECT   = 3;
    const STATUS_FINALIZE = 4;

    /* ── Relationships ── */
    public function HandymanBiddingOrder()
    {
        return $this->belongsTo(HandymanBiddingOrder::class, 'handyman_bidding_order_id');
    }

    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class, 'handyman_store_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Scopes ── */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPT);
    }

    public function scopeCountered($query)
    {
        return $query->where('status', self::STATUS_COUNTER);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECT);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZE);
    }

    public function scopeForStore($query, $handyman_store_id)
    {
        return $query->where('handyman_store_id', $handyman_store_id);
    }

    public function scopeForOrder($query, $bidding_order_id)
    {
        return $query->where('handyman_bidding_order_id', $bidding_order_id);
    }
}
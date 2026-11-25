<?php

namespace App\Models\HandymanStore;

use App\Models\Merchant;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HandymanStoreSubscriptionPurchase extends Model
{
    use HasFactory;

    protected $table   = 'handyman_store_subscription_purchases';
    protected $guarded = [];

    /* ── subscriber type constants ── */
    const TYPE_STORE  = 'store';
    const TYPE_DRIVER = 'driver';

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class, 'handyman_store_id');
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function Subscription()
    {
        return $this->belongsTo(MerchantHandymanStoreSubscription::class, 'subscription_id');
    }

    public function getRemainingPostsAttribute(): int
    {
        if (empty($this->Subscription)) return 0;
        if ($this->Subscription->isUnlimited()) return PHP_INT_MAX;
        return max(0, $this->no_of_service_post - $this->used_service_post);
    }

    /* ── Scopes ── */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeForStore($query, $handyman_store_id)
    {
        return $query->where('handyman_store_id', $handyman_store_id)
                     ->where('subscriber_type', self::TYPE_STORE);
    }

    public function scopeForDriver($query, $driver_id)
    {
        return $query->where('driver_id', $driver_id)
                     ->where('subscriber_type', self::TYPE_DRIVER);
    }
}
<?php

namespace App\Models\HandymanStore;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantHandymanStoreSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table   = 'merchant_handyman_store_subscriptions';
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function Purchases()
    {
        return $this->hasMany(HandymanStoreSubscriptionPurchase::class, 'subscription_id');
    }

    public function HandymanStores()
    {
        return $this->hasMany(HandymanStore::class, 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

    public function scopeForMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id);
    }

    public function isUnlimited(): bool
    {
        return $this->no_of_service_post == 0;
    }
    public function isLifetime(): bool
    {
        return $this->validity_days == 0;
    }
}
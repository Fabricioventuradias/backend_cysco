<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class HandymanStoreSubscription extends Model
{
    use HasFactory;

    // status
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_CANCELLED = 3;

    // payment_status
    const PAY_PAID = 1;
    const PAY_PENDING = 2;
    const PAY_FAILED = 3;

    public function plan()
    {
        return $this->belongsTo(
            HandymanStoreSubscriptionPlan::class,
            'handyman_store_subscription_plan_id'
        );
    }

    public function transactions()
    {
        return $this->hasMany(
            HandymanStoreSubscriptionTransaction::class,
            'handyman_store_subscription_id'
        );
    }

    /**
     * Is this subscription currently usable?
     */
    public function isActive(): bool
    {
        if (empty($this->end_date)) {
            return false;
        }
    
        return (int) $this->status === self::STATUS_ACTIVE
            && (int) $this->payment_status === self::PAY_PAID
            && Carbon::parse($this->end_date)->gte(now()->startOfDay());
    }
    public function User()
    {
        return $this->belongsTo(User:: class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanStoreSubscriptionPlan extends Model
{
    use HasFactory;


    // protected $guarded = [];

    // protected $casts = [
    //     'price'               => 'decimal:2',
    //     'discount_value'      => 'decimal:2',
    //     'max_discount_amount' => 'decimal:2',
    //     'duration_days'       => 'integer',
    // ];

    // 1 = fixed amount, 2 = percentage
    const DISCOUNT_FIXED = 1;
    const DISCOUNT_PERCENT = 2;

    public function translations()
    {
        return $this->hasMany(
            HandymanStoreSubscriptionPlanTranslation::class,
            'handyman_store_subscription_plan_id'
        );
    }

    public function subscriptions()
    {
        return $this->hasMany(
            HandymanStoreSubscription::class,
            'handyman_store_subscription_plan_id'
        );
    }

    /**
     * Localized name for the given locale, falling back to the column value.
     */
    public function localeName($locale)
    {
        return optional($this->translations->firstWhere('locale', $locale))->name
            ?? $this->name;
    }

    public function isPercentage(): bool
    {
        return (int) $this->discount_type === self::DISCOUNT_PERCENT;
    }
}

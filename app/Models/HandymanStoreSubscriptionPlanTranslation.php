<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanStoreSubscriptionPlanTranslation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function plan()
    {
        return $this->belongsTo(
            HandymanStoreSubscriptionPlan::class,
            'handyman_store_subscription_plan_id'
        );
    }
}

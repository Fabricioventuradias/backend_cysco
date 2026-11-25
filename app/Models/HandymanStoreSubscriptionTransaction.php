<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanStoreSubscriptionTransaction extends Model
{
    use HasFactory;
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const STATUS_SUCCESS = 1;
    const STATUS_PENDING = 2;
    const STATUS_FAILED = 3;

    public function subscription()
    {
        return $this->belongsTo(
            HandymanStoreSubscription::class,
            'subscription_id'
        );
    }
}

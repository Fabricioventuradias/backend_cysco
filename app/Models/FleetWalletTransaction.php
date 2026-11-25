<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetWalletTransaction extends Model
{
    protected $guarded = [];
    
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function handymanOrder()
    {
        return $this->belongsTo(HandymanOrder::class, 'handyman_order_id');
    }
}

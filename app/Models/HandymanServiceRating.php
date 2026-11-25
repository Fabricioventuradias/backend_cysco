<?php

namespace App\Models;

use App\Models\HandymanStore\HandymanStore;
use App\Models\HandymanStore\HandymanStoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HandymanServiceRating extends Model
{
    use HasFactory;

    // protected $table  = 'handyman_service_ratings';
    protected $guarded = [];

    /* ── Relationships ── */
    public function HandymanOrder()
    {
        return $this->belongsTo(HandymanOrder::class, 'handyman_order_id');
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function HandymanStoreService()
    {
        return $this->belongsTo(HandymanStoreService::class, 'handyman_store_service_id');
    }

    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class, 'handyman_store_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
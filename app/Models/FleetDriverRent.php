<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetDriverRent extends Model
{
    protected $table = 'fleet_driver_rents';

    protected $fillable = [
        'fleet_id', 'driver_id', 'driver_vehicle_id',
        'amount', 'frequency', 'due_date', 'status',
        'paid_at', 'wallet_transaction_ref',
    ];

    public function Fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_id');
    }
    public function Driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
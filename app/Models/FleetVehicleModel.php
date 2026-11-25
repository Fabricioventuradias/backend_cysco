<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetVehicleModel extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function VehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function DriverVehicleAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class, 'vehicle_id');
    }
    
    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }
    
    public function VehicleMake()
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }
    
    public function VehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
}

<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $guarded = [];
    
    protected $hidden = ['LanguageVehicleModelAny', 'LanguageVehicleModelSingle'];

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function VehicleMake()
    {
        return $this->belongsTo(VehicleMake::class);
    }

    public function LanguageVehicleModel()
    {
        return $this->hasMany(LanguageVehicleModel::class);
    }

    public function LanguageVehicleModelAny()
    {
        return $this->hasOne(LanguageVehicleModel::class);
    }

    public function LanguageVehicleModelSingle()
    {
        return $this->hasOne(LanguageVehicleModel::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getVehicleModelNameAttribute()
    {
        if (empty($this->LanguageVehicleModelSingle)) {
            return $this->LanguageVehicleModelAny->vehicleModelName;
        }
        return $this->LanguageVehicleModelSingle->vehicleModelName;
    }

    public function getVehicleModelDescriptionAttribute()
    {
        if (empty($this->LanguageVehicleModelSingle)) {
            return $this->LanguageVehicleModelAny->vehicleModelDescription;
        }
        return $this->LanguageVehicleModelSingle->vehicleModelDescription;
    }
    
    // VehicleModel → belongs to many Fleets (via pivot)
    public function fleets()
    {
        return $this->belongsToMany(Fleet::class,'fleet_vehicle_models','vehicle_model_id','fleet_id');
    }

    // VehicleModel → has many FleetVehicles (physical vehicles)
    public function fleetVehicles()
    {
        return $this->hasMany(FleetVehicle::class, 'vehicle_model_id', 'id');
    }
}

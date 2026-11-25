<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirFreightServiceType extends Model
{
    protected $guarded = [];
    
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_air_freight_service_types', 'air_freight_service_type_id', 'category_id')
                    ->with('weightParams'); 
    }
    
    public function AirFreightStop(){
        return $this->hasMany(AirFreightStop::class,'air_service_type_id','id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirFreightCheckout extends Model
{
    protected $guarded = [];
    
    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
    
    public function CategoryService(){
        return $this->belongsTo(CategoryAirFreightServiceType::class,'air_freight_category_service_id','id');
    }
    
    public function AirFreightStopSource(){
        return $this->belongsTo(AirFreightStop::class,'source_id','id');
    }
    
    public function AirFreightStopDestination(){
        return $this->belongsTo(AirFreightStop::class,'destination_id','id');
    }
    
    public function AirFreightBookingDoc(){
        return $this->hasMany(AirFreightBookingDocument::class,'checkout_id','id');
    }
}
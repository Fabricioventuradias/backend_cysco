<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAirFreightServiceType extends Model
{
    protected $guarded = [];
    public $timestamps = false;


    public function Category(){
        return $this->belongsTo(Category::class);
    }

    public function AirFreightServiceType(){
        return $this->belongsTo(AirFreightServiceType::class);
    }
    
    public function AirFreightWeightParameter(){
        return $this->belongsTo(AirFreightWeightParameter::class);
    }
}
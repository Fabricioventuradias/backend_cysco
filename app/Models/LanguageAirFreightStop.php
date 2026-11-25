<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageAirFreightStop extends Model
{
    protected $guarded = [];

    public function AirStop(){
        return $this->belongsTo(AirFreightStop::class);
    }
}
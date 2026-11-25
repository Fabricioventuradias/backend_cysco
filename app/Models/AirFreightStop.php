<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class AirFreightStop extends Model
{
    protected $hidden = array('pivot','LanguageSingle','LanguageAny');
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageAirFreightStop::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageAirFreightStop::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {

        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->name;
        }
        return $this->LanguageSingle->name;
    }

    public function getAirStopAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny();
        }
        return $this->LanguageSingle();
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function LanguageAirFreightStop()
    {
        return $this->hasMany(LanguageAirFreightStop::class);
    }

    public function AirFreightServiceType(){
        return $this->belongsTo(AirFreightServiceType::class,'air_service_type_id','id');
    }
}
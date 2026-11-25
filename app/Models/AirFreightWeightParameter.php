<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class AirFreightWeightParameter extends Model
{
    protected $guarded = [];

    protected $hidden = ['pivot', 'LanguageAny', 'LanguageSingle'];

    public function LanguageAny()
    {
        return $this->hasOne(AirFreightWeightParameterTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(AirFreightWeightParameterTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getAirFreightWeightParameterNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->name;
        }
        return $this->LanguageSingle->name;
    }

    public function getAirFreightWeightParameterDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->description;
        }
        return $this->LanguageSingle->description;
    }

    public function Segment()
    {
        return $this->belongsToMany(Segment::class);
    }

    public function ServiceTypes()
    {
        return $this->belongsToMany(
            ServiceType::class,               // adjust to your actual ServiceType model^M
            'air_freight_weight_parameter_segment',
            'air_freight_weight_parameter_id',
            'service_type_id'
        )->withPivot('segment_id')->withTimestamps();
    }
}
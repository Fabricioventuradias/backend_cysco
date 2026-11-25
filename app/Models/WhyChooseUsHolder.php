<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChooseUsHolder extends Model
{
    protected $fillable = [
        'merchant_id',
        'segment_id',
        'title',
        'icon',
        'description',
    ];
}
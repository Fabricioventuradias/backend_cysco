<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetDriver extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function Driver(){
        return $this->belongsTo(Driver::class);
    }

    public function Fleet(){
        return $this->belongsTo(Fleet::class);
    }
}

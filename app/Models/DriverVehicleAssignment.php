<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverVehicleAssignment extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function Driver(){
        return $this->belongsTo(Driver::class);
    }
}

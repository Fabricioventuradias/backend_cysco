<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirFreightBookingDocument extends Model
{
    protected $guarded = [];
    
    public function Document(){
        return $this->belongsTo(Document::class);
    }
    
    public function Checkout(){
        return $this->belongsTo(AirFreightCheckout::class);
    }
    
}
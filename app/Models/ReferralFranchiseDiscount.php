<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralFranchiseDiscount extends Model
{
    protected $guarded =[];
    // app/Models/ReferralFranchiseDiscount.php
    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }
}
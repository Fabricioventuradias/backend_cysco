<?php

namespace App\Models\Checkr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckrCandidate extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'driver_id',
        'response',
        'candidate_id',
        'candidate_driver_license',
        'candidate_driver_license_doc',
        'ssn_number_response',
    ];

}
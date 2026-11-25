<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTypeImage extends Model
{
    use HasFactory;

    protected $table = 'service_type_images';

    protected $fillable = [
        'service_type_id',
        'image',
    ];

    /**
     * service_types
     */
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
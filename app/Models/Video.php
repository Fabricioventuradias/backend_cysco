<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['merchant_id', 'title', 'description','type', 'video', 'status','holder_type','media_type','segment_id'];
    
    public function Segment(){
        return $this->belongsTo(Segment::class);
    }
}
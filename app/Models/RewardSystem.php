<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardSystem extends Model
{
    protected $guarded = [];

    public function Country(){
        return $this->belongsTo(Country::class);
    }

    public function CountryArea(){
        return $this->belongsTo(CountryArea::class);
    }
public function rewardSegments()
{
    return $this->hasMany(
        RewardSystemSegment::class,
        'reward_system_id'
    );
}

public function segments()
{
    return $this->belongsToMany(
        Segment::class,
        'rewardsystems_segments',
        'reward_system_id',
        'segment_id'
    );
}
}

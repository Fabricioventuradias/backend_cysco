<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardSystemSegment extends Model
{
    protected $table = 'rewardsystems_segments';

    public $timestamps = false;

    protected $fillable = [
        'reward_system_id',
        'segment_id'
    ];

    public function rewardSystem()
    {
        return $this->belongsTo(RewardSystem::class, 'reward_system_id');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }
}
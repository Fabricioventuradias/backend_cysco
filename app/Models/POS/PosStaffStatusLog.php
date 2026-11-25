<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosStaffStatusLog extends Model
{
    protected $table = 'pos_staff_status_log';

    public $timestamps = false;

    protected $fillable = [
        'staff_profile_id',
        'pos_session_id',
        'status',
        'note',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(PosStaffProfile::class, 'staff_profile_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('ended_at');
    }
}

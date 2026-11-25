<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    protected $table = 'pos_sessions';

    protected $fillable = [
        'merchant_id',
        'business_segment_id',
        'staff_profile_id',
        'pos_device_id',
        'pos_terminal_id',
        'opening_cash',
        'closing_cash',
        'login_at',
        'logout_at',
        'status',
    ];

    protected $casts = [
        'opening_cash' => 'float',
        'closing_cash' => 'float',
        'login_at'     => 'datetime',
        'logout_at'    => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(PosStaffProfile::class, 'staff_profile_id');
    }

    public function PosTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(PosCashTransaction::class, 'pos_session_id');
    }

    public function posPayments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_session_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(PosSessionTransfer::class, 'pos_session_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getCashSalesAttribute(): float
    {
        return (float) $this->posPayments
            ->where('payment_method', PosPayment::METHOD_CASH)
            ->where('status', PosPayment::STATUS_COMPLETED)
            ->sum('amount');
    }

    public function getExpectedDrawerAttribute(): float
    {
        return $this->opening_cash + $this->cash_sales;
    }

    public function BusinessSegment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessSegment\BusinessSegment::class, 'business_segment_id');
    }
}
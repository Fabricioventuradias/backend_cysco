<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    protected $table = 'pos_payments';

    protected $fillable = [
        'order_id',
        'pos_session_id',
        'payment_method',
        'amount',
        'cash_received',
        'change_returned',
        'transaction_ref',
        'status',
    ];

    protected $casts = [
        'amount'          => 'float',
        'cash_received'   => 'float',
        'change_returned' => 'float',
    ];

    // ── Method constants ─────────────────────────────────────
    const METHOD_CASH   = 'cash';
    const METHOD_CARD   = 'card';
    const METHOD_UPI    = 'upi';
    const METHOD_WALLET = 'wallet';
    const METHOD_SPLIT  = 'split';

    // ── Status constants ─────────────────────────────────────
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_REFUNDED  = 'refunded';

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }
}
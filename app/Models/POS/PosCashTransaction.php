<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosCashTransaction extends Model
{
    protected $table = 'pos_cash_transactions';

    protected $fillable = [
        'pos_session_id', 'order_id', 'pos_payment_id',
        'type', 'direction', 'amount', 'performed_by', 'note',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    const TYPE_SALE          = 1;
    const TYPE_REFUND        = 2;
    const TYPE_CASH_IN       = 3;
    const TYPE_CASH_OUT      = 4;
    const TYPE_OPENING_FLOAT = 5;

    const DIR_IN  = 1;
    const DIR_OUT = 2;

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }
}
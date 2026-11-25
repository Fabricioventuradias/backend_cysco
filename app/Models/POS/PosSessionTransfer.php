<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosSessionTransfer extends Model
{
    protected $table = 'pos_session_transfers';

    protected $fillable = [
        'pos_session_id',
        'from_terminal_id',
        'to_terminal_id',
        'from_cashier_id',
        'to_cashier_id',
        'transferred_by',
        'reason',
    ];

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function fromTerminal()
    {
        return $this->belongsTo(PosTerminal::class, 'from_terminal_id');
    }

    public function toTerminal()
    {
        return $this->belongsTo(PosTerminal::class, 'to_terminal_id');
    }

    public function fromCashier()
    {
        return $this->belongsTo(PosStaffProfile::class, 'from_cashier_id');
    }

    public function toCashier()
    {
        return $this->belongsTo(PosStaffProfile::class, 'to_cashier_id');
    }

    // the manager who performed the transfer
    public function transferredBy()
    {
        return $this->belongsTo(PosStaffProfile::class, 'transferred_by');
    }
}
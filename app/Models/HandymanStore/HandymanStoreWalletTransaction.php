<?php

namespace App\Models\HandymanStore;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanStoreWalletTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    const TYPE_CREDIT = 1;
    const TYPE_DEBIT  = 2;

    public function HandymanStore(){
        return $this->belongsTo(HandymanStore::class);
    }

    public function ActionMerchant()
    {
        return $this->belongsTo(Merchant::class, "action_merchant_id");
    }
    /* ── Scopes ── */
    public function scopeCredit($query)
    {
        return $query->where('transaction_type', self::TYPE_CREDIT);
    }

    public function scopeDebit($query)
    {
        return $query->where('transaction_type', self::TYPE_DEBIT);
    }

    public function scopeForStore($query, $handyman_store_id)
    {
        return $query->where('handyman_store_id', $handyman_store_id);
    }
}

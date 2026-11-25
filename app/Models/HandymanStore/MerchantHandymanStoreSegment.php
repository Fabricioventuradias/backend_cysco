<?php

namespace App\Models\HandymanStore;

use App\Models\Merchant;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantHandymanStoreSegment extends Model
{
    use HasFactory;
     protected $fillable = [
        'merchant_id',
        'handyman_store_id',
        'segment_id',
        'status',
        'sequence',
    ];

   

    /* ── Relationships ── */
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class, 'handyman_store_id');
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    /* ── Scopes ── */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeForStore($query, $handyman_store_id)
    {
        return $query->where('handyman_store_id', $handyman_store_id);
    }

    public function scopeForMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id);
    }
}
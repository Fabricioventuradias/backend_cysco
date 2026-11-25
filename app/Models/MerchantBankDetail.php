<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantBankDetail extends Model
{
    use HasFactory;

    protected $table = 'merchant_bank_details';

    protected $fillable = [
        'merchant_id',
        'user_id',
        'user_type',
        'bank_details',
        'account_status',
        'verification_notes',
        'verified_at',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Scopes
     */
    public function scopeByMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id);
    }

    public function scopeByUserType($query, $user_type)
    {
        return $query->where('user_type', $user_type);
    }

   

    public function scopeVerified($query)
    {
        return $query->where('account_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('account_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('account_status', 'failed');
    }

    public function scopeForUser($query, $merchant_id, $user_id, $user_type)
    {
        return $query->where([
            'merchant_id' => $merchant_id,
            'user_id' => $user_id,
            'user_type' => $user_type,
        ]);
    }
    public function scopeForMerchant($query, $merchant_id, $user_type)
    {
        return $query->where([
            'merchant_id' => $merchant_id,
            'user_type' => $user_type,
        ]);
    }

    /**
     * Methods
     */
    public function getBankDetail($key, $default = null)
    {
        return $this->bank_details[$key] ?? $default;
    }

    public function updateBankDetails($details)
    {
        $this->bank_details = array_merge($this->bank_details ?? [], $details);
        return $this->save();
    }

    public function markAsVerified($notes = null)
    {
        $this->update([
            'account_status' => 'verified',
            'verification_notes' => $notes,
            'verified_at' => now(),
        ]);
        return $this;
    }

    public function markAsFailed($notes = null)
    {
        $this->update([
            'account_status' => 'failed',
            'verification_notes' => $notes,
        ]);
        return $this;
    }

    public function markAsRejected($notes = null)
    {
        $this->update([
            'account_status' => 'rejected',
            'verification_notes' => $notes,
        ]);
        return $this;
    }

    public function isPending()
    {
        return $this->account_status === 'pending';
    }

    public function isVerified()
    {
        return $this->account_status === 'verified';
    }

    public function isFailed()
    {
        return $this->account_status === 'failed';
    }

    public function isRejected()
    {
        return $this->account_status === 'rejected';
    }
}
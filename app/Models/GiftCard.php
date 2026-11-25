<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $fillable = [
        'merchant_id',
        'code',
        'amount',
        'remaining_amount',
        'sender_id',
        'receiver_id',
        'status',
        'is_scheduled',
        'send_at',
        'sent_at',
        'sender_email',
        'sender_message'
    ];

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

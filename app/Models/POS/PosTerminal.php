<?php

namespace App\Models\POS;

use App\Traits\POS\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTerminal extends Model
{
    use HasTranslations;

    protected $table = 'pos_terminals';

    protected $fillable = [
        'merchant_id',
        'business_segment_id',
        'name',
        'code',
        'type',
        'device_id',
        'location_note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'terminal_status' => 'integer',
    ];

    // Translation config (name + location_note can be translated)
    protected $translatable    = ['name', 'location_note'];
    protected $translationType = 'pos_terminal';

    // Type constants
    const TYPE_COUNTER = 'counter';
    const TYPE_KIOSK   = 'kiosk';
    const TYPE_PHONE   = 'phone';
    const TYPE_TABLET  = 'tablet';



    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'pos_terminal_id');
    }

    public function activeSession()
    {
        return $this->hasOne(PosSession::class, 'pos_terminal_id')
                    ->where('status', PosSession::STATUS_ACTIVE);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('business_segment_id', $storeId);
    }

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function isInUse(): bool
    {
        return $this->activeSession()->exists();
    }
}

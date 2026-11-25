<?php

namespace App\Models\POS;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\BusinessSegment\BusinessSegment;
use Illuminate\Foundation\Auth\User as Authenticatable;

class PosStaffProfile extends Authenticatable
{
    protected $table = 'pos_staff_profiles';

    protected $fillable = [
        'user_id',
        'merchant_id',
        'business_segment_id',
        'role_id',
        'pin',
        'shift_start',
        'shift_end',
        'current_status',
        'is_active',
        'phone',
        'avatar_color',
    ];

    protected $hidden = ['pin'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Status constants
    const STATUS_ACTIVE  = 'active';
    const STATUS_BREAK   = 'break';
    const STATUS_OFFLINE = 'offline';

    public function Merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PosRole::class, 'role_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'staff_profile_id');
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(PosSession::class, 'staff_profile_id')
                    ->where('status', 'active');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PosStaffStatusLog::class, 'staff_profile_id');
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

    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    // ── Accessors ────────────────────────────────────────────

    /**
     * Full name from related User: "Amit Kumar"
     */
    public function getFullNameAttribute(): string
    {
        $first = $this->user->first_name ?? '';
        $last  = $this->user->last_name ?? '';
        $name  = trim("{$first} {$last}");
        return $name ?: 'Unknown';
    }

    /**
     * Initials: "Amit Kumar" → "AK"
     */
    public function getInitialsAttribute(): string
    {
        $name  = $this->full_name;
        $parts = explode(' ', $name);

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Shift label: "9 AM – 5 PM"
     */
    public function getShiftLabelAttribute(): ?string
    {
        if (!$this->shift_start || !$this->shift_end) return null;
        return date('g A', strtotime($this->shift_start))
            . ' – '
            . date('g A', strtotime($this->shift_end));
    }

    /**
     * Status label for UI pill
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->current_status) {
            'active'  => 'Active',
            'break'   => 'Break',
            'offline' => 'Offline',
            default   => 'Offline',
        };
    }

    // ── Permission helpers ───────────────────────────────────

    public function hasPermission(string $permissionKey): bool
    {
        return $this->role?->hasPermission($permissionKey) ?? false;
    }

    public function getPermissionKeys(): array
    {
        return $this->role?->permissions()->pluck('key')->toArray() ?? [];
    }
    
    public function BusinessSegment(){
        return $this->belongsTo(BusinessSegment::class);
    }
}

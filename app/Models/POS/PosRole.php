<?php

namespace App\Models\POS;

use App\Traits\POS\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRole extends Model
{
    use HasTranslations;

    protected $table = 'pos_roles';

    protected $fillable = [
        'merchant_id',
        'business_segment_id',
        'name',
        'description',
        'is_system',
        'level'
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected $translatable    = ['name', 'description'];
    protected $translationType = 'pos_role';

    // ── FIX: Override morph class name ───────────────────────
    // Tells Eloquent to use 'pos_role' (matching DB)
    // instead of full class name 'App\Models\POS\PosRole'
    public function getMorphClass(): string
    {
        return $this->translationType;
    }
    // ─────────────────────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PosPermission::class,
            'pos_role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(PosStaffProfile::class, 'role_id');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where(function ($q) use ($storeId) {
            $q->where('business_segment_id', $storeId)
              ->orWhereNull('business_segment_id');
        });
    }

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function hasPermission(string $permissionKey): bool
    {
        return $this->permissions()->where('key', $permissionKey)->exists();
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}
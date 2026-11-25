<?php

namespace App\Models\POS;

use App\Traits\POS\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PosPermission extends Model
{
    use HasTranslations;

    protected $table = 'pos_permissions';

    protected $fillable = [
        'key',
        'name',
        'description',
        'module',
    ];

    // ── Translation config ───────────────────────────────────
    protected $translatable    = ['name', 'description'];
    protected $translationType = 'pos_permission';

    // ── Permission key constants ─────────────────────────────
    const ORDERS_CREATE         = 'orders:create';
    const ORDERS_APPLY_DISCOUNT = 'orders:apply_discount';
    const ORDERS_REFUND         = 'orders:refund';
    const ORDERS_VOID           = 'orders:void';
    const REPORTS_VIEW          = 'reports:view';
    const STAFF_MANAGE          = 'staff:manage';
    const PRODUCTS_EDIT         = 'products:edit';
    const POS_OPEN_SESSION      = 'pos:open_session';
    const POS_CLOSE_SESSION     = 'pos:close_session';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            PosRole::class,
            'pos_role_permissions',
            'permission_id',
            'role_id'
        );
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}

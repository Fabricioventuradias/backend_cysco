<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PosPermission Middleware
 * ─────────────────────────────────────────────────────────────
 * Checks that the POS staff (already set by PosApiMiddleware)
 * has the required permission via their role.
 *
 * Usage on routes:
 *   ->middleware('pos.permission:orders:create')
 *   ->middleware('pos.permission:staff:manage')
 *   ->middleware('pos.permission:orders:void')
 *
 * Must run AFTER pos-api middleware (which sets $request->pos_staff).
 *
 * Register in app/Http/Kernel.php:
 *   'pos.permission' => \App\Http\Middleware\PosPermission::class,
 * ─────────────────────────────────────────────────────────────
 */
class PosPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey)
    {
        // pos_staff is set by PosApiMiddleware
        $staffProfile = $request->pos_staff_id
            ?? $request->attributes->get('pos_staff');

        if (!$staffProfile) {
            return response()->json([
                'status'  => false,
                'message' => 'Authentication required. Run pos-api middleware before pos.permission.',
                'code'    => 'MIDDLEWARE_ORDER_ERROR',
            ], 401);
        }

        // Check permission via role → role_permissions → permissions
        $hasPermission = DB::table('pos_role_permissions as rp')
            ->join('pos_permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('rp.role_id', $staffProfile->role_id)
            ->where('p.key', $permissionKey)
            ->exists();

        if (!$hasPermission) {
            $roleName = DB::table('pos_roles')
                ->where('id', $staffProfile->role_id)
                ->value('name') ?? 'Unknown';

            return response()->json([
                'status'  => false,
                'message' => "Access denied. Your role ({$roleName}) does not have the [{$permissionKey}] permission.",
                'code'    => 'PERMISSION_DENIED',
                'required_permission' => $permissionKey,
                'your_role'           => $roleName,
            ], 403);
        }

        return $next($request);
    }
}

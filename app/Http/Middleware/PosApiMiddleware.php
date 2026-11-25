<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Models\Merchant;

class PosApiMiddleware
{
    use ApiResponseTrait;
    public function handle($request, Closure $next)
    {
        if(strpos($request->server('REQUEST_URI'), 'verify-pin') !== false){
            $public_key = $request->header('publicKey');
            $secret_key = $request->header('secretKey');
            if (!empty($public_key) && !empty($secret_key)) {
                $clientDetail = Merchant::where([['merchantPublicKey', '=', $public_key], ['merchantSecretKey', '=', $secret_key], ['merchantStatus', '=', 1]])->first();
                if (empty($clientDetail)) {
                    return response()->json(['version' => 'NA', 'result' => "0", 'message' => trans("common.merchant_not_found"), 'data' => []]);
                }
                
                $request->merge([
                    'merchant_id' => $clientDetail->id
                ]);
            }
        }else{
            // ── 1. Get token from $_SERVER (Laravel strips it) ────
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
            if (empty($authHeader) && function_exists('getallheaders')) {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            if (empty($authHeader)) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - no token",
                    'data'    => [],
                ]);
            }
    
            $rawToken = trim(preg_replace('/^Bearer\s+/i', '', $authHeader));
    
            if (empty($rawToken)) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - empty token",
                    'data'    => [],
                ]);
            }
    
            // ── 2. Manually decode JWT payload (no library needed) ─
            // JWT format: header.payload.signature (3 base64 parts)
            $parts = explode('.', $rawToken);
    
            if (count($parts) !== 3) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - invalid token format",
                    'data'    => [],
                ]);
            }
    
            // Base64Url decode the payload (middle part)
            $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
            $payload     = json_decode($payloadJson, true);
    
            if (empty($payload) || empty($payload['jti']) || empty($payload['sub'])) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - invalid token payload",
                    'data'    => [],
                ]);
            }
    
            $tokenId = $payload['jti'];
            $userId  = $payload['sub'];
    
            // ── 3. Look up token in oauth_access_tokens ───────────
            $tokenRecord = DB::table('oauth_access_tokens')
                ->where('id', $tokenId)
                ->where('revoked', 0)
                ->first();
    
            if (empty($tokenRecord)) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - token not found or revoked",
                    'data'    => [],
                ]);
            }
    
            // ── 4. Check expiry ───────────────────────────────────
            if ($tokenRecord->expires_at && strtotime($tokenRecord->expires_at) < time()) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - token expired",
                    'data'    => [],
                ]);
            }
    
            // ── 5. Load User ──────────────────────────────────────
            $user = \App\Models\User::find($userId);
            if (empty($user)) {
                return response()->json([
                    'version' => 'NA',
                    'result'  => "999",
                    'message' => "unauthorised request - user not found",
                    'data'    => [],
                ]);
            }
    
            //Load POS staff profile
            $storeId = $request->business_segment_id;
            
            $staffQuery = DB::table('pos_staff_profiles')
                ->where('user_id', $user->id)
                ->where('is_active', 1);
    
            if ($storeId) {
                $staffQuery->where('business_segment_id', $storeId);
            }
    
            $staffProfile = $staffQuery->first();
    
            if (empty($staffProfile)) {
                return $this->failedResponse('No active POS staff profile found for this store.');
            }
    
            // ── 7. Load active session ────────────────────────────
            $activeSession = DB::table('pos_sessions')
                ->where('staff_profile_id', $staffProfile->id)
                ->where('status', 'active')
                ->first();
    
            
            // ── 8. Attach to $request ─────────────────────────────
            $request->merge([
                'merchant_id' => $staffProfile?->merchant_id,
                'store_id'    => $staffProfile?->business_segment_id,
                'pos_user_id' => $staffProfile?->id
            ]);
    
            $request->attributes->set('pos_staff',   $staffProfile);
            $request->attributes->set('pos_session', $activeSession);
            
            $request->pos_staff   = $staffProfile;
            $request->pos_session = $activeSession;
            $request->auth_user   = $user;
        }
        return $next($request);
    }
}
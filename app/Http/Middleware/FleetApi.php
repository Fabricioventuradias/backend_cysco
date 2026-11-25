<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;

class FleetApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $public_key = $request->header('publicKey');
        $secret_key = $request->header('secretKey');
        $AccessToken = $request->header('Authorization');
        if (!empty($public_key) && !empty($secret_key)) {
            $clientDetail = Merchant::where([['merchantPublicKey', '=', $public_key], ['merchantSecretKey', '=', $secret_key], ['merchantStatus', '=', 1]])->first();
            if (empty($clientDetail)) {
                return response()->json(['version' => 'NA','result' => "0", 'message' => trans("api.merchant_not_found"), 'data' => []]);
            }
            // if (empty($clientDetail->FleetConfiguration)) {
            //     return response()->json(['version' => 'NA','result' => "0", 'message' => trans('api.application_not_found'), 'data' => []]);
            // }
            $request->merge([
                'merchant_id' => $clientDetail->id,
                // 'fleet_email_enable' => $clientDetail->FleetConfiguration->fleet_email,
                // 'fleet_phone_enable' => $clientDetail->FleetConfiguration->fleet_phone,
                'fleet_login_type' => $clientDetail->FleetConfiguration?->login_type
            ]);
        }elseif (!empty($AccessToken)) {
            $user = $request->user('fleet-api');
            if (empty($user)) {
                return response()->json(['version' => 'NA','result' => "999", 'message' => "unauthorised request", 'data' => []]);
            }
            $request->merge([
                'merchant_id' => $user->merchant_id,
                // 'fleet_email_enable' => $clientDetail->FleetConfiguration->fleet_email,
                // 'fleet_phone_enable' => $clientDetail->FleetConfiguration->fleet_phone,
                'fleet_login_type' => $user->Merchant->FleetConfiguration?->login_type
            ]);
        }
        else {
            return response()->json(['version' => 'NA','result' => "999", 'message' => "unauthorised request", 'data' => []]);
        }
        return $next($request);
    }
}

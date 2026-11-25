<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\POS\PosStaffProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Merchant;
use Illuminate\Validation\ValidationException;
use App\Models\BusinessSegment\BusinessSegment;

class PosAdminLoginController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('guest:pos-admin')->except('logout');
    }
    
    public function showLoginForm($merchant_alias = null, $business_segment_alias = null)
    {
        if (Auth::guard('pos-admin')->check()) {
            return redirect()->route('pos-module.dashboard');
        }
        $valid_merchant = Merchant::where([['alias_name', '=', $merchant_alias], ['merchantStatus', TRUE]])->first();
        if (!empty($valid_merchant->id)) {
            setS3Config($valid_merchant);
            $valid_business_segment = BusinessSegment::where([['alias_name', '=', $business_segment_alias], ['merchant_id', $valid_merchant->id]])->first();
            if (!empty($valid_business_segment->id) && !empty($valid_merchant->id)) {
                // $posStaffProfile = PosStaffProfile::where('business_segment_id',$valid_business_segment->id)->where('merchant_id',$valid_merchant->id)->first();
                return view('pos-module.login',['merchant' => $valid_merchant, 'business_segment' => $valid_business_segment]);
            } else {
                return view('apporio');
            }
        }
        return view('apporio');
    }

    public function login(Request $request, $alias_name)
    {
        $request->validate([
            'phone' => 'required|string',
            'pin'   => 'required|digits:4',
        ]);
    
        $merchant = Merchant::where('alias_name', $alias_name)->firstOrFail();
    
        $profile = PosStaffProfile::with(['user', 'role.permissions','role'])
            ->where('phone', $request->phone)
            ->where('merchant_id', $merchant->id)
            ->active()
            ->first();
        if (!$profile || !Hash::check($request->pin, $profile->pin)) {
            throw ValidationException::withMessages([
                'phone' => [trans('auth.failed')],
            ]);
        }
    
        Auth::guard('pos-admin')->loginUsingId($profile->id);
        $request->session()->regenerate();
    
        saveLoginLogs(Auth::guard('pos-admin')->user(), $request->ip(), "POS_ADMIN");
        
        return redirect()->intended(route('pos-module.dashboard'));
    }
    
    

    public function logout(Request $request)
    {
        $staff = Auth::guard('pos-admin')->user();
    
        $merchantAlias = $staff?->Merchant?->alias_name;
        $storeAlias    = $staff?->BusinessSegment?->alias_name;
    
        Auth::guard('pos-admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        if ($merchantAlias && $storeAlias) {
            return redirect()->route('pos.login', [
                'merchant_alias_name' => $merchantAlias,
                'alias'               => $storeAlias,
            ]);
        }
    
        return redirect('/');
    }
}
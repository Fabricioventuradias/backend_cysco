<?php

namespace App\Http\Controllers\Auth;

use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;

class FleetLoginController extends Controller
{

    public function showFleetLoginForm($name = null)
    {
        // Session::flush();
        $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1]])->first();
        if (!empty($merchant)) {
            setS3Config($merchant);

            return view('merchant.fleet-login', compact('merchant'));
        }
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'phone' => 'required',
            'password' => 'required|min:5',


        ]);

        if (Auth::guard('fleet-user')->attempt(['fleet_owner_phone' => $request->phone, 'password' => $request->password, 'merchant_id' => $request->merchant_id, 'fleet_delete' => null], $request->remember)) {
            // logout
            // p(Auth::user('user'));
            // $alias = Auth::user('user')->Merchant->alias_name;

            return redirect()->route('fleet.details');
        }
        return redirect()->back()
            ->withInput($request->only('phone'))
            ->withErrors([
                'phone' => 'Invalid phone number or password.',
            ]);
    }

    public function logout()
    {
        $fleet = Auth::guard('fleet-user')->user();

        $alias_name = null;

        if ($fleet && $fleet->Merchant) {
            $alias_name = $fleet->Merchant->alias_name;
        }

        Auth::guard('fleet-user')->logout();
        Session::flush();

        if ($alias_name) {
            return redirect()->route('fleet.login', [
                'alias_name' => $alias_name
            ]);
        }

        return redirect('/');
    }

    // // set custom guard
    // protected function guard()
    // {
    //     return Auth::guard('merchant');
    // }



}
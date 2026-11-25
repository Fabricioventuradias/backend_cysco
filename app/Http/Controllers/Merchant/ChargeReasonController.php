<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ChargeReason;
use App\Models\LanguageChargeReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Traits\MerchantTrait;
use App\Models\InfoSetting;

class ChargeReasonController extends Controller
{
    use MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','ACCOUNT_TYPE')->first();
        view()->share('info_setting', $info_setting);
    }
    public function index()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);

        $charge_reasons = ChargeReason::where('merchant_id', $merchant_id)
        ->with(['LanguageSingle', 'LanguageAny'])
        ->orderBy('id', 'desc')
        ->paginate(20);

        $delete_permission        = true;
        $change_status_permission = true;

        return view('merchant.charge-reasons.index', compact(
            'charge_reasons', 'string_file', 'delete_permission', 'change_status_permission'
        ));
    }

    public function create()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);

        return view('merchant.charge-reasons.create', compact('string_file'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:191',
            'status' => 'required|in:0,1',
        ]);

       $merchant_id = get_merchant_id();
       $string_file = $this->getStringFile($merchant_id);

        $charge_reason = ChargeReason::create([
            'merchant_id' => $merchant_id,
            'status'      => $request->status,
        ]);

        LanguageChargeReason::create([
            'charge_reason_id' => $charge_reason->id,
            'reason'           => $request->name,
            'locale'           => Config::get('app.locale'),
        ]);

        return redirect()->route('charge-reasons.index')
            ->with('success', trans("$string_file.added_successfully"));
    }

    public function edit($id)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);

        $charge_reason = ChargeReason::findOrFail($id);

        $language_charge_reason = LanguageChargeReason::where('charge_reason_id', $id)
            ->where('locale', Config::get('app.locale'))
            ->first();

        return view('merchant.charge-reasons.create', compact(
            'string_file', 'charge_reason', 'language_charge_reason'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'   => 'required|string|max:191',
            'status' => 'required|in:0,1',
        ]);

        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $charge_reason = ChargeReason::findOrFail($id);
        $charge_reason->status = $request->status;
        $charge_reason->save();

        LanguageChargeReason::updateOrCreate(
            [
                'charge_reason_id' => $id,
                'locale'           => Config::get('app.locale'),
            ],
            [
                'reason' => $request->name,
            ]
        );

        return redirect()->route('charge-reasons.index')
            ->with('success', trans("$string_file.updated_successfully"));
    }

    public function changeStatus($id, $status)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $charge_reason = ChargeReason::findOrFail($id);
        $charge_reason->status = $status;
        $charge_reason->save();

        return redirect()->route('charge-reasons.index')
            ->with('success', trans("$string_file.status_updated"));
    }

    public function destroy($id)
    {
        LanguageChargeReason::where('charge_reason_id', $id)->delete();
        ChargeReason::findOrFail($id)->delete();

        return response('Charge reason deleted successfully.');
    }
}
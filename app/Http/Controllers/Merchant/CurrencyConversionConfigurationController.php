<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CurrencyConversionConfiguration;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;
use App\Models\InfoSetting;

class CurrencyConversionConfigurationController extends Controller
{
    use MerchantTrait;

    public function index(Request $request)
    {
        $checkPermission = check_permission(1, 'view_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $search_data = $request->all();

        $query = CurrencyConversionConfiguration::with('Country')
            ->where('merchant_id', $merchant_id);

        if (!empty($search_data['country_id'])) {
            $query->where('country_id', $search_data['country_id']);
        }

        $configs = $query->orderBy('id', 'desc')->paginate(20);
        $countries = Country::where('merchant_id', $merchant_id)->get();
        $info_setting = InfoSetting::where('slug', 'CURRENCY_CONVERSION_CONFIGURATION')->first();

        return view('merchant.currency-conversion-custom.index', compact(
            'configs', 'countries', 'string_file', 'info_setting', 'search_data'
        ));
    }

    public function create()
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $countries = Country::where('merchant_id', $merchant_id)->get();
        $base_currency_code = 'USD';

        return view('merchant.currency-conversion-custom.create', compact(
            'countries', 'base_currency_code', 'string_file'
        ));
    }

    public function store(Request $request)
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'local_currency_code' => 'required|string|max:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'status' => 'required|in:1,2',
        ]);

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $exists = CurrencyConversionConfiguration::where('merchant_id', $merchant_id)
            ->where('country_id', $request->country_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()
                ->with('error', 'A configuration already exists for this country.');
        }

        CurrencyConversionConfiguration::create([
            'merchant_id' => $merchant_id,
            'country_id' => $request->country_id,
            'base_currency_code' => 'USD',
            'local_currency_code' => strtoupper($request->local_currency_code),
            'exchange_rate' => $request->exchange_rate,
            'status' => $request->status,
        ]);

        return redirect()->route('merchant.currency_conversion.index')
            ->with('success', 'Currency conversion configuration created.');
    }

    public function edit($id)
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $config = CurrencyConversionConfiguration::where('merchant_id', $merchant_id)
            ->where('id', $id)
            ->firstOrFail();

        $countries = Country::where('merchant_id', $merchant_id)->get();
        $base_currency_code = $config->base_currency_code;

        return view('merchant.currency-conversion-custom.edit', compact(
            'countries', 'base_currency_code', 'string_file', 'config'
        ));
    }

    public function update(Request $request, $id)
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'local_currency_code' => 'required|string|max:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'status' => 'required|in:1,2',
        ]);

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $config = CurrencyConversionConfiguration::where('merchant_id', $merchant_id)
            ->where('id', $id)
            ->firstOrFail();

        $duplicate = CurrencyConversionConfiguration::where('merchant_id', $merchant_id)
            ->where('country_id', $request->country_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()
                ->with('error', 'Another configuration already exists for this country.');
        }

        $config->update([
            'country_id' => $request->country_id,
            'local_currency_code' => strtoupper($request->local_currency_code),
            'exchange_rate' => $request->exchange_rate,
            'status' => $request->status,
        ]);

        return redirect()->route('merchant.currency_conversion.index')
            ->with('success', 'Currency conversion configuration updated.');
    }

    public function destroy($id)
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);

        $config = CurrencyConversionConfiguration::where('merchant_id', $merchant->id)
            ->where('id', $id)
            ->firstOrFail();

        $config->delete();

        return redirect()->route('merchant.currency_conversion.index')
            ->with('success', 'Currency conversion configuration deleted.');
    }
}
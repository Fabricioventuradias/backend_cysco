<?php

namespace App\Http\Controllers\Merchant\AirFreight;

use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AirFreightServiceType;
use App\Models\AirFreightWeightParameter;
use App\Models\AirFreightWeightParameterTranslation;
use View;
use App;

class AirFreightWeightParameterController extends Controller
{
    use ImageTrait,MerchantTrait;
    public function index()
    {
        $merchant_id = get_merchant_id();
        $weightParams = AirFreightWeightParameter::where([['merchant_id',$merchant_id]])->get();
        return view('merchant.air_weight_parameter.index',compact('weightParams'));
    }


    public function add(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $all_segments_services = $this->getMerchantSegmentServices($merchant_id, '', 1, [], NULL, false, [], NULL);
        // dd($all_segments_services);
        $checkPermission = check_permission(1, $all_segments_services, true);
        $string_file = $this->getStringFile(NULL,$merchant);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        try {
            $weightparam = NULL;
            $arr_selected_service = [];
            if (!empty($id)) {
                $weightparam = AirFreightWeightParameter::find($id);
                $arr_selected_service = array_pluck($weightparam->ServiceTypes, 'id'); // adjust relation name to whatever links weightparam -> service types
            }
            
            $segment_data['arr_segment'] = $all_segments_services;
            $segment_data['selected'] = $arr_selected_service;   // now service IDs, not segment IDs
            $service_html = View::make('service')->with($segment_data)->render();
            $is_demo = $merchant->demo == 1 ? true : false;
            $arr_status = get_active_status("web",$string_file);
            return view('merchant.air_weight_parameter.edit', compact('weightparam','service_html','is_demo','arr_status'));
        }catch(\Exception $e)
        {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    public function save(Request $request, $id = NULL)
{
    $locale = App::getLocale();
    $merchant = get_merchant_id(false);
    $merchant_id = $merchant->id;
    $string_file = $this->getStringFile(NULL, $merchant);

    $request->validate([
        'name' => ['required', 'max:255',
            Rule::unique('air_freight_weight_parameter_translations', 'name')->where(function ($query) use ($merchant_id, &$locale, &$id) {
                $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', $locale], ['air_freight_weight_parameter_id', '!=', $id]]);
            })],
        'description' => 'required',
        'service' => 'required',   // changed from 'segment'
        'sequence' => 'required',
        'price' => 'required'
    ]);

    $weight_parameter = AirFreightWeightParameter::updateOrCreate([
        'id' => $id,
        'merchant_id' => $merchant_id,
    ], [
        'status' => isset($request->status) ? $request->status : 1,
        'sequence' => $request->sequence,
        'price' => $request->price
    ]);
    $id = $weight_parameter->id;

    // build service_type_id => segment_id pivot rows
    $serviceSegmentMap = \App\Models\ServiceType::whereIn('id', $request->service)->pluck('segment_id', 'id');

    $syncData = [];
    foreach ($request->service as $serviceTypeId) {
        $syncData[$serviceTypeId] = ['segment_id' => $serviceSegmentMap[$serviceTypeId] ?? null];
    }
    $weight_parameter->ServiceTypes()->sync($syncData);

    $this->SaveLanguageWeightParameter($merchant_id, $id, $request->name, $request->description);

    return redirect()->route('merchant.weightparameter.index')->withSuccess(trans("$string_file.saved_successfully"));
}

    public function SaveLanguageWeightParameter($merchant_id, $weight_param_id, $name, $description)
    {
        AirFreightWeightParameterTranslation::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'air_freight_weight_parameter_id' => $weight_param_id
        ], [
            'name' => $name,
            'description' => $description,
        ]);
    }
    
}
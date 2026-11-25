<?php

namespace App\Http\Controllers\Merchant\AirFreight;

use App\Models\AdvertisementBanner;
use App\Models\BusinessSegment\BusinessSegment;
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
use App\Models\AirFreightStop;
use App\Models\LanguageAirFreightStop;
use App;

class AirStopController extends Controller
{

    use MerchantTrait;

    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $air_stops = AirFreightStop::with("AirFreightServiceType")->where('merchant_id', $merchant_id)->latest()->paginate(25);
        $status = get_active_status("web", $string_file);
        return view('merchant.air_freight_stops.index', compact('air_stops', 'status'));
    }

    /**
     * Add form
     */
    public function add()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $pre_title = trans("$string_file.add");
        $submit_button = trans("$string_file.save");
        $title = $pre_title . ' ' . trans("$string_file.air_stops");
        $service_type = AirFreightServiceType::where('merchant_id',$merchant_id)->get();
        $return['air_stop'] = [
            'submit_url' => route('air_freight.save_air_stops'),
            'title' => $title,
            'arr_status' => add_blank_option(get_active_status("web", $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'service_type' => $service_type,
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.air_freight_stops.form')->with($return);
    }

    /**
     * Edit form
     */
    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = AirFreightStop::findorfail($id);
        $pre_title = trans("$string_file.edit");
        $submit_button = trans("$string_file.update");

        $title = $pre_title . ' ' . trans("$string_file.air_stops");
        $return['air_stop'] = [
            'data' => $data,
            'submit_url' => route('air_freight.update_air_stops', $id),
            'title' => $title,
            'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button
        ];
        return view('merchant.air_freight_stops.edit')->with($return);
    }

    /***
     * Save function
     */
    public function save(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'stop_name' => [
        'required', 
        'max:80',
        Rule::unique('language_air_freight_stops', 'name')
            ->where(function ($query) use ($merchant_id, $request) {
                return $query
                    ->where('merchant_id', $merchant_id)
                    ->where('locale', App::getLocale())
                    ->whereExists(function ($subquery) use ($request) {
                        $subquery->select(DB::raw(1))
                            ->from('air_freight_stops')
                            ->whereRaw('air_freight_stops.id = language_air_freight_stops.air_freight_stop_id')
                            ->where('air_freight_stops.air_service_type_id', $request->service_type_id);
                    });
            })
    ],
            'service_type_id' => 'required|exists:air_freight_service_types,id',
            'status' => 'required|between:1,2',
            'base_fare_amount'=> 'required'

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $service_type = AirFreightServiceType::find($request->service_type_id);
            $stop = new AirFreightStop;
            $stop->merchant_id = $merchant_id;
            $stop->segment_id = 170;
            $stop->air_service_type_id = $service_type->id;
            $stop->base_fare_amount = $request->base_fare_amount;
            $stop->status = $request->status;
            $stop->save();
            $this->SaveLanguageStops($merchant_id, $stop->id, $request->stop_name);
        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->route('air_freight.air_stops')->withSuccess(trans("$string_file.saved_successfully"));
    }

    /***
     * Update function
     */
    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'stop_name' => [
                'required', 'max:80',
                Rule::unique('language_air_freight_stops', 'name')
            ->where(function ($query) use ($merchant_id, $request,$id) {
                return $query
                    ->where('merchant_id', $merchant_id)
                    ->where('locale', App::getLocale())
                    ->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('air_freight_stop_id', '!=', $id);
                        }
                    })
                    ->whereExists(function ($subquery) use ($request) {
                        $subquery->select(DB::raw(1))
                            ->from('air_freight_stops')
                            ->whereRaw('air_freight_stops.id = language_air_freight_stops.air_freight_stop_id')
                            ->where('air_freight_stops.air_service_type_id', $request->service_type_id);
                    });
            })
            ],
            'status' => 'required|between:1,2',
            'base_fare_amount'=> 'required'

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $stop = AirFreightStop::Find($id);
            $stop->merchant_id = $merchant_id;
            $stop->base_fare_amount = $request->base_fare_amount;
            $stop->status = $request->status;
            $stop->save();
            $this->SaveLanguageStops($merchant_id, $stop->id, $request->stop_name);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->route('air_freight.air_stops')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]
        );
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        if ($merchant->demo == 1) {
            return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
        }
        $merchant_id = $merchant->id;
        $BusStop = BusStop::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $BusStop->status = $status;
        $BusStop->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }

    public function SaveLanguageStops($merchant_id, $id, $AirStopname)
    {
        LanguageAirFreightStop::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'air_freight_stop_id' => $id
        ], [
            'name' => $AirStopname,
        ]);
    }
}

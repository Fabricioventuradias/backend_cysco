<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fleet;
use App\Models\LanguageFleet;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Merchant;
use App\Models\Country;
use App;
use Illuminate\Validation\Rule;
use Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\CountryArea;
use App\Models\FleetDriver;
use App\Models\DriverVehicleDocument;
use App\Models\Onesignal;
use App\Models\DriverVehicle;
use App\Services\FleetManagement\FleetVehicleService;
use App\Models\DriverDocument;

class FleetCompanyController extends Controller
{
    use MerchantTrait;
    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $fleets = Fleet::where('merchant_id', $merchant_id)
            ->withSum('bookingTransactions', 'fleet_earning')
            ->paginate(25);
        return view('merchant.fleetcompany.index',compact('fleets'));
    }
    
    public function add(Request $request , $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $company = null;
        if(!empty($id))
        {
            $company = Fleet::Find($id);
        }
        $countryArea = CountryArea::where('merchant_id',$merchant_id)->get();
        $string_file = $this->getStringFile($merchant_id);
        return view('merchant.fleetcompany.create',compact('company','countryArea'));
    }
    
    public function save(Request $request , $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $rules = [
            'name' => 'required',
            'fleet_owner_name'=> 'required',
            'fleet_owner_email' => ['required',
                Rule::unique('fleets')->where(function($query)use($merchant_id,$id){
                    $query->where([['merchant_id','=',$merchant_id]]);
                    $query->where([['id','!=',$id]]);
                })],
            'fleet_owner_phone' => ['required',
                Rule::unique('fleets')->where(function($query)use($merchant_id,$id){
                    $query->where([['merchant_id','=',$merchant_id]]);
                    $query->where([['id','!=',$id]]);
                })],
            'password' => 'required_without:id',
            // 'company_logo' => 'required_without:id',
            'country_area_id' => 'required',
            'address' => 'required',
            'payment_type' => 'required|in:1,2,3',
        ];
        
        if (in_array($request->payment_type, [1, 2])) {
            $rules['commission_percentage'] = 'required|numeric|min:0|max:100';
        }
        if ($request->payment_type == 2) {
            $rules['default_driver_share_percentage'] = 'required|numeric|min:0|max:100';
        }
        if ($request->payment_type == 3) {
            $rules['rent_amount'] = 'required|numeric|min:0';
            $rules['rent_frequency'] = 'required|in:1,2';
        }
        
        $validator = Validator::make($request->all(), $rules);
        DB::beginTransaction();
        try
        {
        
            if(!empty($id))
            {
                $fleet = Fleet::find($id);
            }
            else
            {
                $fleet = new Fleet;
                $fleet->merchant_id = $merchant_id;
            }
            
            if($request->password){
                $password = Hash::make($request->password);
                $fleet->password = $password;
            }
            
            $fleet->fleet_owner_phone = $request->fleet_owner_phone;
            $fleet->fleet_owner_email = $request->fleet_owner_email;
            $fleet->country_area_id = $request->country_area_id;
            $fleet->payment_type = $request->payment_type;
            
            // Only persist fields relevant to selected model — null out the rest
            $fleet->commission_percentage = in_array($request->payment_type, [1, 2])
                ? $request->commission_percentage : null;
            
            $fleet->default_driver_share_percentage = $request->payment_type == 2
                ? $request->default_driver_share_percentage : null;
            
            $fleet->rent_amount = $request->payment_type == 3
                ? $request->rent_amount : null;
            
            $fleet->rent_frequency = $request->payment_type == 3
                ? $request->rent_frequency : null;
            
            $fleet->save();
            
            $this->SaveLanguageFleet($merchant_id,$fleet->id,$request->name,$request->address,$request->fleet_owner_name);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }
        // Commit Transaction
        DB::commit();
        $string_file = $this->getStringFile($merchant_id);
        return redirect()->route('merchant.fleet-company')->withSuccess(trans($string_file.".saved_successfully"));
        
    }
    
    public function SaveLanguageFleet($merchant_id, $fleetId,$name,$address,$fleet_owner_name)
    {
        LanguageFleet::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'fleet_id'=> $fleetId
        ], [
            'fleet_name' => $name,
            'address' => $address,
            'fleet_owner_name'=> $fleet_owner_name
        ]);
    }
    
    public function StatusUpdate(Request $request,$id)
    {
        $company = Fleet::find($id);
        $company->fleet_status = $request->status;
        $string_file = $this->getStringFile($company->merchant_id);
        $company->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }
    
    public function getPendingDoc(Request $request)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;

        // Show vehicles that have at least one pending doc
        $pending_vehicles = DriverVehicle::with([
            'Fleet',
            'Driver',
            'VehicleType',
            'DriverVehicleDocument' => function ($q) {
                $q->where('status', 1)->whereNotNull('document_id');
            },
            'DriverVehicleDocument.Document.LanguageSingle',
            'DriverVehicleDocument.Document.LanguageAny',
        ])
        ->whereHas('Fleet', function ($q) use ($merchant_id) {
            $q->where('merchant_id', $merchant_id);
        })
        ->whereHas('DriverVehicleDocument', function ($q) {
            $q->where('document_verification_status', 1) // pending
            ->where('status', 1);
        })
        ->paginate(15);

        return view('merchant.fleetcompany.vehicle_pending_docs', compact('pending_vehicles'));
    }

    public function getPendingDocDetail(Request $request, $driver_vehicle_id)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $driver_vehicle = DriverVehicle::with([
            'Fleet',
            'Driver',
            'VehicleType',
            'DriverVehicleDocument' => function ($q) {
                $q->where('status', 1)->whereNotNull('document_id');
            },
            'DriverVehicleDocument.Document.LanguageSingle',
            'DriverVehicleDocument.Document.LanguageAny',
        ])
        ->whereHas('Fleet', function ($q) use ($merchant_id) {
            $q->where('merchant_id', $merchant_id);
        })
        ->findOrFail($driver_vehicle_id);

        return view('merchant.fleetcompany.pending_docs_detail', compact('driver_vehicle'));
    }

    public function approveDoc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doc_id'             => 'required|exists:driver_vehicle_documents,id',
            'status'             => 'required|in:2,3',
            'driver_vehicle_id'  => 'required|exists:driver_vehicles,id',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->messages()->all()[0]);
        }
        
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $doc  = DriverVehicleDocument::findOrFail($request->doc_id);
        $doc->document_verification_status = $request->status;
        $doc->save();

        $driverVehicle = $doc->DriverVehicle;
        if ($driverVehicle && $driverVehicle->fleet_id) {
            app(FleetVehicleService::class)->checkAndUpdateFleetReadiness($driverVehicle);
        }
        $driver_id = $driverVehicle->driver_id;

        //sent notification
        setLocal($driverVehicle->Fleet->language);
        $string_file = $this->getStringFile($merchant_id);
        $msg = trans("$string_file.your")." ".trans("$string_file.document")." ".trans("$string_file.has")." ".trans("$string_file.been") ." ". trans("$string_file.rejected");
        $title = trans("$string_file.document") ." ". trans("$string_file.rejected");
        if($request->status == 2){
            $msg = trans("$string_file.your")." ".trans("$string_file.document")." ".trans("$string_file.has")." ".trans("$string_file.been") ." ". trans("$string_file.approved");
            $title = trans("$string_file.document") ." ". trans("$string_file.approved");
        }
        $data['notification_type'] = $request->status == 2 ? "DOCUMENT_APPROVED" : "DOCUMENT_REJECTED";
        $arr_param = ['fleet_id'=>$driverVehicle->fleet_id,'merchant_id'=> $merchant_id,'data' => $data, 'message' => $msg, 'title' => $title,'driver_id'=>$driver_id];
        Onesignal::FleetPushMessage($arr_param);
        
        return redirect()->route('fleetcompany.pending-doc', $request->driver_vehicle_id)->with('success', $msg);
    }
    
    public function getFleetPendingDriver(Request $request){
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        
        $fleetPendingDrivers = FleetDriver::with('Driver')
            ->whereHas('Driver', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
            })->where('fleet_signup_step','<=',2)->paginate(15);
            
        return view('merchant.fleetcompany.pending_driver_fleet', compact('fleetPendingDrivers'));
    }
    
    public function approveDriver($id,$status)
    {
        $fleetDriver = FleetDriver::with('Fleet','Driver')->find($id);
        $merchant_id = $fleetDriver->Fleet->merchant_id;
        $driver = $fleetDriver->Driver;
        if($status == 1){
            $fleetDriver->update([
                'fleet_signup_step' => 3, 
                'is_approved'       => 1,
            ]);
            
            //sent notification
            setLocal($fleetDriver->Fleet->language);
            $string_file = $this->getStringFile($merchant_id);
            $msg = trans("$string_file.your")." ".trans("$string_file.driver")." ".trans("$string_file.has")." ".trans("$string_file.been") ." ". trans("$string_file.approved");
            $title = trans("$string_file.driver") ." ". trans("$string_file.approved");
            $data['notification_type'] = "DRIVER_APPROVED";
            $arr_param = ['fleet_id'=>$fleetDriver->fleet_id,'merchant_id'=> $merchant_id,'data' => $data, 'message' => $msg, 'title' => $title,'driver_id'=>$fleetDriver?->driver_id];
            Onesignal::FleetPushMessage($arr_param);
            $status = get_driver_auto_verify_status($driver->id, 'final_status', 'doc');
            if($status == 1){
                DriverDocument::where('driver_id', $driver->id)->update(['document_verification_status' => 2]);
            }
            setLocal($fleetDriver->Driver->language);
            $string_file = $this->getStringFile($merchant_id);
            $msg = trans($string_file . '.driver') . ' ' . trans("$string_file.approved");
            $title = trans($string_file . '.driver') . ' ' . trans("$string_file.approved");
            $data1['notification_type'] = "DRIVER_APPROVED";
            $data1['segment_type'] = "";
            $data1['segment_data'] = [];
            $arr_param = ['driver_id' => $fleetDriver->driver_id, 'data' => $data1, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
            Onesignal::DriverPushMessage($arr_param);
        
            return redirect()->back()->with('success', 'Driver approved successfully.');
        }
        
    }
    
    public function rejectDriver(Request $request)
    {
        $id = $request->fleet_driver_id;
        $fleetDriver = FleetDriver::find($id);
        $merchant_id = $fleetDriver->Fleet->merchant_id;
        FleetDriver::findOrFail($id)->update([
            'is_approved'   => 2, //rejected
            'reject_reason' => $request->comment,
        ]);
        $driver = $fleetDriver->Driver;

        //sent notification
        setLocal($fleetDriver->Fleet->language);
        $string_file = $this->getStringFile($merchant_id);
        $string_file = $this->getStringFile($merchant_id);
        $msg = trans("$string_file.your")." ".trans("$string_file.driver")." ".trans("$string_file.has")." ".trans("$string_file.been") ." ". trans("$string_file.rejected");
        $title = trans("$string_file.driver") ." ". trans("$string_file.rejected");
        $data['notification_type'] = "DRIVER_REJECTED";
        $arr_param = ['fleet_id'=>$fleetDriver->fleet_id,'merchant_id'=> $merchant_id,'data' => $data, 'message' => $msg, 'title' => $title,'driver_id'=>$fleetDriver?->driver_id];
        Onesignal::FleetPushMessage($arr_param);
        
        $driver->signupStep = 3;
        $driver->save();
        $status = get_driver_auto_verify_status($driver->id, 'final_status', 'doc');
        if($status == 1){
            DriverDocument::whereIn('id', $request->document_id)->update(['document_verification_status' => 3]);
        }
        
        setLocal($driver->language);
        $string_file = $this->getStringFile($merchant_id);
        $msg = trans($string_file . '.driver') . ' ' . trans("$string_file.rejected");
        $title = trans($string_file . '.driver') . ' ' . trans("$string_file.rejected");
        $data1['notification_type'] = "DRIVER_REJECTED";
        $data1['segment_type'] = "";
        $data1['segment_data'] = [];
        $arr_param = ['driver_id' => $fleetDriver->driver_id, 'data' => $data1, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
        Onesignal::DriverPushMessage($arr_param);
    
        return redirect()->back()->with('error', 'Driver has been rejected.');
    }
    
    public function driverPersonalDoc($id,$fdriver_id){
        $fleetDriver = FleetDriver::find($fdriver_id);
        $merchant_id = get_merchant_id();
        $vehicle_details = NULL;
        $all_vehicle_details = NULL;
        $handyman_segment = NULL;
        $arr_segment = [];
        $arr_days = [];
        $rejectreasons = \App\Models\RejectReason::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])->get();
        $config = \App\Models\Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $driver = \App\Models\Driver::with(['DriverDocument' => function ($query) {
            $query->with('Document');
        }])->where('id', $id)->first();
        $driver_id = $driver->id;
        $driver_config = \App\Models\DriverRideConfig::select('latitude', 'longitude', 'radius')->where('driver_id', $driver->id)->first();
        $driver_wallet = DB::table('driver_wallet_transactions')->select(DB::raw('SUM(amount) as wallet_amount'))->where(['merchant_id' => $merchant_id, 'driver_id' => $driver->id])->first();
    
        $driver_add_vehicle_seatOrside_image_enable = isset($driver->Merchant->ApplicationConfiguration->driver_add_vehicle_seatOrside_image_enable) ? $driver->Merchant->ApplicationConfiguration->driver_add_vehicle_seatOrside_image_enable : 2;
        return view('merchant.fleetcompany.driver_pending_docs', compact('driver', 'rejectreasons', 'config', 'driver_wallet', 'driver_config','fleetDriver'));
    }
   
}

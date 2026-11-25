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

class AirFreightServiceTypeController extends Controller
{
    use ImageTrait,MerchantTrait;
    
    public function index(){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        $airfreightservices = AirFreightServiceType::where('merchant_id',$merchant_id)->paginate(10);
        
        return view('merchant.air_freight_service_type.index', compact('airfreightservices','merchant_id'));
    }
    
    public function add(Request $request,$id = NULL){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        if($id){
            $airfreightservices = AirFreightServiceType::where('id',$id)->where('merchant_id',$merchant_id)->first();
        }
    
        return view('merchant.air_freight_service_type.create', compact('airfreightservices','merchant_id'));
    }
    
    public function save(Request $request,$id = NULL){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        if($id){
            $airfreightservices = AirFreightServiceType::where('id',$id)->where('merchant_id',$merchant_id)->first();
        }
        
        if ($request->hasFile('air_service_type_image')) {
            $airfreightservices->service_type_image = $this->uploadImage('air_service_type_image', 'air_service_type_image', $merchant_id);
        }
        
        if($request->air_freight_type_name){
            $airfreightservices->air_freight_type_name = $request->air_freight_type_name;
        }
        if($request->air_service_type_name){ 
            $airfreightservices->air_service_type_name = $request->air_service_type_name;
        }
        $airfreightservices->save();
    
        return redirect()->back()->withSuccess("$string_file.saved_successfully");
    }
    
}
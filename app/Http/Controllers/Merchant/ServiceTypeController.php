<?php

namespace App\Http\Controllers\Merchant;

use App\Models\HandymanStore\HandymanStore;
use App\Models\InfoSetting;
use DB;
use App\Models\Merchant;
use App\Models\ServiceType;
use App;
use Auth;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\Segment;
use App\Models\ServiceTypeImage;


class ServiceTypeController extends Controller
{
    use ImageTrait, MerchantTrait;
    public function index()
    {
        $checkPermission =  check_permission(1, "view_service_types");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $merchant_segement_group_icons = Merchant::select("handyman_segement_group_icon")->where("id", $merchant_id)->first();
        $appConfig = $merchant->ApplicationConfiguration;
        $handyman_segment_creation = $merchant->Configuration->handyman_segment_creation ?? 2;
        $handyman_store_enable = $merchant->Configuration->handyman_store ?? 2;
        $segment_services = $this->getMerchantSegmentServices($merchant_id, 'service_type_screen',[1,3,4],[],NULL,false,[],NULL,'ALL',false,true);
        $handyman_segment_services = $this->getMerchantSegmentServices($merchant_id, 'service_type_screen',2);
        $arr_segment_group =   $this->segmentGroup($merchant_id,$return_type = "drop_down","");
        $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];
        // ADD THIS — fetch POS service types separately
        // $pos_service_types = [];
        // if (!empty($merchant->BookingConfiguration->pos_module_enable) && $merchant->BookingConfiguration->pos_module_enable == 1) {
        //     $pos_service_types = ServiceType::where('is_pos', 1)
        //         ->whereHas('Merchant', function ($q) use ($merchant_id) {
        //             $q->where('merchant_id', $merchant_id);
        //         })
        //         ->with(['ServiceTranslations' => function ($q) use ($merchant_id) {
        //             $q->where('merchant_id', $merchant_id);
        //         }])
        //         ->with(['Merchant' => function ($q) use ($merchant_id) {
        //             $q->where('merchant_id', $merchant_id);
        //         }])
        //         ->get();
        // }
        //    p($segment_services);
        $info_setting = InfoSetting::where('slug', 'SERVICE_TYPE_SETTINGS')->first();
        if (Schema::hasTable('handyman_stores')) {
            $handyman_stores = HandymanStore::where("merchant_id", $merchant_id)->get();
        } else {
            $handyman_stores = collect([]); // Empty collection
        }
        return view('merchant.service_types.index', compact('segment_services', 'info_setting','appConfig','merchant_segment_group', 'handyman_segment_services', 'merchant_segement_group_icons', 'merchant', 'handyman_segment_creation', 'handyman_stores','handyman_store_enable'));
    }

    // public function add(Request $request, $segment_id, $id = NULL)
    // {
    //     $checkPermission =  check_permission(1, "view_service_types");
    //     if ($checkPermission['isRedirect']) {
    //         return  $checkPermission['redirectBack'];
    //     }
    //     $service = '';
    //     if (empty($id) && !in_array($segment_id, array_keys(get_merchant_segment(false)))) {
    //         return redirect()->route('merchant.serviceType.index')->withErrors(trans('admin.trying_to_add_invalid_segment'));
    //     }
    //     $merchant = get_merchant_id(false);
    //     $appConfig = $merchant->ApplicationConfiguration;

    //     if (!empty($id)) {
    //         $locale = App::getLocale();
    //         $service = $merchant->ServiceType->where('id', $id)->first();
    //         $service_locale = '';
    //         $merchant_id = $merchant->id;
    //         $service_description = "";

    //         if (!empty($service->ServiceName($merchant_id))) {
    //             $service_locale = $service->ServiceName($merchant_id);
    //         }
    //         if (!empty($service->ServiceDescription($merchant_id))) {
    //             $service_description = $service->ServiceDescription($merchant_id);
    //         }
    //         $service->service_locale_name = !empty($service_locale) ? $service_locale : $service->serviceName;
    //         $service->service_locale_description = $service_description;
    //     }
    //     // dd($service);
    //     $arr_segment = get_merchant_segment();
    //     $segment = $arr_segment[$segment_id];
    //     return view('merchant.service_types.form', compact('service', 'segment_id', 'segment','appConfig'));
    // }

    public function add(Request $request, $segment_id, $id = NULL)
    {
        $checkPermission = check_permission(1, "view_service_types");
    
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
    
        if (empty($id) && !in_array($segment_id, array_keys(get_merchant_segment(false)))) {
            return redirect()
                ->route('merchant.serviceType.index')
                ->withErrors(trans('admin.trying_to_add_invalid_segment'));
        }
    
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $appConfig = $merchant->ApplicationConfiguration;
    
        $service = '';
    
        // ================= COMMON SERVICE DATA =================
        if (!empty($id)) {
    
            if ($merchant->BookingConfiguration->handyman_service_version == 1) {
    
                $service = ServiceType::with('images')
                            ->where('id', $id)
                            ->first();
    
            } else {
    
                $service = $merchant->ServiceType
                            ->where('id', $id)
                            ->first();
            }
    
            $service_locale = '';
            $service_description = '';
    
            if (!empty($service->ServiceName($merchant_id))) {
                $service_locale = $service->ServiceName($merchant_id);
            }
    
            if (!empty($service->ServiceDescription($merchant_id))) {
                $service_description = $service->ServiceDescription($merchant_id);
            }
    
            $service->service_locale_name = !empty($service_locale)
                ? $service_locale
                : $service->serviceName;
    
            $service->service_locale_description = $service_description;
        }
    
        // ================= COMMON SEGMENT DATA =================
        $arr_segment = get_merchant_segment();
        $segment = $arr_segment[$segment_id];
    
        // ================= CONDITIONAL PART =================
        if ($merchant->BookingConfiguration->handyman_service_version == 1) {
    
            $segment_group_id = Segment::where('id', $segment_id)
                                ->value('segment_group_id');
            return view(
                'merchant.service_types.newform',
                compact(
                    'service',
                    'segment_id',
                    'segment',
                    'appConfig',
                    'segment_group_id'
                )
            );
    
        } else {
    
            return view(
                'merchant.service_types.form',
                compact(
                    'service',
                    'segment_id',
                    'segment',
                    'appConfig'
                )
            );
        }
    }
    
    
    

    public function saveSegemtGroupIcon(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $request->validate([
            'handyman_grouping_icon' => 'required'
        ]);
        DB::beginTransaction();
        try {
            if ($request->hasFile("handyman_grouping_icon")) {
                $handyman_grouping_icon = $this->uploadImage('handyman_grouping_icon', 'segment_group_icons', $merchant_id);
                $merchant = Merchant::find($merchant_id);
                $merchant->handyman_segement_group_icon = $handyman_grouping_icon;
                $merchant->handyman_segement_group_name = $request->handyman_grouping_name;
                $merchant->save();
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            return redirect()->route('merchant.serviceType.index')->withErrors($message);
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    }



    // public function update(Request $request, $id = NULL)
    // {
    //     $checkPermission =  check_permission(1, "edit_service_types");
    //     if ($checkPermission['isRedirect']) {
    //         return  $checkPermission['redirectBack'];
    //     }
    //     $request->validate([
    //         'service' => 'required'
    //     ]);
    //     DB::beginTransaction();
    //     try {
    //         $merchant = get_merchant_id(false);
    //         $merchant_id = $merchant->id;

    //         $string_file = $this->getStringFile(NULL, $merchant);

    //         if (empty($id)) {
    //             $exist_service =   DB::table('service_types')->select('id', 'owner','serviceName')->where([
    //                 ['serviceName', '=', $request->service], ['segment_id', '=', $request->segment_id]
    //             ])->where(function ($q) use ($id) {
    //                 if (!empty($id)) {
    //                     $q->where('id', '!=', $id);
    //                 }
    //             })
    //                 ->first();

    //             if (!empty($exist_service->id)) {
    //                 $merchantService = DB::table('merchant_service_type')->where('service_type_id',$exist_service->id)
    //                 ->where('merchant_id',$merchant_id)
    //                 ->where('segment_id',$request->segment_id)->first();
                    
    //                 if(empty($merchantService)){
    //                     DB::table('merchant_service_type')->insert([
    //                         ['service_type_id' => $exist_service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
    //                     ]);
    //                 }else{
    //                     return redirect()->route('merchant.serviceType.index')->withErrors(trans("$string_file.service_type_duplicate"));
    //                 }
    //             }else{
    //                 $service = new ServiceType;
    //                 $service->serviceName = $request->service;
    //                 $service->segment_id = $request->segment_id;
    //                 $service->serviceStatus = 1;
    //                 $service->owner_id = $merchant_id;
    //                 $service->owner = 2; // service added by merchant
    //                 $service->save();
    //                 // insert row in pivot table
    //                 DB::table('merchant_service_type')->insert([
    //                     ['service_type_id' => $service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
    //                 ]);
    //             }
    //             //                $merchant->ServiceType()->attach($service->id,['segment_id'=>$request->segment_id,'sequence'=>$request->sequence]);
    //             //                p($service);
    //         }
    //         App\Models\ServiceTranslation::updateOrCreate([
    //             'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'service_type_id' => $id
    //         ], [
    //             'name' => $request->service,
    //             'description' => $request->description,
    //         ]);
    //         $insert_param['sequence'] = $request->sequence;
    //         if ($request->hasFile('icon')) {
    //             $insert_param['service_icon'] = $this->uploadImage('icon', 'service');
    //         }
    //         DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id], ['segment_id', '=', $request->segment_id]])->update($insert_param);
    //     } catch (\Exception $e) {
    //         $message = $e->getMessage();
    //         //  p($message);
    //         return redirect()->route('merchant.serviceType.index')->withErrors($message);
    //         // Rollback Transaction
    //         DB::rollback();
    //     }
    //     DB::commit();
    //     $this->clearSegmentServicesCache($merchant_id);
    //     return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    // }
    public function update(Request $request, $id = NULL)
    { 
        $checkPermission =  check_permission(1, "edit_service_types");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $request->validate([
            'service' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;

            $string_file = $this->getStringFile(NULL, $merchant);
            if ($merchant->BookingConfiguration->handyman_service_version == 1) 
            {
                if (!empty($id)) {
                    $exist_service =  DB::table('service_types')->where('id', $id)->first();
                    $service_typeex=ServiceType::find($id);
                    $service_typeex->price_type = isset($request->price_type)?$request->price_type:$exist_service->price_type;
                    $service_typeex->price_type_description= $request->price_type_description;
                    $service_typeex->included = $request->included 
                        ? json_encode(array_values(array_filter($request->included))) 
                        : null;
                    $service_typeex->excluded = $request->excluded 
                        ? json_encode(array_values(array_filter($request->excluded))) 
                        : null;
                    $service_typeex->estimated_time = $request->estimated_time;
                    $service_typeex->save();
                    if (!empty($request->hasFile('banners'))) {
                            $arr_list = $request->file('banners');
                            foreach ($arr_list as $image) {
                                $service_type_image = new ServiceTypeImage;
                                $service_type_image->service_type_id = $service_typeex->id;
                                $additional_req = ['compress' => true, 'custom_key' => 'product'];
                                $service_type_image->image = $this->uploadImage($image, 'service_type_image', $merchant_id, 'multiple',$additional_req);
                                $service_type_image->save();
                            }
                        }
                    if (!empty($exist_service->id)) {
                        $merchantService = DB::table('merchant_service_type')->where('service_type_id',$exist_service->id)
                        ->where('merchant_id',$merchant_id)
                        ->where('segment_id',$request->segment_id)->first();
                        
                        if ($request->hasFile('icon')) {
                            $service_icon = $this->uploadImage('icon', 'service');
                        }else{
                            $service_icon=$merchantService?->service_icon;
                        }
                        if(empty($merchantService)){
                            DB::table('merchant_service_type')->insert([
                                ['service_type_id' => $exist_service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence, 'service_icon'=>$service_icon]
                            ]);
                        }else{
                            DB::table('merchant_service_type')
                            ->where('service_type_id', $exist_service->id)
                            ->where('merchant_id', $merchant_id)
                            ->where('segment_id', $request->segment_id)
                            ->update([
                                'sequence'    => $request->sequence,
                                'service_icon' => $service_icon,
                            ]);
                        }
                        
                    }
                    
                   
                }
                else{
                        $service = new ServiceType;
                        $service->serviceName = $request->service;
                        $service->segment_id = $request->segment_id;
                        $service->serviceStatus = 1;
                        $service->owner_id = $merchant_id;
                        $service->price_type = $request->price_type;
                        $service->price_type_description= $request->price_type_description;
                        $service->excluded = $request->excluded 
                                ? json_encode(array_values(array_filter($request->excluded))) 
                                : null;
                        $service->included = $request->included 
                                ? json_encode(array_values(array_filter($request->included))) 
                                : null;
                        $service->estimated_time = $request->estimated_time;
                        $service->owner = 2; // service added by merchant
                        $service->save();
                        if ($request->hasFile('icon')) {
                            $service_icon = $this->uploadImage('icon', 'service');
                        }else{
                            $service_icon=$merchantService?->service_icon;
                        }
                        // insert row in pivot table
                        DB::table('merchant_service_type')->insert([
                            ['service_type_id' => $service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence,'service_icon'=>$service_icon]
                        ]);
                        if (!empty($request->hasFile('banners'))) {
                            $arr_list = $request->file('banners');
                            foreach ($arr_list as $image) {
                                $service_type_image = new ServiceTypeImage;
                                $service_type_image->service_type_id = $service->id;
                                $additional_req = ['compress' => true, 'custom_key' => 'product'];
                                $service_type_image->image = $this->uploadImage($image, 'service_type_image', $merchant_id, 'multiple',$additional_req);
                                $service_type_image->save();
                            }
                        }
                    }
                App\Models\ServiceTranslation::updateOrCreate([
                    'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'service_type_id' => $id
                ], [
                    'name' => $request->service,
                    'description' => $request->description,
                ]);
                $insert_param['sequence'] = $request->sequence;
                if ($request->hasFile('icon')) {
                    $insert_param['service_icon'] = $this->uploadImage('icon', 'service',$merchant_id, 'single');
                }
                DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id], ['segment_id', '=', $request->segment_id]])->whereNull('handyman_store_id')->update($insert_param);
            }else{
                $service_type_id=$id;
                if (empty($id)) {
                    $exist_service =   DB::table('service_types')->select('id', 'owner','serviceName')->where([
                        ['serviceName', '=', $request->service], ['segment_id', '=', $request->segment_id]
                    ])->where(function ($q) use ($id) {
                        if (!empty($id)) {
                            $q->where('id', '!=', $id);
                        }
                    })
                        ->first();
    
                    if (!empty($exist_service->id)) {
                        $merchantService = DB::table('merchant_service_type')->where('service_type_id',$exist_service->id)
                        ->where('merchant_id',$merchant_id)
                        ->where('segment_id',$request->segment_id)->first();
                        $service_type_id= $exist_service->id;
                        if(empty($merchantService)){
                            DB::table('merchant_service_type')->insert([
                                ['service_type_id' => $exist_service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
                            ]);
                        }else{
                            return redirect()->route('merchant.serviceType.index')->withErrors(trans("$string_file.service_type_duplicate"));
                        }
                    }else{
                        $service = new ServiceType;
                        $service->serviceName = $request->service;
                        $service->segment_id = $request->segment_id;
                        $service->serviceStatus = 1;
                        $service->owner_id = $merchant_id;
                        $service->owner = 2; // service added by merchant
                        $service->save();
                        // insert row in pivot table
                        DB::table('merchant_service_type')->insert([
                            ['service_type_id' => $service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
                        ]);
                        $service_type_id= $service->id;

                    }
                }
                App\Models\ServiceTranslation::updateOrCreate([
                    'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'service_type_id' => $service_type_id
                ], [
                    'name' => $request->service,
                    'description' => $request->description,
                ]);
                $insert_param['sequence'] = $request->sequence;
                if ($request->hasFile('icon')) {
                    $insert_param['service_icon'] = $this->uploadImage('icon', 'service');
                }
                DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type_id], ['segment_id', '=', $request->segment_id]])->update($insert_param);
                
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return redirect()->route('merchant.serviceType.index')->withErrors($message);
            DB::rollback();
        }
        DB::commit();

        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function editSegment($id)
    {
        $locale = App::getLocale();
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
//        if ($merchant->demo == 1) {
//            return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
//        }
        $segment = $merchant->Segment->where('id', $id)->first();
        //        p($segment);
        $segment->segment_locale_name = $segment->slag;
        $segment_locale = $segment->Name($merchant->id);
        if (!empty($segment_locale)) {
            $segment->segment_locale_name = $segment_locale;
        }
        return view('merchant.service_types.segment', compact('segment'));
    }

    public function updateSegment(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        if($merchant->id== 1340)
        {
            $string_file = $this->getStringFile(NULL, $merchant);
        // if ($merchant->demo == 1) {
        //     return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
        // }
        $merchant_id = $merchant->id;
        $request->validate([
            'segment' => 'required'
        ]);

        /* ══ segment must exist and be usable by this merchant ══ */
        $segment = Segment::where('id', $id)
                    ->where(function ($q) use ($merchant_id) {
                        $q->where(function ($qq) {
                            $qq->where('owner', 1)->whereNull('owner_id');
                        })->orWhere(function ($qq) use ($merchant_id) {
                            $qq->where('owner', 2)->where('owner_id', $merchant_id);
                        });
                    })
                    ->first();

        if (empty($segment)) {
            return redirect()->back()->withInput()->withErrors(trans("$string_file.something_went_wrong"));
        }

        DB::beginTransaction();
        try {
            App\Models\SegmentTranslation::updateOrCreate([
                'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'segment_id' => $id
            ], [
                'name' => $request->segment,
            ]);

            $arr_update = ['sequence' => $request->sequence, 'is_coming_soon' => (isset($request->is_coming_soon) && !empty($request->is_coming_soon)) ? $request->is_coming_soon : 2];

            /* ══ only overwrite when the field was submitted ══ */
            if ($request->has('sub_title')) {
                $arr_update['sub_title'] = $request->sub_title;
            }

            if ($request->hasFile('icon')) {
                $additional_req = ['compress' => true, 'custom_key' => 'segment'];
                $icon = $this->uploadImage('icon', 'segment', $merchant_id, 'single', $additional_req);
                $arr_update['segment_icon'] = $icon;
            }

            if($request->has('dynamic_url')){
                $arr_update['dynamic_url'] = $request->dynamic_url;
            }

            /* ══ checkbox — never write null ══ */
            $arr_update['is_popular'] = !empty($request->is_popular) ? $request->is_popular : 2;

            /* ══ self-heal — repair the row if it went missing ══ */
            $exists = DB::table('merchant_segment')
                        ->where('merchant_id', $merchant_id)
                        ->where('segment_id',  $id)
                        ->exists();

            if ($exists) {
                DB::table('merchant_segment')->where('merchant_id', $merchant_id)
                    ->where('segment_id', $id)->update($arr_update);
            } else {
                \Log::warning('merchant_segment row missing — recreated by updateSegment', [
                    'merchant_id' => $merchant_id,
                    'segment_id'  => $id,
                ]);

                DB::table('merchant_segment')->insert(array_merge($arr_update, [
                    'merchant_id' => $merchant_id,
                    'segment_id'  => $id,
                ]));
            }

            DB::commit();

        } catch (\Exception $e) {
            // Rollback Transaction
            DB::rollback();
            \Log::error('updateSegment failed', [
                'merchant_id' => $merchant_id,
                'segment_id'  => $id,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }

        try {
            $this->clearSegmentServicesCache($merchant_id);
        } catch (\Exception $e) {
            \Log::error('clearSegmentServicesCache failed: '.$e->getMessage());
        }

        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
            
        }else{
        $string_file = $this->getStringFile(NULL, $merchant);
//        if ($merchant->demo == 1) {
//            return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
//        }
        $merchant_id = $merchant->id;
        $request->validate([
            'segment' => 'required'
        ]);
        DB::beginTransaction();
        try {
            App\Models\SegmentTranslation::updateOrCreate([
                'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'segment_id' => $id
            ], [
                'name' => $request->segment,
            ]);
            $arr_update = ['sub_title' => $request->sub_title,'sequence' => $request->sequence, 'is_coming_soon' => (isset($request->is_coming_soon) && !empty($request->is_coming_soon)) ? $request->is_coming_soon : 2];
            if ($request->hasFile('icon')) {
                $additional_req = ['compress' => true, 'custom_key' => 'segment'];
                $icon = $this->uploadImage('icon', 'segment', $merchant_id, 'single', $additional_req);
                $arr_update['segment_icon'] = $icon;
            }
            if($request->dynamic_url){
                $arr_update['dynamic_url'] = $request->dynamic_url;
            }
            $arr_update['is_popular'] = $request->is_popular;
            DB::table('merchant_segment')->where('merchant_id', $merchant_id)
                ->where('segment_id', $id)->update($arr_update);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        $this->clearSegmentServicesCache($merchant_id);
        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
        }
    }

    public function serviceImageDelete($id){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $service = $merchant->ServiceType->where('id', $id)->first();
        if(isset($service['pivot']['service_icon'])){
            $insert_param['service_icon'] = NULL;
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id]])->update($insert_param);
            return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.image_removed_successfully"));
        }
    }

    public function changeStatus(Request $request, $id = null , $status = null)
    {
        $request->merge(['status'=>$status,'id'=>$id]);
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:service_types,id',
            'status' => 'integer|required|between:0,1'
        ],[
            'status.between' => trans('admin.invalid_status'),
            'id.exists' => trans('admin.cashback_addederror'),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors[0];
        }
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            $service = ServiceType::find($request->id);
            $status = $request->status == 1 ? 1 : 0;
            $insert_param = array("is_recommended" => $status);
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id], ['segment_id', '=', $service->segment_id]])->update($insert_param);
            return trans("$string_file.success");
        }catch (\Exception $exception){
            return $exception->getMessage();
        }
    }

    public function addSegment(){
        return view("merchant.segment.create");
    }

    public function saveSegment(Request $request)
    {
        $request->validate([
            'segment' => 'required|max:255',
            'icon'    => 'required',
        ]);
    
        $merchant    = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
    
        // if ($merchant->demo == 1) {
        //     return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
        // }
    
        /* ══ clean name — one pass, no double strtoupper ══ */
        $cleanseg     = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9]/', ' ', strtoupper($request->segment))));
        $cleansegment = str_replace(' ', '_', $cleanseg);
    
        if (empty($cleanseg)) {
            return redirect()->back()->withInput()->withErrors(trans("$string_file.something_went_wrong"));
        }
    
        $sequence = !empty($request->sequence) ? $request->sequence : 0;
    
        /* ══════════════════════════════════════════════════
         |  resolve segment — read only, no transaction yet
         |  exists() + first() replaced by a single first()
         ══════════════════════════════════════════════════ */
        $segment   = Segment::where('name', $cleanseg)->where('owner', 1)->whereNull('owner_id')->first();
        $is_global = !empty($segment);
    
        if (!$is_global) {
            $segment = Segment::where('name', $cleanseg)->where('owner', 2)->where('owner_id', $merchant->id)->first();
        }
    
        /* ══ duplicate check BEFORE the upload — no wasted file ══ */
        if (!empty($segment)) {
            $already_added = DB::table('merchant_segment')
                                ->where('merchant_id', $merchant->id)
                                ->where('segment_id',  $segment->id)
                                ->exists();
    
            if ($already_added) {
                return redirect()->back()->withInput()->withErrors(trans("$string_file.already_exists"));
            }
        }
    
        /* ══ upload outside the transaction — no row locks held
           while the file is written and compressed ══ */
        $segment_icon = "";
        if ($request->hasFile('icon')) {
            $segment_icon = $this->uploadImage('icon', 'segment', $merchant->id);
        }
    
        DB::beginTransaction();
        try {
    
            /* ══ create / refresh merchant-owned segment ══ */
            if (!$is_global) {
    
                if (empty($segment)) {
                    $segment = new App\Models\Segment();
                }
    
                $segment->name                 = $cleanseg;
                $segment->description          = $cleanseg;
                $segment->slag                 = $cleansegment;
                $segment->sub_group_for_app    = 5;
                $segment->sub_group_for_admin  = 3;
                $segment->segment_group_id     = 2;
                $segment->owner                = 2;
                $segment->owner_id             = $merchant->id;
                $segment->save();
    
                App\Models\SegmentTranslation::updateOrCreate([
                    'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'segment_id' => $segment->id
                ], [
                    'name' => $cleanseg,
                ]);
            }
    
            DB::table('merchant_segment')->insert([
                'sequence'       => $sequence,
                'is_coming_soon' => !empty($request->is_coming_soon) ? $request->is_coming_soon : 2,
                'merchant_id'    => $merchant->id,
                'segment_id'     => $segment->id,
                'segment_icon'   => $segment_icon,
                'dynamic_url'    => !empty($request->dynamic_url) ? $request->dynamic_url : NULL,
            ]);
    
            /* ══════════════════════════════════════════════════
             |  resolve service — shared base query, cloned
             ══════════════════════════════════════════════════ */
            $service_type_name = "Normal ".$cleanseg;
    
            $service_query = ServiceType::where('serviceName', $service_type_name)
                                ->where('segment_id', $segment->id);
    
            $service = NULL;
    
            if ($is_global) {
                $service = (clone $service_query)->whereNull('owner_id')->first();
    
                if (!empty($service) && $service->owner != 1) {
                    $service->owner = 1;
                    $service->save();
                }
            }
    
            if (empty($service)) {
                $service = (clone $service_query)->where('owner_id', $merchant->id)->first();
            }
    
            if (empty($service)) {
                $service = new ServiceType;
                $service->serviceName   = $service_type_name;
                $service->segment_id    = $segment->id;
                $service->serviceStatus = 1;
                $service->owner_id      = $is_global ? NULL : $merchant->id;
                $service->owner         = $is_global ? 1 : 2;
                $service->save();
            }
    
            DB::table('merchant_service_type')->updateOrInsert(
                [
                    'merchant_id'     => $merchant->id,
                    'service_type_id' => $service->id,
                    'segment_id'      => $segment->id,
                ],
                [
                    'sequence'     => $sequence,
                    'service_icon' => $segment_icon,
                ]
            );
    
            App\Models\ServiceTranslation::updateOrCreate([
                'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'service_type_id' => $service->id
            ], [
                'name'        => $service_type_name,
                'description' => $service_type_name,
            ]);
    
            DB::commit();
    
        } catch (\Exception $exception) {
            DB::rollback();
            \Log::error('saveSegment failed', [
                'merchant_id' => $merchant->id,
                'segment'     => $request->segment,
                'message'     => $exception->getMessage(),
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine(),
            ]);
            return redirect()->back()->withInput()->withErrors($exception->getMessage());
        }
    
        try {
            $this->clearSegmentServicesCache($merchant->id);
        } catch (\Exception $e) {
            \Log::error('clearSegmentServicesCache failed: '.$e->getMessage());
        }
    
        return redirect()->back()->withSuccess(trans("$string_file.segment")." ".trans("$string_file.added_successfully"));
    }
    // public function saveSegment(Request $request){
    //     $request->validate([
    //         // 'segment' => 'required|max:255|unique:segments,name',
    //         'icon' => 'required',
    //     ]);
    //     $merchant = get_merchant_id(false);
    //     $string_file = $this->getStringFile(NULL, $merchant);
    //     // if ($merchant->demo == 1) {
    //     //     return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
    //     // }
    //     DB::beginTransaction();
    //     try{
    //         $cleansegment = preg_replace('/[^A-Za-z0-9]/', ' ', strtoupper($request->segment));
    //         $cleanseg = preg_replace('/\s+/', ' ', $cleansegment);
    //         $cleansegment = str_replace(' ','_',strtoupper($cleanseg));
            
    //         $segmentData = Segment::where('name',$cleanseg)->exists();
    //         if($segmentData){
    //             $segment = Segment::where('name',$cleanseg)->first();
    //             $segment_icon = "";
    //             if ($request->hasFile('icon')) {
    //                 $segment_icon = $this->uploadImage('icon', 'segment', $merchant->id);
    //             }
    //         }else{
    //             $segment_icon = "";
    //             if ($request->hasFile('icon')) {
    //                 $segment_icon = $this->uploadImage('icon', 'segment', $merchant->id);
    //             }
                
    //             $segment = new App\Models\Segment();
    //             $segment->icon = $segment_icon;
    //             $segment->name = $cleanseg;
    //             $segment->description = $cleanseg;
    //             $segment->slag = $cleansegment;
    //             $segment->sub_group_for_app = 5;
    //             $segment->sub_group_for_admin = 3;
    //             $segment->segment_group_id = 2;
    //             $segment->owner = 2;
    //             $segment->owner_id = $merchant->id;
    //             $segment->save();

    //             App\Models\SegmentTranslation::updateOrCreate([
    //                 'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'segment_id' => $segment->id
    //             ], [
    //                 'name' => $cleanseg,
    //             ]);
    //         }

    //         $arr_create = [
    //             'sequence' => $request->sequence,
    //             'is_coming_soon' => $request->is_coming_soon,
    //             'merchant_id' => $merchant->id,
    //             'segment_id' => $segment->id,
    //             'segment_icon' => $segment_icon,
    //             'dynamic_url'=> $request->dynamic_url
    //         ];

    //         DB::table('merchant_segment')->insert($arr_create);

    //         $service_type_name = "Normal ".$cleanseg;

    //         $service = ServiceType::where('serviceName',$service_type_name)->first();
    //         if($service){
                
    //         }else{
    //             $service = new ServiceType;
    //             $service->serviceName = $service_type_name;
    //             $service->segment_id = $segment->id;
    //             $service->serviceStatus = 1;
    //             $service->owner_id = $merchant->id;
    //             $service->owner = 2; // service added by merchant
    //             $service->save();
    //         }
    //         // insert row in pivot table
    //         DB::table('merchant_service_type')->insert([
    //             ['service_type_id' => $service->id, 'merchant_id' => $merchant->id, 'segment_id' => $segment->id, 'sequence' => $request->sequence]
    //         ]);

    //         App\Models\ServiceTranslation::updateOrCreate([
    //             'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'service_type_id' => $service->id
    //         ], [
    //             'name' => $service_type_name,
    //             'description' => $service_type_name,
    //         ]);
    //         $insert_param['sequence'] = $request->sequence;
    //         if ($request->hasFile('icon')) {
    //             $insert_param['service_icon'] = $this->uploadImage('icon', 'service');
    //         }
    //         DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant->id], ['service_type_id', '=', $service->id], ['segment_id', '=', $segment->id]])->update($insert_param);

    //         DB::commit();
    //         return redirect()->back()->withSuccess(trans("$string_file.segment")." ".trans("$string_file.added_successfully"));
    //     }catch (\Exception $exception){
    //         DB::rollback();
    //         p($exception->getMessage());
    //     }
    // }


    public function assignToStore(Request $request)
    {
        $service = '';
        $already_assigned_to_store_names = [];
        if (!in_array($request->segment_id_to_assign, array_keys(get_merchant_segment(false)))) {
            return redirect()->route('merchant.serviceType.index')->withErrors(trans('admin.trying_to_add_invalid_segment'));
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $segment = Segment::find($request->segment_id_to_assign);

        $arr_stores = $request->stores;

        foreach ($arr_stores as $store){
            $arr_create = [
                'sequence' => $segment->sequence,
                'is_coming_soon' => $segment->is_coming_soon,
                'merchant_id' => $merchant->id,
                'handyman_store_id' => $store,
                'segment_id' => $segment->id,
                'segment_icon' => $segment->icon,
                'dynamic_url'=> NULL,
            ];

            $already_assigned = DB::table("merchant_segment")
                ->where([["handyman_store_id", $store], ["segment_id" , $segment->id], ["merchant_id", $merchant->id]])
                ->count();

            if($already_assigned > 0){
                $already_assigned_to_store_names[] = $store;
                continue;
            }
            DB::table('merchant_segment')->insert($arr_create);
        }

        if(count($already_assigned_to_store_names) > 0){
            $stores = HandymanStore::select("full_name")->whereIn("id", $already_assigned_to_store_names)->get();
            $names = "";
            foreach ($stores as $key => $value){
                $names .= $value->full_name;
                if(count($stores) != $key+1){
                    $names .=", ";
                }
            }
            return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"). " ".trans("$string_file.already_assigned").$names);
        }
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }
    public function updateImage(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $request->validate([
            'image_id' => 'required|exists:service_type_images,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $imageRow = ServiceTypeImage::find($request->image_id);
        if (!$imageRow) {
            return back()->with('error', 'Image not found');
        }
        // If new image uploaded
        if ($request->hasFile('image')) {
            $additional_req = [
                'compress' => true,
                'custom_key' => 'product'
            ];
            $imageRow->image = $this->uploadImage('image','service_type_image',$merchant->id,'single',$additional_req);
        }
        $imageRow->save();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }
    public function deleteImage(Request $request,$id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $imageRow = ServiceTypeImage::find($id);
        if (!$imageRow) {
            return back()->with('error', 'Image not found');
        }
        $imageRow->delete();
        return redirect()->back()->withSuccess(trans("$string_file.delete_successfully"));
    }
}

<?php

namespace App\Traits;
use App\Http\Controllers\Helper\Merchant as MerchantController;
use App\Models\Segment;
use App\Models\ServiceType;
use DB;
use App\Models\BannerManagement;
use App;
use App\Models\HandymanStore\HandymanStore;
use App\Models\HandymanStore\MerchantHandymanStoreSegment;
use App\Models\HandymanStore\HandymanStoreSubscriptionPurchase;

use App\Models\AdvertisementBanner;

trait HandymanStoreTrait{


    public function getAllHandymanStoreSegment($merchant_id, $call_from = '', $segment_group_id = NULL, $arr_segment = [], $country_area_id = NULL, $arr_segment_not_in = false, $handyman_store_id = NULL)
    {
        /* ── 1. Get assigned segment IDs from new table ── */
        $assigned_query = \App\Models\HandymanStore\MerchantHandymanStoreSegment::where('merchant_id', $merchant_id)
            ->where('status', 1)
            ->orderBy('sequence');
    
        if (!empty($handyman_store_id)) {
            $assigned_query->where('handyman_store_id', $handyman_store_id);
        }
    
        $assigned_records = $assigned_query->get()->keyBy('segment_id');
        $assigned_ids     = $assigned_records->keys()->toArray();
    
        if (empty($assigned_ids)) {
            return $call_from == 'api' ? collect() : [];
        }
    
        /* ── 2. Main segment query — same as old but use assigned IDs ── */
        $query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
            ->with(['Merchant' => function ($q) use ($merchant_id) {
                $q->where('id', $merchant_id)
                  ->select('id', 'sequence')
                  ->orderBy('sequence', 'ASC');
            }])
            ->with(['HandymanStore' => function ($q) {
                $q->select('id', 'sequence')
                  ->orderBy('sequence', 'ASC');
            }])
            ->whereIn('id', $assigned_ids);   // ← replaces merchant_segment join
    
        /* ── 3. Country area filter — same as old ── */
        if (!empty($country_area_id)) {
            $query->with(['ServiceType.CountryArea' => function ($qqq) use ($country_area_id) {
                        $qqq->where('country_area_id', $country_area_id);
                    }])
                  ->whereHas('ServiceType.CountryArea', function ($qqq) use ($country_area_id) {
                        $qqq->where('country_area_id', $country_area_id);
                  });
        }
    
        /* ── 4. Segment group filter — same as old ── */
        if (!empty($segment_group_id)) {
            if (is_array($segment_group_id)) {
                $query->whereIn('segment_group_id', $segment_group_id);
            } else {
                $query->where('segment_group_id', $segment_group_id);
            }
        }
    
        /* ── 5. Specific segment IDs filter — same as old ── */
        if (!empty($arr_segment)) {
            if ($arr_segment_not_in == true) {
                $query->whereNotIn('id', $arr_segment);
            } else {
                $query->whereIn('id', $arr_segment);
            }
        }
    
        $segment_services = $query->get();
    
        /* ── 6. Coming soon — same as old ── */
        if ($call_from == 'api' || $call_from == "service_type_screen") {
            $is_coming_query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
                ->with(['Merchant' => function ($q) use ($merchant_id) {
                    $q->where('id', $merchant_id)
                      ->select('id')
                      ->where('is_coming_soon', 1);
                }])
                ->with(['HandymanStore' => function ($q) {
                    $q->select('id')->where('is_coming_soon', 1);
                }])
                ->whereHas('HandymanStore', function ($q) {
                    $q->orderBy('sequence', 'ASC')->where('is_coming_soon', 1);
                })
                ->whereIn('id', $assigned_ids)  // ← scoped to assigned only
                ->get();
    
            $segment_services = $segment_services->merge($is_coming_query);
        }
    
        /* ── 7. Map — same as old + use assigned_records for pivot data ── */
        $arr_segment = $segment_services->map(function ($item) use (
            $merchant_id, $call_from, $country_area_id, $assigned_records
        ) {
            // ── pivot from MerchantHandymanStoreSegment — replaces HandymanStore[0]['pivot'] ──
            $assigned = $assigned_records[$item->id] ?? null;
    
            $name = $item->Name($merchant_id);
    
            return [
                'segment_id'     => $item->id,
                'slag'           => $item->slag,
                'is_coming_soon' => $assigned->is_coming_soon ?? 2,
                'name'           => !empty($name) ? $name : $item->Name(),
                'segment_icon'   => !empty($assigned->segment_icon)
                                        ? get_image($assigned->segment_icon, 'segment', $merchant_id, true)
                                        : get_image($item->icon, 'segment_super_admin', NULL, false),
                'dynamic_url'    => $assigned->dynamic_url ?? "",
                'sequence'       => $assigned->sequence    ?? 0,
            ];
        });
    
        /* ── 8. Sort by sequence from assigned table ── */
        $arr_segment = $arr_segment->sortBy('sequence')->values();
    
        if ($call_from == 'api') {
            return $arr_segment;
        } else {
            $arr_segment = $arr_segment->toArray();
            $arr_segment = array_column($arr_segment, NULL, 'segment_id');
            return $arr_segment;
        }
    }

 
    public function getHandymanStoreSegmentServices($merchant_id, $handyman_store_id, $call_from = '', $segment_group_id = NULL, $arr_segment = [], $country_area_id = NULL, $arr_segment_not_in = false, $arr_services = [], $sub_group_for_admin = NULL, $service_type = "ALL", $show_category = false)
    {
        /* ── 1. Get only assigned segment IDs from new table ── */
        $assigned_segment_ids = MerchantHandymanStoreSegment::where('handyman_store_id', $handyman_store_id)
            ->where('merchant_id', $merchant_id)
            ->where('status', 1)
            ->orderBy('sequence')
            ->pluck('segment_id')
            ->toArray();
    
        if (empty($assigned_segment_ids)) {
            return $call_from == 'api' ? collect() : [];
        }
    
        /* ── 2. Build segment query — same as old code but scoped to assigned IDs ── */
        $query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'owner_id', 'sub_group_for_admin')
            ->whereIn('id', $assigned_segment_ids)   // ← only assigned segments
            ->with(['Merchant' => function ($q) use ($merchant_id) {
                $q->where('id', $merchant_id)
                  ->select('id', 'sequence')
                  ->orderBy('sequence', 'ASC');
            }])
            ->with(['Handymanstoreservice' => function ($qq) use ($handyman_store_id, $arr_services) {
                $qq->where('handyman_store_id', $handyman_store_id);
                if (!empty($arr_services)) {
                    $qq->whereIn('service_type_id', $arr_services);
                }
            }]);
    
        /* ── 3. Country area filter — same as old code ── */
        if (!empty($country_area_id)) {
            $query->with(['ServiceType.CountryArea' => function ($qqq) use ($country_area_id) {
                        $qqq->where('country_area_id', $country_area_id);
                    }])
                  ->whereHas('ServiceType.CountryArea', function ($qqq) use ($country_area_id) {
                        $qqq->where('country_area_id', $country_area_id);
                  });
        }
    
        /* ── 4. Segment group filter ── */
        if (!empty($segment_group_id)) {
            if (is_array($segment_group_id)) {
                $query->whereIn('segment_group_id', $segment_group_id);
            } else {
                $query->where('segment_group_id', $segment_group_id);
            }
        }
    
        /* ── 5. Owner filter — same as old code ── */
        $query->where(function ($q) use ($merchant_id) {
            $q->where('owner_id', $merchant_id)
              ->orWhereNull('owner_id');
        });
    
        $segment_services = $query->get();
    
        /* ── 6. Coming soon segments — same as old code ── */
        if ($call_from == 'api' || $call_from == "service_type_screen") {
            $is_coming_query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin', 'owner_id')
                ->whereIn('id', $assigned_segment_ids)   // ← scoped to assigned only
                ->with(['Merchant' => function ($q) use ($merchant_id) {
                    $q->where('id', $merchant_id)
                      ->select('id')
                      ->where('is_coming_soon', 1);
                }])
                ->with(['HandymanStore' => function ($q) use ($handyman_store_id) {
                    $q->where('id', $handyman_store_id)
                      ->select('id')
                      ->where('is_coming_soon', 1);
                }])
                ->whereHas('HandymanStore', function ($q) use ($handyman_store_id) {
                    $q->where('handyman_store_id', $handyman_store_id)
                      ->orderBy('sequence', 'ASC')
                      ->where('is_coming_soon', 1);
                })
                ->orWhereHas('Merchant', function ($q) use ($merchant_id) {
                    $q->where('merchant_id', $merchant_id)
                      ->orderBy('sequence', 'ASC')
                      ->where('is_coming_soon', 1);
                })
                ->with(['ServiceType.Merchant' => function ($qq) use ($merchant_id, $arr_services) {
                    $qq->where('merchant_id', $merchant_id);
                    if (!empty($arr_services)) {
                        $qq->whereIn('id', $arr_services);
                    }
                }])
                ->whereHas('ServiceType.Merchant', function ($qqq) use ($merchant_id) {
                    $qqq->where('merchant_id', $merchant_id)
                        ->orderBy('sequence', 'ASC');
                })
                ->where('owner_id', $merchant_id)
                ->get();
    
            $segment_services = $segment_services->merge($is_coming_query);
        }
    
        /* ── 7. Map — same as old code + use MerchantHandymanStoreSegment for pivot data ── */
        // pre-index assigned records for O(1) pivot lookup
        $assigned_map = MerchantHandymanStoreSegment::where('handyman_store_id', $handyman_store_id)
            ->where('merchant_id', $merchant_id)
            ->where('status', 1)
            ->get()
            ->keyBy('segment_id');
    
        $arr_segment = $segment_services->map(function ($item) use (
            $merchant_id, $handyman_store_id, $call_from,
            $country_area_id, $service_type, $show_category, $assigned_map
        ) {
            // ── pivot from MerchantHandymanStoreSegment ──
            $assigned = $assigned_map[$item->id] ?? null;
    
            $name = $item->Name($merchant_id);
    
            return [
                'segment_id'       => $item->id,
                'slag'             => $item->slag,
    
                // ── is_coming_soon from assigned record ──
                'is_coming_soon'   => $assigned->is_coming_soon ?? 2,
    
                'sub_group_for_app'=> $item->sub_group_for_app,
                'segment_group_id' => $item->segment_group_id,
    
                // ── price_card_owner from assigned record ──
                'price_card_owner' => $assigned->price_card_owner ?? "",
    
                'name'             => !empty($name) ? $name : $item->slag,
    
                // ── segment_icon from assigned record, fallback to super admin ──
                'segment_icon'     => !empty($assigned->segment_icon)
                                        ? get_image($assigned->segment_icon, 'segment', $merchant_id, true)
                                        : get_image($item->icon, 'segment_super_admin', NULL, false),
    
                // ── services — same method as old code ──
                'arr_services'     => $this->handymanStoreServiceTypes(
                                        $item->Handymanstoreservice,
                                        $call_from,
                                        $merchant_id,
                                        $country_area_id,
                                        $service_type,
                                        $handyman_store_id
                                    ),
    
                'arr_categories'   => $item->slag == "TAXI" && $show_category
                                        ? $this->getTaxiCategory($merchant_id, $item->id, $country_area_id)
                                        : [],
    
                // ── dynamic_url from assigned record ──
                'dynamic_url'      => $assigned->dynamic_url ?? "",
    
                // ── sequence from assigned record for sorting ──
                'sequence'         => $assigned->sequence ?? 0,
            ];
        });
    
        /* ── 8. Sort by sequence from assigned table ── */
        $arr_segment = $arr_segment->sortBy('sequence')->values();
    
        if ($call_from == 'api') {
            return $arr_segment;
        } else {
            $arr_segment = $arr_segment->toArray();
            $arr_segment = array_column($arr_segment, NULL, 'segment_id');
            return $arr_segment;
        }
    }


    protected function handymanStoreServiceTypes($arr_data, $call_from, $merchant_id, $country_area_id = NULL, $service_type = "", $handyman_store_id = NULL)
    {
        $arr_service = [];
        $data_list =  [];
        $string_file = $this->getStringFile($merchant_id);
        foreach ($arr_data as $data) {
            
            if (isset($data->HandymanStore) && ($country_area_id  == NULL || (!empty($country_area_id) && $data->HandymanStore->CountryArea->count() > 0))) {
            // if (isset($data->HandymanStore) ) {
                
                if ($service_type == "ALL" || ($service_type == "SELF_PICKUP" && $data->type == 6) || ($service_type == "DELIVERY" && $data->type != 6) || (in_array($service_type, ["Intra-City", "Inter-City"]))) {
                    $sequanceData=$data->HandymanStoreServiceSequance($data->service_type_id,$data->handyman_store_id,$data->segment_id,$data->HandymanStore->merchant_id);
                    $serviceName = $data->serviceName;
                    $locale_service_name = $data->serviceName;
                    // $locale_service_description = $call_from != 'api' ? $data->ServiceDescription($merchant_id) : "";
                    // $locale_service_description = !empty($data->ServiceDescription($merchant_id)) ? $data->ServiceDescription($merchant_id) : "";
                    $data_list = array(
                        'id' => $data->id,
                        'segment_id' => $data->segment_id, // $data->serviceName
                        'service_type_id' => $data->service_type_id, // $data->serviceName
                        'serviceName' => $serviceName,
                        'service_sequence' => isset($sequanceData->sequence) ? $sequanceData->sequence : 0,
                        'service_is_recommended' => isset($data->Merchant[0]['pivot']) && $data->Merchant[0]['pivot']->is_recommended == 1 ? true : false,
                        'service_icon' =>  get_image($data->HandymanStoreService($data->service_type_id,$data->handyman_store_id),'service',$data->HandymanStore->merchant_id),
                        'locale_service_name' => !empty($locale_service_name) ? $locale_service_name : $serviceName, //!empty($data->ServiceName($merchant_id)) ? $data->ServiceName($merchant_id) : $data->serviceName,
                        // 'locale_service_description' => ($call_from != 'api' && !empty($locale_service_description)) ? $locale_service_description : "",
                        'locale_service_description' => (!empty($data->description)) ? $data->description : "",
                        'type' => $data->type,
                        'price_type'=>get_price_type($string_file,(int)$data->price_type),
                    );
                    if ($call_from == 'api') {
                        $arr_service[] = $data_list;
                    } else {
                        $arr_service[$data->id] = $data_list;
                    }
                }
            }
        }
        // sort the sequences of services
        // array_multisort(array_column($arr_service, 'service_sequence'), SORT_ASC, $arr_service);
        return $arr_service;
       
    }

    public function getMerchantHandymanStore($request)
    {
        $merchant_id = $request->merchant_id;
        $segment_id =  $request->segment_id;
        $is_popular = $request->is_popular;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;
        $locale = \App::getLocale();

        $user_id =  $request->user_id;
        $is_fav = $request->is_favourite;

        $arr_categories_id = [];
        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        // p($haversineSQL);
        $query = HandymanStore::select('id', 'country_area_id', 'full_name', 'country_id',  'minimum_amount', 'business_logo', 'latitude', 'longitude', 'rating','is_open')
        //     ->addSelect(DB::raw($haversineSQL . ' AS distance'))
        //     ->join("merchant_segment", "merchant_segment.handyman_store_id", "=", "handyman_stores.id")
            ->where([['handyman_stores.merchant_id', '=', $merchant_id], ['status', '=', 1]])
        // //    ->where(function ($q) use ($is_search, $search_text, $locale, $arr_categories_id) {
        // //    })
            ->where(function ($q) use ($is_popular) {
                if (!empty($is_popular) && $is_popular == "YES") {
                    $q->where('is_popular', 1);
                }
            })
            ->when(!empty($segment_id), function ($q) use ($segment_id) {
                $q->whereHas('HandymanStoreServices', function ($qq) use ($segment_id) {
                    $qq->where('segment_id', $segment_id)
                       ->where('type', '!=', 6);
                });
            });
            
        $query->with([
            'HandymanStoreServices' => function ($q) use ($segment_id) {
                $q->select('id', 'handyman_store_id', 'service_type_id', 'serviceName', 'segment_id', 'type')
                  ->when(!empty($segment_id), fn($qq) => $qq->where('segment_id', $segment_id))
                  ->where('type', '!=', 6)
                  ->latest()
                  ->limit(4);
            },
            'HandymanStoreServices.serviceType:id,serviceName', // fallback name
        ]);

        //     ->where('country_area_id', $request->area);
        //    if(empty($is_fav)){
        //        $query->where([["merchant_segment.segment_id", $segment_id]]);
        //    }
        //    $query->orderBy('distance');
            if (!empty($is_fav) && $is_fav == "YES") {
                $query->whereHas('FavouriteHandymanStore', function ($qq) use ($user_id) {
                    $qq->where([['user_id', '=', $user_id]]);
                });
            }
            if (!empty($is_fav) && $is_fav == "YES") {
                $query->distinct()->whereHas('FavouriteHandymanStore', function ($qq) use ($user_id) {
                    $qq->where([['user_id', '=', $user_id]]);
                });
            } else {
                // $query->where([["merchant_segment.segment_id", $segment_id]]);
            }
        if ($user_type != 1) {
            // $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }
        $arr_stores =  $query->paginate(10);
        return $arr_stores;
    }


    public function getMerchantAllHandymanStores($request)
    {
        $merchant_id = $request->merchant_id;
        $query = HandymanStore::select('id', 'country_area_id', 'full_name', 'country_id', 'business_logo', 'latitude', 'longitude')
            ->where([['handyman_stores.merchant_id', '=', $merchant_id]]);
        $arr_stores =  $query->paginate(10);
        return $arr_stores;
    }

    public function getHandymanStoreBanners($request, $filtered = true)
    {
        $query = AdvertisementBanner::with('HandymanStore')->select('name as banner_name','id','image as banner_images','handyman_store_id','redirect_url','is_deleted','merchant_id')
            ->where([
                ['handyman_store_id','=',$request->handyman_store_id],
                ['merchant_id','=',$request->merchant_id],
                ['status','=',1]
            ])
            ->orderBy('sequence')
            ->where('banner_for', 5)
            ->orderBy('updated_at')
            ->where('is_deleted', NULL)
            ->where(function ($q) use ($request) {
                if($request->home_screen == 1)
                {
                    $q->where('home_screen',1);
                }else{
                    $q->where('home_screen',"!=",1);
                }
                if(!empty($request->segment_id)){
                    $q->where('segment_id',$request->segment_id);
                }
            })
            ->where(function ($q) use ($request) {
                $q->where([['activate_date','=',NULL]]);
                $q->orWhere('activate_date','<=',date('Y-m-d'));
            })
            ->where(function ($q) use ($request) {
                $q->where([['expire_date','>=',date('Y-m-d')],['validity','=',2]]);
                $q->orWhere([['expire_date','=',NULL],['validity','=',1]]);

            });

            if($request->home_screen != 1){
                $query->orWhereNotNull('handyman_store_id');
            }
        $arr_banner = $query->get();
        $return_banners = [];
        if($filtered){
            $string_file = $this->getStringFile($request->merchant_id);
           $return_banners = $arr_banner
                ->filter(function ($item) {
                    return is_null($item->is_deleted) && !empty($item->id);
                })
                ->map(function ($item) use ($request, $string_file) {
                    return [
                        'id' => $item->id,
                        'handyman_store_id' => $item->handyman_store_id,
                        'title' => $item->banner_name,
                        'image' => get_image($item->banner_images, 'handyman_store_banners', $request->merchant_id, true, false),
                        'redirect_url' => $item->redirect_url,
                    ];
                })
                ->values(); // reindex array
        }else{
            $return_banners = $arr_banner;
        }
        return $return_banners;
    }

    public function getHandymanStoreSegmentsArray($store_id)
    {
        return Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
            // ->whereIn("sub_group_for_app", [1, 2])
            ->whereHas('HandymanStore', function ($q) use ($store_id) {
                $q->select('handyman_stores.id as handyman_store_id');
                $q->where('handyman_store_id', $store_id);
                $q->select('id', 'sequence');
                $q->orderBy('sequence', 'ASC');
                // $q->where('is_coming_soon', 2);
            })->get()->pluck("slag")->toArray();


    }

    public function getHandymanStoreCategories($handyman_store_id, $segment_id)
    {
        $arr = [];
        $arr_categories = App\Models\HandymanCategory::whereHas('ServiceTypes')->where([['handyman_store_id', '=', $handyman_store_id], ['segment_id', '=', $segment_id], ['status', '=', 1]])->get();
        if(!empty($arr_categories)){
            $arr =   $arr_categories->map(function ($category) use ($handyman_store_id) {
                return [
                    'category_id' => $category->id,
                    'name' => $category->Category,
                    'category_image' => get_image($category->icon,'category',$category->merchant_id),
                    'description' => $category->Description,
                    'services_count' => count($category->ServiceTypes)
                ];
            });
        }
        return $arr;
    }
    public function getStoreSegmentServices($request, $paginate = false)
    {
        $segment_id            = $request->segment_id;
        $handyman_store_id     = $request->handyman_store_id;
        $merchant_id           = $request->merchant_id;
        $segment_price_card_id = $request->segment_price_card_id;
        $country_area_id       = $request->area;

        $merchant_helper = new MerchantController();
        $string_file     = "";

        if (!empty($merchant_id)) {
            $merchant    = \App\Models\Merchant::select('string_file')->find($merchant_id);
            $string_file = $merchant->string_file ?? "";
        }

        $query = \App\Models\HandymanStore\HandymanStoreService::where('handyman_store_id', $handyman_store_id)
            ->where('type', '!=', 6)
            ->with([
                'serviceType' => function ($q) {
                    $q->select('id', 'serviceName', 'price_type');
                },
                'serviceType.SegmentPriceCardDetail' => function ($q) use ($segment_price_card_id, $handyman_store_id, $country_area_id) {
                    if (!empty($segment_price_card_id)) {
                        $q->where('segment_price_card_id', $segment_price_card_id);
                    }
                    $q->whereHas('SegmentPriceCard', function ($qq) use ($segment_price_card_id, $handyman_store_id, $country_area_id) {
                        $qq->where('handyman_store_id', $handyman_store_id);
                        if (!empty($segment_price_card_id)) {
                            $qq->where('id', $segment_price_card_id);
                        }
                        if (!empty($country_area_id)) {
                            $qq->where('country_area_id', $country_area_id);
                        }
                    });
                },
            ]);

        if (!empty($segment_id)) {
            $query->where('segment_id', $segment_id);
        }

        if (!empty($request->handyman_category_id)) {
            $query->where('handyman_category_id', $request->handyman_category_id);
        }

        /* ── Pagination ── */
        if ($paginate) {
            $per_page     = (int) $request->input('per_page', 10);
            $current_page = (int) $request->input('page', 1);
            $services     = $query->paginate($per_page, ['*'], 'page', $current_page);
            $items        = $services->items();
        } else {
            $services = $query->get();
            $items    = $services;
        }

        $arr_services = collect();

        if (count($items) > 0) {

            $user = $request->user('api');
            $data = hasActiveSubscription($user);
        
            /* ── fetch store once — reused in map, no N+1 ── */
            $store = \App\Models\HandymanStore\HandymanStore::select(
                        'id', 'latitude', 'longitude', 'address', 'full_name'
                     )->find($handyman_store_id);
        
            /* ── fetch ratings for all handyman_store_service_ids — single query ── */
            $store_service_ids = collect($items)->pluck('id')->toArray();
        
            $ratings_map = \App\Models\HandymanServiceRating::select(
                    'handyman_store_service_id',
                    \DB::raw('ROUND(AVG(rating), 1) as avg_rating'),
                    \DB::raw('COUNT(id) as review_count')
                )
                ->whereIn('handyman_store_service_id', $store_service_ids)
                ->where('order_type', 'store')
                ->groupBy('handyman_store_service_id')
                ->get()
                ->keyBy('handyman_store_service_id');
        
            $arr_services = collect($items)->map(function ($item) use (
                $merchant_id, $handyman_store_id, $merchant_helper,
                $string_file, $request, $data, $store, $ratings_map
            ) {
                $serviceType = $item->serviceType;
        
                /* ── guard priceDetail null ── */
                $priceDetail = !empty($serviceType)
                                ? ($serviceType->SegmentPriceCardDetail ?? null)
                                : null;
        
                /* ── safe amount read ── */
                $amount         = !empty($priceDetail) ? (float) ($priceDetail->amount ?? 0) : 0;
                $discountAmount = 0;
        
                /* ── subscription discount guard ── */
                if (!empty($data) && !empty($data->discount_type) && $amount > 0) {
                    if ($data->discount_type == 2) {
                        $discountAmount = ($amount * ($data->discount_percentage ?? 0)) / 100;
                    } elseif ($data->discount_type == 1) {
                        $discountAmount = (float) ($data->discount_amount ?? 0);
                    }
                }
        
                $finalAmount  = max(0, $amount - $discountAmount);
                $name         = !empty($item->serviceName)
                                    ? $item->serviceName
                                    : (!empty($serviceType) ? $serviceType->serviceName : '');
                $description  = $item->description ?? '';
                $price_type   = !empty($item->price_type)
                                    ? (int) $item->price_type
                                    : (!empty($serviceType) ? (int) $serviceType->price_type : 0);
                $included     = !empty($item->included) ? json_decode($item->included) : [];
                $excluded     = !empty($item->excluded) ? json_decode($item->excluded) : [];
                $serviceImage = $item->HandymanStoreService($item->service_type_id, $item->handyman_store_id);
                $pivot        = isset($serviceType->HandymanStore[0]['pivot'])
                                    ? $serviceType->HandymanStore[0]['pivot']
                                    : null;
        
                /* ── safe currency read ── */
                $currency = null;
                if (!empty($priceDetail) &&
                    !empty($priceDetail->SegmentPriceCard) &&
                    !empty($priceDetail->SegmentPriceCard->CountryArea) &&
                    !empty($priceDetail->SegmentPriceCard->CountryArea->Country)) {
                    $currency = $priceDetail->SegmentPriceCard->CountryArea->Country->isoCode ?? null;
                }
        
                /* ── safe amount strings ── */
                $amount_string   = $amount > 0
                                    ? (string) $merchant_helper->PriceFormat($amount, $merchant_id)
                                    : "0";
                $discount_string = $discountAmount > 0
                                    ? (string) $merchant_helper->PriceFormat($discountAmount, $merchant_id)
                                    : "0";
                $final_string    = $finalAmount > 0
                                    ? (string) $merchant_helper->PriceFormat($finalAmount, $merchant_id)
                                    : "0";
        
                /* ── dynamic rating — order_type = store, keyed by handyman_store_service_id ── */
                $rating_data  = $ratings_map[$item->id] ?? null;
                $avg_rating   = !empty($rating_data) ? (string) number_format($rating_data->avg_rating, 1)   : '0';
                $review_count = !empty($rating_data) ? (int)   $rating_data->review_count : 0;
        
                return [
                    'id'                           => $item->service_type_id,
                    'handyman_store_service_id'    => $item->id,
                    'name'                         => $name,
                    'amount'                       => $amount,
                    'amount_string'                => $amount_string,
                    'discount_amount'              => $discountAmount,
                    'discount_amount_string'       => $discount_string,
                    'final_amount'                 => $finalAmount,
                    'final_amount_string'          => $final_string,
                    'segment_price_card_detail_id' => !empty($priceDetail) ? $priceDetail->id : null,
                    'selected'                     => false,
                    'description'                  => $description,
                    'included'                     => $included,
                    'excluded'                     => $excluded,
                    'service_icon'                 => !empty($serviceImage)
                                                        ? get_image($serviceImage, 'service', $merchant_id)
                                                        : "",
                    'service_sequence'             => $pivot->sequence ?? 0,
                    'is_favroutie'                 => false,
        
                    // ── dynamic rating ──
                    'rating'                       => $avg_rating,
                    'reviewCount'                  => $review_count,
        
                    'type'                         => get_price_type($string_file, $price_type),
                    'price_type'                   => $price_type ?: null,
                    'currency'                     => $currency,
        
                    // ── store location ──
                    'store_lat'                    => $store->latitude  ?? null,
                    'store_long'                   => $store->longitude ?? null,
                    'store_address'                => $store->address   ?? "",
                ];
            });
        
            $arr_services = $arr_services->toArray();
            array_multisort(array_column($arr_services, 'service_sequence'), SORT_ASC, $arr_services);
        }

        /* ── Return with pagination meta ── */
        if ($paginate) {
            return [
                'data'       => $arr_services,
                'pagination' => [
                    'total'        => $services->total(),
                    'per_page'     => $services->perPage(),
                    'current_page' => $services->currentPage(),
                    'last_page'    => $services->lastPage(),
                    'from'         => $services->firstItem(),
                    'to'           => $services->lastItem(),
                ],
            ];
        }

        return $arr_services;
    }
    public function calculateSubscriptionDiscount($subscriptionData, float $amount): array
    {
        $discountAmount = 0;
    
        // ── guard against null — user may not have active subscription ──
        if (!empty($subscriptionData) && !empty($subscriptionData->discount_type)) {
            if ($subscriptionData->discount_type == 2) {
                $discountAmount = ($amount * ($subscriptionData->discount_percentage ?? 0)) / 100;
            } elseif ($subscriptionData->discount_type == 1) {
                $discountAmount = $subscriptionData->discount_amount ?? 0;
            }
        }
    
        $finalAmount = max(0, $amount - $discountAmount);
    
        return [
            'discount_amount' => (float) $discountAmount,
            'final_amount'    => (float) $finalAmount,
        ];
    }
    public function getHandymanStoreBookingStatus($req_param = [], $string_file = "")
    {
        if (isset($req_param['string_file'])) {
            $string_file = $req_param['string_file'];
        } else {
            $merchant_id = $req_param['merchant_id'];
            //            $string_file =  $this->getStringFile($merchant_id);
        }
        return array(
            '1' => trans("$string_file.pending"), //'Booking Pending',//Order placed
            '2' => trans("$string_file.booking_cancelled_by_user"), //
            '3' => trans("$string_file.rejected"),
            '4' => trans("$string_file.accepted"),
            '9' => trans("$string_file.arrived"),
            '5' => trans("$string_file.ride_cancelled_by_driver"), ////'Cancelled by Provider',
            '6' => trans("$string_file.started"), //'Booking Started ',
            '7' => trans("$string_file.completed"), //'Booking Finished',
            '8' => trans("$string_file.expired"), //'Booking Expired',
            '10' => trans("$string_file.dispute"),
            '11' => trans("$string_file.dispute_settled"),
            '12' => trans("$string_file.dispute_rejected"),
            '13' => trans("$string_file.pending"),                          // Order placed (store flow)
            '14' => trans("$string_file.order_accepted_by_store"),          // Accepted by store
            '15' => trans("$string_file.order_rejected_by_store"),          // Rejected by store
            '16' => trans("$string_file.order_assigned_to_provider"),       // Provider assigned
            '17' => trans("$string_file.order_processing_by_provider"),     // In progress
            '18' => trans("$string_file.order_completed_by_provider"),      // Completed by provider
        );
    }
    
    
    /**
     * Resolve segment price card — used in getStoreServices, getStoreServiceDetail, checkout
     */
    protected function resolveSegmentPriceCard($merchant_id, $handyman_store_id, $segment_id, $area)
    {
        return \App\Models\SegmentPriceCard::select('id', 'amount', 'price_type', 'minimum_booking_amount')
            ->where([
                ['merchant_id',       '=', $merchant_id],
                ['handyman_store_id', '=', $handyman_store_id],
                ['segment_id',        '=', $segment_id],
                ['country_area_id',   '=', $area],
                ['delete',            '=', NULL],
                ['status',            '=', 1],
            ])->first();
    }

    /**
     * Resolve tax from HandymanCommission — used in getStoreServices, getStoreServiceDetail, getCartDataApp
     */
    protected function resolveStoreTax($merchant_id, $handyman_store_id, $area, $segment_id): float
    {
        $commission = \App\Models\HandymanCommission::select('id', 'tax')
            ->where([
                ['merchant_id',       '=', $merchant_id],
                ['handyman_store_id', '=', $handyman_store_id],
                ['country_area_id',   '=', $area],
                ['segment_id',        '=', $segment_id],
            ])->first();

        return (!empty($commission->id) && !empty($commission->tax)) ? (float) $commission->tax : 0.0;
    }

    /**
     * Format slot time — used in getOrders, getOrderDetail
     */
    protected function formatSlotTime($slot, int $time_format): string
    {
        if (empty($slot)) return '';
        $fmt   = $time_format == 2 ? "H:i" : "h:i a";
        $start = date($fmt, strtotime($slot->from_time));
        $end   = date($fmt, strtotime($slot->to_time));
        return $start . '-' . $end;
    }

    /**
     * Build store_detail block — used in getStoreServiceDetail, getOrderDetail
     */
    protected function buildStoreDetail($handymanStore, $merchant_id): array
    {
        $rating_summary = \App\Models\HandymanStore\HandymanStoreRating::select(
                \DB::raw('COUNT(id) as total_ratings')
            )
            ->where('handyman_store_id', $handymanStore->id)
            ->first();
    
        /* ── latest 3 customer reviews ── */
        $customer_review = \App\Models\HandymanStore\HandymanStoreRating::with(
                'User:id,first_name,last_name,UserProfileImage'
            )
            ->where('handyman_store_id', $handymanStore->id)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'rating'     => (float) $r->rating,
                'review'     => $r->review ?? '',
                'user_name'  => trim(($r->User->first_name ?? '') . ' ' . ($r->User->last_name ?? '')),
                'user_image' => !empty($r->User->UserProfileImage)
                                    ? get_image($r->User->UserProfileImage, 'user', $merchant_id,true, true, 'user')
                                    : get_image(),
                'created_at' => $r->created_at->diffForHumans(),
            ])->toArray();
        return [
            'handyman_store_id' => $handymanStore->id,
            'title'             => $handymanStore->full_name,
            'image'             => !empty($handymanStore->business_logo)
                                    ? get_image($handymanStore->business_logo, 'handyman_store_logo', $merchant_id)
                                    : get_image(),
            'rating'            => !empty($handymanStore->rating) ? $handymanStore->rating : "2.5",
            'review_count'      => (int) ($rating_summary->total_ratings ?? 0),
            'customer_review'   => $customer_review,
            
            'store_lat'         => $handymanStore->latitude  ?? null,
            'store_long'        => $handymanStore->longitude ?? null,
            'store_address'     => $handymanStore->address   ?? "",
            'is_store_open'     => $handymanStore->is_open==1 ? true : false,
        ];
    }

    /**
     * Build bill_details block — used in getOrderDetail, HandymanStoreCheckoutResource
     */
    protected function buildBillDetails($cart_amount, $tax_amount, $discount_amount, $final_amount_paid, $string_file, $merchant_helper, $merchant_id, $format_price, $trip_calc_method): array
    {
        return [
            ['name' => trans("$string_file.cart_amount"),       'value' => $merchant_helper->PriceFormat($cart_amount,       $merchant_id, $format_price, $trip_calc_method), 'bold' => false],
            ['name' => trans("$string_file.tax_amount"),        'value' => $merchant_helper->PriceFormat($tax_amount,        $merchant_id, $format_price, $trip_calc_method), 'bold' => false],
            ['name' => trans("$string_file.discount_amount"),   'value' => $merchant_helper->PriceFormat($discount_amount,   $merchant_id, $format_price, $trip_calc_method), 'bold' => false],
            ['name' => trans("$string_file.final_amount_paid"), 'value' => $merchant_helper->PriceFormat($final_amount_paid, $merchant_id, $format_price, $trip_calc_method), 'bold' => true],
        ];
    }

    /**
     * Build service_details map — used in getOrders, getOrderDetail
     */
    protected function buildOrderServiceDetails($orderDetails, $subscriptionData, $currency, $merchant_id, $merchant_helper, $format_price, $trip_calc_method): array
    {
        return $orderDetails->map(function ($d) use (
            $subscriptionData, $currency, $merchant_id,
            $merchant_helper, $format_price, $trip_calc_method
        ) {
            $storeService = $d->HandymanStoreService;
            $serviceType  = $storeService->serviceType ?? null;

            // Name — same fallback chain as getStoreSegmentServices
            $name = !empty($storeService->serviceName)
                        ? $storeService->serviceName
                        : ($serviceType ? $serviceType->serviceName : '');

            $disc        = $this->calculateSubscriptionDiscount($subscriptionData, (float) $d->price);
            $disc_amount = $disc['discount_amount'];
            $final       = $disc['final_amount'];

            return [
                'service_type_id'           => $d->service_type_id,
                'handyman_store_service_id' => $d->handyman_store_service_id,
                'name'                      => $name,
                'quantity'                  => $d->quantity,
                'amount'                    => $d->price,
                'amount_string'             => $currency . ' ' . $merchant_helper->PriceFormat($d->price,       $merchant_id, $format_price, $trip_calc_method),
                'discount_amount'           => $disc_amount,
                'discount_amount_string'    => $currency . ' ' . $merchant_helper->PriceFormat($disc_amount,   $merchant_id, $format_price, $trip_calc_method),
                'final_amount'              => $final,
                'final_amount_string'       => $currency . ' ' . $merchant_helper->PriceFormat($final,         $merchant_id, $format_price, $trip_calc_method),
                'total_amount'              => $d->total_amount,
                'total_amount_string'       => $currency . ' ' . $merchant_helper->PriceFormat($d->total_amount, $merchant_id, $format_price, $trip_calc_method),
            ];
        })->toArray();
    }

    /**
     * Shared HandymanOrderDetail eager load — used in getOrders + getOrderDetail
     */
    protected function orderDetailWith(): array
    {
        return [
            'HandymanOrderDetail' => fn($q) => $q->select(
                'id', 'handyman_order_id', 'service_type_id',
                'handyman_store_service_id', 'segment_price_card_detail_id',
                'quantity', 'price', 'total_amount'
            ),
            'HandymanOrderDetail.HandymanStoreService:id,serviceName,service_type_id',
            'HandymanOrderDetail.HandymanStoreService.serviceType:id,serviceName',
        ];
    }
    public function getHandymanStoreNotification($status, $string_file)
    {
        $notifications = [
            '1'  => ['notification_type' => 'STORE_ORDER_PLACED',                 'title' => trans("$string_file.store_order_placed_title"),                    'message' => trans("$string_file.store_order_placed_message")],
            '2'  => ['notification_type' => 'STORE_ORDER_CANCELLED_BY_USER',      'title' => trans("$string_file.store_order_cancelled_by_user_title"),         'message' => trans("$string_file.store_order_cancelled_by_user_message")],
            '14' => ['notification_type' => 'STORE_ORDER_ACCEPTED_BY_STORE',      'title' => trans("$string_file.store_order_accepted_by_store_title"),         'message' => trans("$string_file.store_order_accepted_by_store_message")],
            '15' => ['notification_type' => 'STORE_ORDER_REJECTED_BY_STORE',      'title' => trans("$string_file.store_order_rejected_by_store_title"),         'message' => trans("$string_file.store_order_rejected_by_store_message")],
            '16' => ['notification_type' => 'STORE_ORDER_ASSIGNED_TO_PROVIDER',   'title' => trans("$string_file.store_order_assigned_to_provider_title"),      'message' => trans("$string_file.store_order_assigned_to_provider_message")],
            '17' => ['notification_type' => 'STORE_ORDER_PROCESSING_BY_PROVIDER', 'title' => trans("$string_file.store_order_processing_by_provider_title"),    'message' => trans("$string_file.store_order_processing_by_provider_message")],
            '18' => ['notification_type' => 'STORE_ORDER_COMPLETED_BY_PROVIDER',  'title' => trans("$string_file.store_order_completed_by_provider_title"),     'message' => trans("$string_file.store_order_completed_by_provider_message")],

            // store bidding
            
            'STORE_ACCEPTED_BID'      => ['notification_type' => 'STORE_ACCEPTED_BID',      'title' => trans("$string_file.store_accepted_bid_title"),      'message' => trans("$string_file.store_accepted_bid_message")],
            'STORE_REJECTED_BID'      => ['notification_type' => 'STORE_REJECTED_BID',      'title' => trans("$string_file.store_rejected_bid_title"),      'message' => trans("$string_file.store_rejected_bid_message")],
            'STORE_COUNTER_OFFER'     => ['notification_type' => 'STORE_COUNTER_OFFER',     'title' => trans("$string_file.store_counter_offer_title"),     'message' => trans("$string_file.store_counter_offer_message")],
        ];

        return $notifications[(string) $status] ?? [
            'notification_type' => 'STORE_ORDER_UPDATE',
            'title'             => trans("$string_file.store_order_update_title"),
            'message'           => trans("$string_file.store_order_update_message"),
        ];
    }
    
    public function updateHandymanStoreAverageRating(int $handyman_store_id): void
    {
        $avg = \App\Models\HandymanStore\HandymanStoreRating::where('handyman_store_id', $handyman_store_id)
                    ->avg('rating');
        HandymanStore::where('id', $handyman_store_id)
            ->update(['rating' => round($avg ?? 0, 1)]);
    }

    /* ══════════════════════════════════════════════════════
    |  CHECK SERVICE POST LIMIT — works for both store and driver
    ══════════════════════════════════════════════════════ */
    public function checkServicePostLimit(int $id, string $type = 'store'): array
    {
        /* ── query based on type ── */
        $query = HandymanStoreSubscriptionPurchase::with('Subscription')
                    ->where('subscriber_type', $type)
                    ->where('status', 1);

        if ($type === 'driver') {
            $query->where('driver_id', $id);
            $merchant_id = \App\Models\Driver::select('merchant_id')->find($id)->merchant_id ?? null;
        } else {
            $query->where('handyman_store_id', $id);
            $merchant_id = \App\Models\HandymanStore\HandymanStore::select('merchant_id')->find($id)->merchant_id ?? null;
        }

        $purchase = $query->latest()->first();

        if (empty($purchase)) {
            $default = \App\Models\HandymanStore\MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                            ->where('is_default', 1)
                            ->where('status', 1)
                            ->first();

            if (empty($default)) {
                return [
                    'can_post'     => true,
                    'remaining'    => PHP_INT_MAX,
                    'is_unlimited' => true,
                    'plan_name'    => '',
                ];
            }

            $is_unlimited = $default->isUnlimited();

            return [
                'can_post'     => true,
                'remaining'    => $is_unlimited ? PHP_INT_MAX : $default->no_of_service_post,
                'is_unlimited' => $is_unlimited,
                'plan_name'    => $default->subscription_name,
                'is_default'   => true,
            ];
        }

        $is_unlimited = $purchase->Subscription->isUnlimited();
        $remaining    = $is_unlimited
                            ? PHP_INT_MAX
                            : max(0, $purchase->no_of_service_post - $purchase->used_service_post);
         /* ── check expiry ── */
        $is_expired = !empty($purchase->end_date) &&
                  now()->toDateString() > $purchase->end_date;

        return [
            'can_post'     => $is_unlimited || $remaining > 0,
            'remaining'    => $remaining,
            'is_unlimited' => $is_unlimited,
            'plan_name'    => $purchase->Subscription->subscription_name ?? '',
            'is_default'   => false,
            'is_expired'   => $is_expired,
            'start_date'   => $purchase->start_date,
            'end_date'     => $purchase->end_date,
            'is_lifetime'  => empty($purchase->end_date),
        ];
    }

    /* ══════════════════════════════════════════════════════
    |  INCREMENT — works for both store and driver
    ══════════════════════════════════════════════════════ */
    public function incrementServicePostCount(int $id, string $type = 'store'): void
    {
        $query = HandymanStoreSubscriptionPurchase::where('subscriber_type', $type)
                    ->where('status', 1);

        if ($type === 'driver') {
            $query->where('driver_id', $id);
        } else {
            $query->where('handyman_store_id', $id);
        }

        $query->increment('used_service_post');
    }
    /* ══════════════════════════════════════════════════════
    |  HANDYMAN STORE WALLET CHECK BALANCE
    ══════════════════════════════════════════════════════ */
    public static function HandymanStoreCheckWalletBalance($handyman_store_id, $amount)
    {
        $store = \App\Models\HandymanStore\HandymanStore::select('id', 'wallet_money')
                    ->find($handyman_store_id);

        if (empty($store)) return false;

        return $store->wallet_money >= $amount;
    }

    /* ══════════════════════════════════════════════════════
    |  HANDYMAN STORE GET WALLET BALANCE
    ══════════════════════════════════════════════════════ */
    public static function HandymanStoreGetWalletBalance($handyman_store_id)
    {
        $store = \App\Models\HandymanStore\HandymanStore::select('id', 'wallet_money')
                    ->find($handyman_store_id);

        return (float) ($store->wallet_money ?? 0);
    }

    public function distributeHandymanStoreOrderAmount($order, $string_file): void
    {
        try {
            $final_amount = (float) $order->final_amount_paid;
            $merchant_id  = $order->merchant_id;

            /* ── 1. merchant commission — merchant level (no store) ── */
            $merchant_commission_row =$order->HandymanStore;

            $merchant_commission_method = 1;
            $merchant_commission_value  = 0;
            $company_earning            = 0;

            if (!empty($merchant_commission_row) && $merchant_commission_row->commission > 0) {
                $merchant_commission_method = (int)   $merchant_commission_row->commission_method;
                $merchant_commission_value  = (float) $merchant_commission_row->commission;

                if ($merchant_commission_method == 1) {
                    // flat
                    $company_earning = $merchant_commission_value;
                } else {
                    // percentage
                    $company_earning = round(($final_amount * $merchant_commission_value) / 100, 2);
                }
            }

            /* ── 2. driver commission — store-scoped ── */
            $driver_commission_row = \App\Models\HandymanCommission::where('merchant_id',       $merchant_id)
                                            ->where('segment_id',        $order->segment_id)
                                            ->where('country_area_id',   $order->country_area_id)
                                            ->where('handyman_store_id', $order->handyman_store_id)
                                            ->where('status', 1)
                                            ->first();

            $driver_commission_method = 1;
            $driver_commission_value  = 0;
            $driver_earning           = 0;
            $amount_after_merchant    = $final_amount - $company_earning;

            if (!empty($driver_commission_row) && $driver_commission_row->commission > 0 && !empty($order->driver_id)) {
                $driver_commission_method = (int)   $driver_commission_row->commission_method;
                $driver_commission_value  = (float) $driver_commission_row->commission;

                if ($driver_commission_method == 1) {
                    // flat
                    $driver_earning = $driver_commission_value;
                } else {
                    // percentage
                    $driver_earning = round(($amount_after_merchant * $driver_commission_value) / 100, 2);
                }
            }

            /* ── 3. store earning = remaining ── */
            $store_earning = round($final_amount - $company_earning - $driver_earning, 2);
            if ($store_earning < 0) $store_earning = 0;

            /* ── 4. payment type ── */
            $payment_method_type = $order->PaymentMethod->payment_method_type ?? 2;
            $cash_payment        = $payment_method_type == 1 ? $final_amount : 0;
            $online_payment      = $payment_method_type == 1 ? 0            : $final_amount;

            /* ── 5. save to booking_transactions — keyed on handyman_order_id ── */
            $transaction_data = [
                /* ── existing BookingTransaction fields ── */
                'date_time_details'              => date('Y-m-d H:i:s'),
                'sub_total_before_discount'      => $order->cart_amount     ?? 0,
                'discount_amount'                => $order->discount_amount ?? 0,
                'tax_amount'                     => $order->tax             ?? 0,
                'customer_paid_amount'           => $final_amount,
                'cash_payment'                   => $cash_payment,
                'online_payment'                 => $online_payment,
                'merchant_id'                    => $merchant_id,
                'company_earning'                => $company_earning,

                /* ── zero unused booking fields ── */
                'surge_amount'                   => 0,
                'extra_charges'                  => 0,
                'tip'                            => 0,
                'insurance_amount'               => 0,
                'cancellation_charge_received'   => 0,
                'cancellation_charge_applied'    => 0,
                'toll_amount'                    => 0,
                'booking_fee'                    => 0,
                'rounded_amount'                 => 0,
                'corporate_earning'              => 0,
                'booking_additional_charges'     => 0,
                'additional_mover_charges'       => 0,

                /* ── new store fields ── */
                'store_earning'                  => $store_earning,
                'driver_earning'                 => $driver_earning,
                'handyman_store_id'              => $order->handyman_store_id,
            ];

            /* ── updateOrCreate — keyed on handyman_order_id (NOT booking_id) ── */
            \App\Models\BookingTransaction::updateOrCreate(
                ['handyman_order_id' => $order->id],
                $transaction_data
            );

            /* ── 6. credit store wallet ── */
            if ($store_earning > 0) {
                \App\Http\Controllers\Helper\WalletTransaction::HandymanStoreWalletCredit([
                    'handyman_store_id' => $order->handyman_store_id,
                    'handyman_order_id' => $order->id,
                    'amount'            => $store_earning,
                    'narration'         => 'ORDER_COMPLETED',
                    'receipt'           => $order->merchant_order_id,
                    'description'       => 'Order #' . $order->merchant_order_id . ' — store earning',
                ]);
            }
            $merchant_commission_row->wallet_amount += $store_earning;
            $merchant_commission_row->save();

            /* ── 7. update order commission columns ── */
            // \App\Models\HandymanOrder::where('id', $order->id)->update([
            //     'merchant_commission'      => $company_earning,
            //     'driver_commission_amount' => $driver_earning,
            //     'store_commission_amount'  => $store_earning,
            // ]);

            // $paramArray = array(
            //                 'merchant_id' => $booking->merchant_id,
            //                 'driver_id' => $booking->driver_id,
            //                 'booking_id' => $booking->id,
            //                 'amount' => $cancel_charges,
            //                 'narration' => 14,
            //                 'platform' => 1,
            //                 'payment_method' => 2,
            //             );
            // WalletTransaction::WalletCredit($paramArray);
            // WalletTransaction::WalletDeduct($paramArray);
            // \Log::channel('debugger')->info('HandymanStore order distributed', [
            //     'order_id'        => $order->id,
            //     'final_amount'    => $final_amount,
            //     'company_earning' => $company_earning,
            //     'driver_earning'  => $driver_earning,
            //     'store_earning'   => $store_earning,
            // ]);

        } catch (\Exception $e) {
            \Log::error('HandymanStore distribution failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Merchant;


use App\Models\InfoSetting;
use App\Models\MapMarker;
use App\Traits\BookingTrait;
use Auth;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomMapMarker;
use App\Traits\ImageTrait;

class MapController extends Controller
{
    use BookingTrait,MerchantTrait,ImageTrait;

    public function HeatMap()
    {
        $checkPermission =  check_permission(1,'view_heat_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        // Areas configured for this merchant, for the service-area filter dropdown.
        // Same merchant_id scope used in getServiceAreaBookingStats().
        $merchant_areas = \App\Models\CountryArea::with(['LanguageSingle', 'LanguageAny'])
            ->where('merchant_id', $merchant->id)
            ->get()
            ->mapWithKeys(function ($area) {
                $name = $area->CountryAreaName;
                return [$area->id => $name];
            })->toArray();
        if (count($merchant_areas) > 1) {
            $merchant_areas = add_blank_option($merchant_areas, trans("$string_file.all")." ".trans("$string_file.area"));
        }
        $booking = $this->allBookings(false);
        $bookings = $booking->get(['pickup_latitude', 'pickup_longitude']);
        // p($bookings);
        $info_setting = InfoSetting::where('slug', 'HEAT_MAP')->first();
        return view('merchant.map.heat', compact('bookings','info_setting','merchant_segments','merchant_areas'));
    }

    public function getServiceAreaHeatmapPoints(Request $request)
    {
        $merchant_id = get_merchant_id();
        $segment_ids = $request->filled('segment_id') ? (array) $request->segment_id : [];
        $area_id     = $request->filled('area_id') ? $request->area_id : null;
    
        $segments = \App\Models\Segment::select('id', 'slag', 'segment_group_id', 'sub_group_for_app')
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
            })
            ->when(!empty($segment_ids), function ($q) use ($segment_ids) {
                $q->whereIn('id', $segment_ids);
            })
            ->get();
    
        $ride_ids      = [1, 2];
        $special_slags = ['LAUNDRY', 'CARPOOLING', 'BUS_BOOKING'];
    
        $rideIds    = $segments->whereIn('id', $ride_ids)->pluck('id')->toArray();
        $laundryIds = $segments->where('slag', 'LAUNDRY_OUTLET')->pluck('id')->toArray();
    
        $homeDeliveryIds = $segments->where('segment_group_id', 1)
            ->whereIn('sub_group_for_app', [1, 2])
            ->whereNotIn('id', $ride_ids)
            ->whereNotIn('slag', $special_slags)
            ->pluck('id')->toArray();
    
        $points = collect();
    
        // --- Taxi / Parcel Delivery: bookings.pickup_latitude / pickup_longitude ---
        if (!empty($rideIds)) {
            $bookingPoints = $this->allBookings(false)
                ->whereIn('bookings.segment_id', $rideIds)
                ->when($area_id, function ($q) use ($area_id) {
                    $q->where('bookings.country_area_id', $area_id);
                })
                ->reorder()
                ->whereNotNull('bookings.pickup_latitude')
                ->whereNotNull('bookings.pickup_longitude')
                ->select('bookings.pickup_latitude', 'bookings.pickup_longitude')
                ->get();
    
            foreach ($bookingPoints as $b) {
                $points->push([
                    'lat'          => (float) $b->pickup_latitude,
                    'lng'          => (float) $b->pickup_longitude,
                    'segment_type' => 'BOOKING',
                ]);
            }
        }
    
        // --- Home Delivery: orders.latitude / orders.longitude ---
        if (!empty($homeDeliveryIds)) {
            $orderPoints = \DB::table('orders')
                ->join('business_segments as bs', 'orders.business_segment_id', '=', 'bs.id')
                ->where('orders.merchant_id', $merchant_id)
                ->whereIn('orders.segment_id', $homeDeliveryIds)
                ->whereNotIn('orders.order_status', [2, 3, 5, 8, 12])
                ->when($area_id, function ($q) use ($area_id) {
                    $q->where('orders.country_area_id', $area_id);
                })
                ->whereNotNull('bs.latitude')
                ->whereNotNull('bs.longitude')
                ->select('bs.latitude', 'bs.longitude')
                ->get();
        
            foreach ($orderPoints as $o) {
                $points->push([
                    'lat'          => (float) $o->latitude,
                    'lng'          => (float) $o->longitude,
                    'segment_type' => 'ORDER',
                ]);
            }
        }
    
        // --- Laundry: laundry_outlet_orders joined to laundry_outlets for lat/lng ---
        if (!empty($laundryIds)) {
            $laundryPoints = \DB::table('laundry_outlet_orders as lo')
                ->join('laundry_outlets as outlet', 'lo.laundry_outlet_id', '=', 'outlet.id')
                ->where('lo.merchant_id', $merchant_id)
                ->whereIn('lo.segment_id', $laundryIds)
                ->whereNotIn('lo.order_status', [2, 3, 5, 8, 12])
                ->when($area_id, function ($q) use ($area_id) {
                    $q->where('lo.country_area_id', $area_id);
                })
                ->whereNotNull('outlet.latitude')
                ->whereNotNull('outlet.longitude')
                ->select('outlet.latitude', 'outlet.longitude')
                ->get();
    
            foreach ($laundryPoints as $l) {
                $points->push([
                    'lat'          => (float) $l->latitude,
                    'lng'          => (float) $l->longitude,
                    'segment_type' => 'LAUNDRY',
                ]);
            }
        }
    
        return response()->json($points->values());
    }
    public function DriverMap()
    {
        $checkPermission =  check_permission(1,'view_driver_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        return view('merchant.map.driver',compact('merchant_segments','info_setting'));
    }

    public function realTimeDriver()
    {
        $checkPermission =  check_permission(1,'view_driver_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        return view('merchant.map.realtime',compact('merchant_segments','info_setting'));
    }

    public function MapMarker(){
        $merchant = get_merchant_id(false);
//        $string_file = $this->getStringFile(NULL,$merchant);
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        $map_marker = MapMarker::where([['merchant_id','=',$merchant->id]])->first();
        return view('merchant.map.map_marker',compact('map_marker','info_setting'));
    }

    public function SaveMapMarker(Request $request, $id = NULL){
        $validator = Validator::make($request->all(),[
            'pickup_map_marker' => 'required',
            'drop_map_marker' => 'required',
        ]);

        if ($validator->fails()){
            $message = $validator->messages();
            return redirect()->back()->withErrors($message);
        }

        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        if (!empty($id)){
            $map_marker = MapMarker::find($id);
        }else{
            $map_marker = new MapMarker();
            $map_marker->merchant_id = $merchant_id;
        }

        $map_marker->pickup_map_marker = $request->pickup_map_marker;
        $map_marker->drop_map_marker = $request->drop_map_marker;
        $map_marker->status = $request->status;
        $map_marker->save();
        return redirect()->back()->with('success', trans("$string_file.success"));
    }

    public function IndexCustomMapMarker(){
        $merchant = get_merchant_id(false);
        $map_markers = CustomMapMarker::where([['merchant_id','=',$merchant->id]])->paginate(15);
        return view('merchant.map.custom_map_marker',compact('map_markers'));
    }
    
    public function addCustomMarker(){
        return view('merchant.map.add_custom_map_marker');
    }
    
    public function saveCustomMarker(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'map_marker_image' => 'required|image|mimes:png,jpg,jpeg,svg|dimensions:width=60,height=60',
        ]);

        if ($validator->fails()){
            $message = $validator->messages();
            return redirect()->back()->withErrors($message);
        }
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $map_marker = new CustomMapMarker();
        $map_marker->merchant_id = $merchant_id;
        $map_marker->name = $request->name;
        if($request->hasFile('map_marker_image')){
            $map_marker->marker_image = $this->uploadImage('map_marker_image','map_marker_image');
        }
        $map_marker->status = $request->status ?? 1;
        $map_marker->save();
        return redirect()->back()->with('success', trans("$string_file.success"));
    }
    public function deleteCustomMarker($id)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
    
        $map_marker = CustomMapMarker::where('id', $id)
            ->where('merchant_id', $merchant_id)
            ->first();
    
        if (!$map_marker) {
            return redirect()->back()->with('error', trans("$string_file.record_not_found"));
        }
    
        $map_marker->delete();
    
        return redirect()->back()->with('success', trans("$string_file.deleted_successfully"));
    }
}

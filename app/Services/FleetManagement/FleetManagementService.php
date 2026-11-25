<?php

namespace App\Services\FleetManagement;

use DB;
use Carbon\Carbon;
use App\Models\Fleet;
use App\Models\Driver;
use App\Models\Booking;
use App\Models\FleetDriver;
use App\Models\FleetVehicle;
use App\Models\CountryArea;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\HandymanOrder;
use App\Models\DriverVehicleAssignment;
use App\Models\BusinessSegment\Order;
use App\Models\LaundryOutlet\LaundryOutletOrder;
use App\Http\Controllers\Helper\CommonController;
use App\Models\FleetVehicleType;
use App\Models\FleetVehicleModel;
use App\Models\FleetVehicleMake;
use App\Models\DriverDriverVehicle;
use App\Models\AdvertisementBanner;
use App\Models\FleetDriverRent;
use App\Traits\MerchantTrait;

class FleetManagementService
{
    use MerchantTrait;
    // =====================================================
    // Fleet Dashboard
    // =====================================================
    public function getDashboardData($fleet, $merchant_id)
    {
        $fleet_id = $fleet->id;
    // ---- Get all driver IDs belonging to this fleet ----
    $driverIds = FleetDriver::where('fleet_id', $fleet_id)
        ->where('status', 1)
        ->pluck('driver_id')
        ->toArray();

    // ---- Get all vehicle IDs belonging to this fleet ----
    $totalVehicles = FleetVehicle::where('fleet_id', $fleet_id)
        ->where('vehicle_status', 1)
        ->count();

    // ---- Active Drivers ----
    $activeDrivers = Driver::whereIn('id', $driverIds)->count();
    
    // =====================================================
    // Unassigned Count — using same logic as getFleetVehicleDriver
    // =====================================================
    $activeAssignments = DriverVehicleAssignment::where('fleet_id', $fleet_id)
        ->whereNull('unassigned_at')
        ->get();

    $assignedVehicleIds = $activeAssignments->pluck('vehicle_id')->filter()->toArray();
    $assignedDriverIds  = $activeAssignments->pluck('driver_id')->filter()->toArray();

    // Vehicles with no active assignment
    $unassignedVehicleCount = FleetVehicle::where('fleet_id', $fleet_id)
        ->where('vehicle_status', 1)
        ->whereNotIn('id', $assignedVehicleIds)
        ->count();

    // Drivers with no active assignment
    $unassignedDriverCount = FleetDriver::where('fleet_id', $fleet_id)
        ->where('status', 1)
        ->where('fleet_signup_step',3)
        ->whereNotIn('driver_id', $assignedDriverIds)
        ->count();

    // ============================================
    // Today's Overview
    // ============================================
    
    // ---- Ongoing Trips ----
    $ongoingBookings = Booking::whereIn('driver_id', $driverIds)
        ->whereIn('booking_status', [1002, 1003, 1004, 1012])
        ->whereDate('created_at', Carbon::today())
        ->count();

    $ongoingOrders = Order::whereIn('driver_id', $driverIds)
        ->whereIn('order_status', [4, 6, 7, 9, 10])
        ->whereDate('created_at', Carbon::today())
        ->count();

    $ongoingHandyman = HandymanOrder::whereIn('driver_id', $driverIds)
        ->whereIn('order_status', [4, 6, 9])
        ->whereDate('created_at', Carbon::today())
        ->count();

    $ongoingLaundry = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        ->whereIn('order_status', [4, 6, 7, 9, 13, 15])
        ->whereDate('created_at', Carbon::today())
        ->count();

    $totalOngoing = $ongoingBookings + $ongoingOrders + $ongoingHandyman + $ongoingLaundry;

    // ---- Completed Today ----
    $completedBookings = Booking::whereIn('driver_id', $driverIds)
        ->where('booking_status', 1005)
        ->whereDate('created_at', Carbon::today())
        ->count();

    $completedOrders = Order::whereIn('driver_id', $driverIds)
        ->where('order_status', 11)
        ->whereDate('created_at', Carbon::today())
        ->count();

    $completedHandyman = HandymanOrder::whereIn('driver_id', $driverIds)
        ->where('order_status', 7)
        ->whereDate('created_at', Carbon::today())
        ->count();

    $completedLaundry = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        ->where('order_status', 14)
        ->whereDate('created_at', Carbon::today())
        ->count();

    $totalCompletedToday = $completedBookings + $completedOrders + $completedHandyman + $completedLaundry;

    // ============================================
    // Driver Status
    // ============================================
    $drivers        = Driver::whereIn('id', $driverIds)->select('id', 'online_offline', 'free_busy')->get();
    $onlineDrivers  = $drivers->where('online_offline', 1)->count();
    $offlineDrivers = $drivers->where('online_offline', 2)->count();
    $busyDrivers    = $drivers->where('online_offline', 1)->where('free_busy', 1)->count();
    $totalDrivers   = $drivers->count();

    // ============================================
    //Earnings from booking_transactions
    // ============================================

    // --- Get completed booking/order IDs ---
    $todayCompletedBookingIds = Booking::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('booking_status', 1005)
        ->whereDate('created_at', Carbon::today())
        ->pluck('id')->toArray();

    $weekCompletedBookingIds = Booking::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('booking_status', 1005)
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->pluck('id')->toArray();

    $todayCompletedOrderIds = Order::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 11)
        ->whereDate('created_at', Carbon::today())
        ->pluck('id')->toArray();

    $weekCompletedOrderIds = Order::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 11)
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->pluck('id')->toArray();

    $todayCompletedHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 7)
        ->whereDate('created_at', Carbon::today())
        ->pluck('id')->toArray();

    $weekCompletedHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 7)
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->pluck('id')->toArray();

    $todayCompletedLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 14)
        ->whereDate('created_at', Carbon::today())
        ->pluck('id')->toArray();

    $weekCompletedLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        ->where('fleet_id', $fleet_id)
        ->where('order_status', 14)
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->pluck('id')->toArray();

    // Today fleet_earning
    $todayBookingEarning  = !empty($todayCompletedBookingIds)
        ? \App\Models\BookingTransaction::whereIn('booking_id', $todayCompletedBookingIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $todayOrderEarning    = !empty($todayCompletedOrderIds)
        ? \App\Models\BookingTransaction::whereIn('order_id', $todayCompletedOrderIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $todayHandymanEarning = !empty($todayCompletedHandymanIds)
        ? \App\Models\BookingTransaction::whereIn('handyman_order_id', $todayCompletedHandymanIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $todayLaundryEarning  = !empty($todayCompletedLaundryIds)
        ? \App\Models\BookingTransaction::whereIn('laundry_outlet_order_id', $todayCompletedLaundryIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $todayTotalEarning = $todayBookingEarning + $todayOrderEarning + $todayHandymanEarning + $todayLaundryEarning;

    // Weekly fleet_earning
    $weekBookingEarning  = !empty($weekCompletedBookingIds)
        ? \App\Models\BookingTransaction::whereIn('booking_id', $weekCompletedBookingIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $weekOrderEarning    = !empty($weekCompletedOrderIds)
        ? \App\Models\BookingTransaction::whereIn('order_id', $weekCompletedOrderIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $weekHandymanEarning = !empty($weekCompletedHandymanIds)
        ? \App\Models\BookingTransaction::whereIn('handyman_order_id', $weekCompletedHandymanIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $weekLaundryEarning  = !empty($weekCompletedLaundryIds)
        ? \App\Models\BookingTransaction::whereIn('laundry_outlet_order_id', $weekCompletedLaundryIds)
            ->whereNotNull('fleet_earning')
            ->sum('fleet_earning')
        : 0;

    $weekTotalEarning = $weekBookingEarning + $weekOrderEarning + $weekHandymanEarning + $weekLaundryEarning;
    
    //advertisement banner

    $banners = AdvertisementBanner::where([['merchant_id', '=', $merchant_id],['banner_for', '=', '5'],])->get();


    $banners = $banners->map(function ($item) use ($merchant_id) {
        $image = get_image($item->image, 'banners', $merchant_id);

        return array(
            'id' => $item->id,
            'merchant_id' => $item->merchant_id,
            'segment_id' => $item->segment_id,
            'business_segment_id' => $item->business_segment_id,
            'laundry_outlet_id' => $item->laundry_outlet_id,
            'name' => $item->name,
            'image' => $image,
            'image_width' => !empty($item->image_width) ? $item->image_width : 1000,
            'image_height' => !empty($item->image_height) ? $item->image_height : 500,
            'action_type' => $item->action_type,
            'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
            'product_id' => $item->product_id,
            'category_id' => $item->category_id,
            'validity' => $item->validity,
            'home_screen' => $item->home_screen,
            'activate_date' => $item->activate_date,
            'expire_date' => $item->expire_date,
            'sequence' => $item->sequence,
            'status' => $item->status,
            'banner_for' => $item->banner_for,
            'home_screen_holder_id' => $item->home_screen_holder_id,
            'banner_width' => !empty($item->banner_width) ? $item->banner_width : 100,
        );
    })->values();
    
    $overdueRentsCount = FleetDriverRent::with('Driver')
            ->where('fleet_id', $fleet->id)
            ->where('status', 3)
            ->orderBy('due_date', 'desc')
            ->count();
    $total_overdue_count  = $overdueRentsCount;
    $total_overdue_amount = FleetDriverRent::where('fleet_id', $fleet->id)
                ->where('status', 3)->sum('amount');
                
    $scheduledRideTimezone = $fleet->CountryArea->timezone ?? 'UTC';
    $scheduledRideToday = Carbon::now($scheduledRideTimezone)->format('Y-m-d');

    $baseScheduledRideQuery = function () use ($fleet) {
        return Booking::where('booking_status', 1001)
            ->where('booking_type', 2)
            ->where('country_area_id', $fleet->country_area_id)
            ->whereNull('driver_id');
    };

    $todayScheduledRideCount = $baseScheduledRideQuery()
        ->where('later_booking_date', $scheduledRideToday)
        ->count();

    $upcomingScheduledRideCount = $baseScheduledRideQuery()
        ->where('later_booking_date', '>', $scheduledRideToday)
        ->count();
        
    $merchant_segment = $this->getMerchantSegmentDetails($fleet->merchant_id);
    $merchant_segment = $merchant_segment->reject(function ($segment) {
        return in_array($segment['slag'], ['FOOD', 'GROCERY']);
    })->values();

    return [
        // ---- Top Stats ----
        'total_vehicles' => $totalVehicles,
        'active_drivers' => $activeDrivers,
        'unassigned_vehicle_count'=> $unassignedVehicleCount,
        'unassigned_driver_count' => $unassignedDriverCount,

        // ---- Today's Overview ----
        'todays_overview' => [
            'ongoing_trips'      => $totalOngoing,
            'ongoing_breakdown'  => [
                'bookings' => $ongoingBookings,
                'orders'   => $ongoingOrders,
                'handyman' => $ongoingHandyman,
                'laundry'  => $ongoingLaundry,
            ],
            'completed_today'    => $totalCompletedToday,
            'completed_breakdown' => [
                'bookings' => $completedBookings,
                'orders'   => $completedOrders,
                'handyman' => $completedHandyman,
                'laundry'  => $completedLaundry,
            ],
        ],

        // ---- Driver Status ----
        'driver_status' => [
            'total'   => $totalDrivers,
            'online'  => $onlineDrivers,
            'offline' => $offlineDrivers,
            'busy'    => $busyDrivers,
        ],
        'banners' => $banners,

        // Earnings
        'earnings' => [
            'today' => [
                'period'        => Carbon::today()->format('d M Y'),
                'total_earning' => round($todayTotalEarning, 2),
                'trips'         => $totalCompletedToday,
                'breakdown'     => [
                    'bookings' => round($todayBookingEarning,  2),
                    'orders'   => round($todayOrderEarning,    2),
                    'handyman' => round($todayHandymanEarning, 2),
                    'laundry'  => round($todayLaundryEarning,  2),
                ],
            ],
            'weekly' => [
                'period' => [
                    'from' => Carbon::now()->startOfWeek()->format('d M Y'),
                    'to'   => Carbon::now()->endOfWeek()->format('d M Y'),
                ],
                'total_earning' => round($weekTotalEarning, 2),
                'trips'         => $completedBookings + $completedOrders + $completedHandyman + $completedLaundry,
                'breakdown'     => [
                    'bookings' => round($weekBookingEarning,  2),
                    'orders'   => round($weekOrderEarning,    2),
                    'handyman' => round($weekHandymanEarning, 2),
                    'laundry'  => round($weekLaundryEarning,  2),
                ],
            ],
        ],
        'overdues'=> [
            'total_overdue_count'=> (string)$total_overdue_count,
            'total_overdue_amount'=>(string)$total_overdue_amount
        ],
        'today_scheduled_ride_count' => $todayScheduledRideCount,
        'upcoming_scheduled_ride_count' => $upcomingScheduledRideCount,
        'merchant_segment'=> $merchant_segment,
        'currency'=> $fleet->CountryArea->Country->isoCode
    ];
}

    public function getFleetLiveRides(int $fleetId)
    {
        // Get all driver IDs in this fleet
        $driverIds = FleetDriver::where('fleet_id', $fleetId)
            ->where('status', 1)
            ->pluck('driver_id')
            ->toArray();
    
        if (empty($driverIds)) {
            return $this->emptyLiveRidesResponse();
        }
    
        // Pre-load driver map
        $driversMap = Driver::whereIn('id', $driverIds)
            ->select('id', 'first_name', 'last_name', 'phoneNumber', 'online_offline', 'free_busy', 'profile_image','current_latitude','current_longitude')
            ->get()
            ->keyBy('id');
    
        // =====================================================
        // Ongoing Status Groups
        // =====================================================
        $bookingOngoing  = [1002, 1003, 1004, 1012];
        $orderOngoing    = [4, 6, 7, 9, 10];
        $handymanOngoing = [4, 6, 9];
        $laundryOngoing  = [4, 6, 7, 9, 13, 15];
    
        // =====================================================
        // Fetch Ongoing Bookings (Taxi/Delivery)
        // =====================================================
        $ongoingBookings = Booking::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleetId)
            ->whereIn('booking_status', $bookingOngoing)
            ->with([
                'User:id,first_name,last_name,UserPhone',
                'ServiceType:id',
                'CountryArea:id,timezone',
            ])
            ->select('id','driver_id','user_id','service_type_id','booking_status','merchant_booking_id','payment_method_id',
                'pickup_location','drop_location','pickup_latitude','pickup_longitude','drop_latitude', 'drop_longitude','estimate_bill','final_amount_paid','created_at','driver_vehicle_id','segment_id','country_area_id','merchant_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) use ($driversMap) {
                $driver = $driversMap[$b->driver_id] ?? null;
                
                $timezone = $b->countryArea->timezone ?? 'UTC';        // ← resolve tz
                $formatted = $b->created_at
                    ? \Carbon\Carbon::parse($b->created_at)
                        ->setTimezone($timezone)
                        ->format('d M Y, h:i A')
                    : null;
                    
                return [
                    'ride_id'          => $b->merchant_booking_id,
                    'type'             => 'BOOKING',
                    'type_label'       => 'Ride',
                    'Segment'          => $b->Segment->Name(),
                    'status'           => $b->booking_status,
                    'status_label'     => $this->bookingStatusLabel($b->booking_status),
                    // 'service_type'     => $b->ServiceType->ServiceName($b->merchant_id) ?? null,
    
                    // Driver info
                    'driver'           => [
                        'id'            => $driver->id            ?? null,
                        'name'          => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
                        'phone'         => $driver->phoneNumber   ?? null,
                        'profile_pic'   => $driver->profile_image ?? null,
                        'online_offline'=> $driver->online_offline ?? null,
                        'free_busy'     => $driver->free_busy     ?? null,
                        'current_latitude'     => $driver->current_latitude     ?? null,
                        'current_longitude'     => $driver->current_longitude     ?? null,
                    ],
    
                    // User/Customer info
                    'customer'         => [
                        'id'    => $b->User->id ?? null,
                        'name'  => $b->User ? $b->User->first_name . ' ' . $b->User->last_name : 'N/A',
                        'phone' => $b->User->UserPhone ?? null,
                    ],
    
                    // Location info
                    'pickup'           => $b->pickup_location  ?? null,
                    'drop'             => $b->drop_location    ?? null,
                    'pickup_lat'       => $b->pickup_latitude  ?? null,
                    'pickup_lng'       => $b->pickup_longitude ?? null,
                    'drop_lat'         => $b->drop_latitude    ?? null,
                    'drop_lng'         => $b->drop_longitude   ?? null,
    
                    // Fare info
                    'estimate_bill'   => $b->estimate_bill ? number_format($b->estimate_bill,2)  : 0 ,
                    'final_amount'     => $b->final_amount_paid ? number_format($b->final_amount_paid,2)  : 0 ,
                    'payment_method'    => $b->PaymentMethod->MethodName($b->merchant_id),
                    'started_at'       => $formatted,
                    'driver_vehicle_id'=> $b->driver_vehicle_id ?? null,
                ];
            });
    
        // =====================================================
        // Fetch Ongoing Orders (Food/Delivery/Grocery)
        // =====================================================
        $ongoingOrders = collect([]);
        // $ongoingOrders = Order::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->whereIn('order_status', $orderOngoing)
        //     ->with([
        //         'User:id,first_name,last_name,UserPhone',
        //         'ServiceType:id',
        //     ])
        //     ->select('id','driver_id','user_id','service_type_id','order_status','segment_id','final_amount_paid','created_at','driver_vehicle_id')
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($o) use ($driversMap) {
        //         $driver    = $driversMap[$o->driver_id] ?? null;
        //         $typeLabel = $this->segmentLabel($o->segment_id);
    
        //         return [
        //             'ride_id'          => $o->id,
        //             'type'             => 'ORDER',
        //             'type_label'       => $typeLabel,
        //             'status'           => $o->order_status,
        //             'status_label'     => $this->orderStatusLabel($o->order_status),
        //             // 'service_type'     => $o->ServiceName($b->merchant_id) ?? null,
    
        //             'driver'           => [
        //                 'id'            => $driver->id            ?? null,
        //                 'name'          => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //                 'phone'         => $driver->phoneNumber   ?? null,
        //                 'profile_pic'   => $driver->profile_image ?? null,
        //                 'online_offline'=> $driver->online_offline ?? null,
        //                 'free_busy'     => $driver->free_busy     ?? null,
        //             ],
    
        //             'customer'         => [
        //                 'id'    => $o->User->id ?? null,
        //                 'name'  => $o->User ? $o->User->first_name . ' ' . $o->User->last_name : 'N/A',
        //                 'phone' => $o->User->UserPhone ?? null,
        //             ],
    
        //             'pickup'           => null,
        //             'drop'             => null,
        //             'pickup_lat'       => null,
        //             'pickup_lng'       => null,
        //             'drop_lat'         => null,
        //             'drop_lng'         => null,
    
        //             'estimate_bill'   => 0,
        //             'final_amount'     => $o->final_amount_paid ? number_format($o->final_amount_paid,2) : 0,
    
        //             'started_at'       => $o->created_at ? $o->created_at->format('d M Y, h:i A') : null,
        //             'driver_vehicle_id'=> $o->driver_vehicle_id ?? null,
        //         ];
        //     });
    
        // =====================================================
        // Fetch Ongoing Handyman Orders
        // =====================================================
        $ongoingHandyman = collect([]);
        // $ongoingHandyman = HandymanOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->whereIn('order_status', $handymanOngoing)
        //     ->with(['User:id,first_name,last_name,UserPhone'])
        //     ->select('id','driver_id','user_id','order_status','segment_id','final_amount_paid','created_at')
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($h) use ($driversMap) {
        //         $driver    = $driversMap[$h->driver_id] ?? null;
        //         $typeLabel = $this->segmentLabel($h->segment_id);
    
        //         return [
        //             'ride_id'          => $h->id,
        //             'type'             => 'HANDYMAN',
        //             'type_label'       => $typeLabel,
        //             'status'           => $h->order_status,
        //             'status_label'     => $this->handymanStatusLabel($h->order_status),
        //             // 'service_type'     => null,
    
        //             'driver'           => [
        //                 'id'            => $driver->id            ?? null,
        //                 'name'          => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //                 'phone'         => $driver->phoneNumber   ?? null,
        //                 'profile_pic'   => $driver->profile_image ?? null,
        //                 'online_offline'=> $driver->online_offline ?? null,
        //                 'free_busy'     => $driver->free_busy     ?? null,
        //             ],
    
        //             'customer'         => [
        //                 'id'    => $h->User->id ?? null,
        //                 'name'  => $h->User ? $h->User->first_name . ' ' . $h->User->last_name : 'N/A',
        //                 'phone' => $h->User->UserPhone ?? null,
        //             ],
    
        //             'pickup'           => null,
        //             'drop'             => null,
        //             'pickup_lat'       => null,
        //             'pickup_lng'       => null,
        //             'drop_lat'         => null,
        //             'drop_lng'         => null,
    
        //             'estimate_bill'   => 0,
        //             'final_amount'     => (float) ($h->final_amount_paid ?? 0),
    
        //             'started_at'       => $h->created_at ? $h->created_at->format('d M Y, h:i A') : null,
        //             'driver_vehicle_id'=> null,
        //         ];
        //     });
    
        // =====================================================
        // Fetch Ongoing Laundry Orders
        // =====================================================
        $ongoingLaundry = collect([]);
        // $ongoingLaundry = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->whereIn('order_status', $laundryOngoing)
        //     ->with(['User:id,first_name,last_name,UserPhone'])
        //     ->select(
        //         'id',
        //         'driver_id',
        //         'user_id',
        //         'order_status',
        //         'final_amount_paid',
        //         'created_at',
        //     )
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($l) use ($driversMap) {
        //         $driver = $driversMap[$l->driver_id] ?? null;
    
        //         return [
        //             'ride_id'          => $l->id,
        //             'type'             => 'LAUNDRY',
        //             'type_label'       => 'Laundry',
        //             'status'           => $l->order_status,
        //             'status_label'     => $this->laundryStatusLabel($l->order_status),
        //             'service_type'     => null,
    
        //             'driver'           => [
        //                 'id'            => $driver->id            ?? null,
        //                 'name'          => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //                 'phone'         => $driver->phoneNumber   ?? null,
        //                 'profile_pic'   => $driver->profile_image ?? null,
        //                 'online_offline'=> $driver->online_offline ?? null,
        //                 'free_busy'     => $driver->free_busy     ?? null,
        //             ],
    
        //             'customer'         => [
        //                 'id'    => $l->User->id ?? null,
        //                 'name'  => $l->User ? $l->User->first_name . ' ' . $l->User->last_name : 'N/A',
        //                 'phone' => $l->User->UserPhone ?? null,
        //             ],
    
        //             'pickup'           => null,
        //             'drop'             => null,
        //             'pickup_lat'       => null,
        //             'pickup_lng'       => null,
        //             'drop_lat'         => null,
        //             'drop_lng'         => null,
    
        //             'estimate_bill'   => 0,
        //             'final_amount'     => (float) ($l->final_amount_paid ?? 0),
    
        //             'started_at'       => $l->created_at ? $l->created_at->format('d M Y, h:i A') : null,
        //             'driver_vehicle_id'=> null,
        //         ];
        //     });
    
        // =====================================================
        // Merge & Sort by latest first
        // =====================================================
        $allLiveRides = $ongoingBookings
            ->concat($ongoingOrders)
            ->concat($ongoingHandyman)
            ->concat($ongoingLaundry)
            ->sortByDesc('started_at')
            ->values();
    
        return [
            'summary' => [
                'total_live'      => $allLiveRides->count(),
                'bookings'        => $ongoingBookings->count(),
                'orders'          => $ongoingOrders->count(),
                'handyman'        => $ongoingHandyman->count(),
                'laundry'         => $ongoingLaundry->count(),
            ],
            'live_rides' => $allLiveRides,
        ];
    }

    // =====================================================
    // Helper — Booking Status Label
    // =====================================================
    private function bookingStatusLabel(int $status): string
    {
        return match($status) {
            1002    => 'Driver Accepted',
            1003    => 'Driver Arrived',
            1004    => 'Trip Started',
            1012    => 'Driver On The Way',
            default => 'Unknown',
        };
    }

    // =====================================================
    // Empty Response
    // =====================================================
    private function emptyLiveRidesResponse(): array
    {
        return [
            'summary'    => [
                'total_live' => 0,
                'bookings'   => 0,
                'orders'     => 0,
                'handyman'   => 0,
                'laundry'    => 0,
            ],
            'live_rides' => [],
        ];
    }

    // =====================================================
    // Vehicle Configuration
    // =====================================================
    public function getVehicleConfiguration($merchant, $country_area_id, $is_engine_based = null)
    {
        $merchant_id      = $merchant->id;
        $merchant_segment = array_pluck($merchant->Segment, 'id');
        $merchant_service = array_pluck($merchant->ServiceType, 'id');

        $area_vehicle = CountryArea::select('id')->with(['VehicleType' => function ($q) use ($merchant_segment, $merchant_service, $merchant_id) {
            $q->addSelect('id', 'vehicle_type_id', 'vehicleTypeImage', 'vehicleTypeMapImage', 'model_expire_year', 'volumetric_capacity', 'package_weight_range');
            $q->where('admin_delete', NULL);
            $q->where('merchant_id', $merchant_id);
            $q->whereIn('segment_id', $merchant_segment);
            $q->whereIn('service_type_id', $merchant_service);
            $q->orderBy('vehicleTypeRank');
        }])->whereHas('VehicleType', function ($qq) {
            $qq->where('admin_delete', NULL);
        })->find($country_area_id);

        if (empty($area_vehicle)) {
            return null;
        }

        if ($is_engine_based !== null) {
            $vehicleTypes = $area_vehicle->vehicleType()
                ->where('engine_type', $is_engine_based)
                ->get()
                ->unique();
        } else {
            $vehicleTypes = $area_vehicle->VehicleType->unique();
        }

        $vehicleTypes = $vehicleTypes->map(function ($value) {
            return [
                'id'                         => $value->id,
                'vehicleTypeName'             => $value->VehicleTypeName,
                'modelExpireYear'             => $value->model_expire_year ?? "",
                'volumetirc_capacity_range'   => $value->volumetric_capacity ?? "",
                'max_package_weight'          => $value->package_weight_range ?? "",
            ];
        });

        $vehicleMake = VehicleMake::select('id')
            ->where('merchant_id', $merchant_id)
            ->where('admin_delete', NULL)
            ->get()
            ->sortBy('vehicleMakeName')
            ->values()
            ->map(function ($value) {
                return [
                    'id'              => $value->id,
                    'vehicleMakeName' => $value->VehicleMakeName,
                ];
            });

        return [
            'vehicle_type' => $vehicleTypes,
            'vehicle_make' => $vehicleMake,
        ];
    }

    // =====================================================
    // Assign Vehicle to Fleet
    // =====================================================
    public function assignVehicle($fleet_id, $data, $string_file)
    {
        // Check vehicle type/make/model belongs to this fleet
        $typeInFleet = FleetVehicleType::where('fleet_id', $fleet_id)
            ->where('vehicle_type_id', $data['vehicle_type'])
            ->exists();
    
        if (!$typeInFleet) {
            return ['success' => false, 'message' => 'Vehicle type does not belong to this fleet'];
        }
    
        $makeInFleet = FleetVehicleMake::where('fleet_id', $fleet_id)
            ->where('vehicle_make_id', $data['vehicle_make'])
            ->exists();
    
        if (!$makeInFleet) {
            return ['success' => false, 'message' => 'Vehicle make does not belong to this fleet'];
        }
    
        $modelInFleet = FleetVehicleModel::where('fleet_id', $fleet_id)
            ->where('vehicle_model_id', $data['vehicle_model'])
            ->exists();
    
        if (!$modelInFleet) {
            return ['success' => false, 'message' => 'Vehicle model does not belong to this fleet'];
        }
    
        // Check duplicate
        $exists = FleetVehicle::where('fleet_id', $fleet_id)
            ->where('vehicle_type_id',  $data['vehicle_type'])
            ->where('vehicle_make_id',  $data['vehicle_make'])
            ->where('vehicle_model_id', $data['vehicle_model'])
            ->where('vehicle_status', 1)
            ->exists();
    
        if ($exists) {
            return ['success' => false, 'message' => trans("$string_file.vehicle_already_assigned_to_fleet")];
        }
    
        $vehicle = FleetVehicle::create([
            'fleet_id'         => $fleet_id,
            'vehicle_type_id'  => $data['vehicle_type'],
            'vehicle_make_id'  => $data['vehicle_make'],
            'vehicle_model_id' => $data['vehicle_model'],
            'vehicle_status'   => $data['vehicle_status'],
        ]);
    
        return ['success' => true, 'data' => $vehicle];
    }
    

    // =====================================================
    // Check Vehicle Belongs to Fleet
    // =====================================================
    public function vehicleBelongsToFleet($fleet_id, $fleet_vehicle_id)
    {
        return FleetVehicle::where('id', $fleet_vehicle_id)
            ->where('fleet_id', $fleet_id)
            ->where('vehicle_status', 1)
            ->exists();
    }

    // =====================================================
    // Assign/Unassign Driver Vehicle
    // =====================================================
    public function driverVehicleAssignment($fleet_id, $driver_id, $fleet_vehicle_id, $is_assigned, $driver_vehicle_id, $data = [])
    {
        DB::beginTransaction();
        try {
            if ($is_assigned == 1) {
                $existsData = DriverVehicleAssignment::where('fleet_id',   $fleet_id)
                    ->where('driver_id',  $driver_id)
                    ->where('vehicle_id', $fleet_vehicle_id)
                    ->whereNull('unassigned_at')
                    ->exists();
    
                if ($existsData) {
                    DB::rollBack();
                    return ['success' => false, 'message' => 'already_assigned'];
                }
    
                $assignment = DriverVehicleAssignment::updateOrCreate(
                    ['fleet_id' => $fleet_id, 'driver_id' => $driver_id],
                    [
                        'vehicle_id'    => $fleet_vehicle_id,
                        'assigned_at'   => Carbon::now('UTC'),
                        'unassigned_at' => NULL,
                    ]
                );
    
                // Update driver_vehicle + sync segments + update signup step
                $driverVehicle = $this->updateDriverVehicleRecord(
                    $fleet_id,
                    $driver_id,
                    $fleet_vehicle_id,
                    $driver_vehicle_id
                );
    
                $fleet = Fleet::find($fleet_id);
    
                // ===== Optional per-driver payment_type override, only if merchant allows it =====
                $canOverridePaymentType = !empty($fleet)
                    && isset($fleet->Merchant->FleetConfiguration->payment_type_during_assign_driver)
                    && $fleet->Merchant->FleetConfiguration->payment_type_during_assign_driver == 1;
                    
                if ($canOverridePaymentType && !empty($data['payment_type']) && $data['payment_type'] != '') {
                    FleetDriver::where('fleet_id', $fleet_id)
                        ->where('driver_id', $driver_id)
                        ->update([
                            'payment_type'            => $data['payment_type'],
                            'commission_percentage'   => $data['commission_percentage'] ?? null,
                            'driver_share_percentage' => $data['driver_share_percentage'] ?? null,
                            'rent_amount'             => $data['rent_amount'] ?? null,
                            'rent_frequency'          => $data['rent_frequency'] ?? null,
                        ]);
                }
    
                // ===== Resolve final payment type (driver override, just-set or pre-existing, else fleet default) =====
                $fleetDriver = FleetDriver::where('fleet_id', $fleet_id)->where('driver_id', $driver_id)->first();
                $resolvedPaymentType = $fleetDriver->payment_type ?? $fleet->payment_type ?? 1;
                $resolvedRentAmount = $fleetDriver->rent_amount ?? $fleet->rent_amount;
                $resolvedRentFrequency = $fleetDriver->rent_frequency ?? $fleet->rent_frequency;
    
                // Seed first rent row if resolved payout model is fixed-rent
                if ($resolvedPaymentType == 3 && $resolvedRentAmount) {
                    $existingPendingRent = FleetDriverRent::where('fleet_id', $fleet_id)
                        ->where('driver_id', $driver_id)
                        ->where('status', 1)
                        ->exists();
    
                    if (!$existingPendingRent) {
                        $dueDateUtc = $resolvedRentFrequency == 1
                            ? Carbon::now('UTC')->addDay()
                            : Carbon::now('UTC')->addWeek();
    
                        FleetDriverRent::create([
                            'fleet_id'          => $fleet_id,
                            'driver_id'         => $driver_id,
                            'driver_vehicle_id' => $driver_vehicle_id,
                            'amount'            => $resolvedRentAmount,
                            'frequency'         => $resolvedRentFrequency,
                            'due_date'          => $dueDateUtc,
                            'status'            => 1,
                        ]);
                    }
                }
    
                DB::commit();
    
                return [
                    'success' => true,
                    'type'    => 'assigned',
                    'data'    => [
                        'fleet_id'          => $fleet_id,
                        'driver_id'         => $driver_id,
                        'fleet_vehicle_id'  => $fleet_vehicle_id,
                        'driver_vehicle_id' => $driver_vehicle_id,
                        'assigned_at'       => $assignment->assigned_at,
                    ]
                ];
    
            } else {
                $assignment = DriverVehicleAssignment::where('fleet_id',  $fleet_id)
                    ->where('driver_id', $driver_id)
                    ->whereNull('unassigned_at')
                    ->first();
    
                if (empty($assignment)) {
                    DB::rollBack();
                    return ['success' => false, 'message' => 'no_vehicle_assigned_to_driver'];
                }
    
                $assignment->update(['unassigned_at' => Carbon::now('UTC')]);
    
                FleetDriverRent::where('fleet_id', $fleet_id)
                    ->where('driver_id', $driver_id)
                    ->where('status', 1)
                    ->update(['status' => 4]); // waived
    
                $this->resetDriverOnUnassign($driver_id, $assignment->vehicle_id);
                $this->deactivateDriverVehicleRecord($driver_id, $assignment->vehicle_id);
    
                DB::commit();
    
                return [
                    'success' => true,
                    'type'    => 'unassigned',
                    'data'    => [
                        'fleet_id'         => $fleet_id,
                        'driver_id'        => $driver_id,
                        'fleet_vehicle_id' => $fleet_vehicle_id,
                        'unassigned_at'    => $assignment->unassigned_at,
                    ]
                ];
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =====================================================
    // When driver-vehicle assigned → create driver_vehicle
    // =====================================================
    public function updateDriverVehicleRecord(int $fleetId, int $driverId, int $fleetVehicleId, int $driver_vehicle_id)
    {
    try {
        // Find existing driver_vehicle by id
        $driverVehicle = \App\Models\DriverVehicle::where('id', $driver_vehicle_id)
            ->whereNull('driver_id')
            ->first();

        if (!empty($driverVehicle)) {
            // Update driver_id regardless of is_fleet_ready
            $driverVehicle->update([
                'driver_id' => $driverId,
                'owner_id'  => $driverId,
            ]);
        } else {
            // Create new driver_vehicle if not found
            $fleetVehicle  = FleetVehicle::find($fleetVehicleId);
            $driverVehicle = \App\Models\DriverVehicle::create([
                'merchant_id'      => $driverVehicle->merchant_id  ?? null,
                'fleet_id'         => $fleetId,
                'fleet_vehicle_id' => $fleetVehicleId,
                'driver_id'        => $driverId,
                'owner_id'         => $driverId,
                'vehicle_type_id'  => $fleetVehicle->vehicle_type_id,
                'vehicle_make_id'  => $fleetVehicle->vehicle_make_id,
                'vehicle_model_id' => $fleetVehicle->vehicle_model_id,
            ]);
        }

        // Sync driver_driver_vehicle pivot
        $driverVehicle->Drivers()->syncWithoutDetaching([
            $driverId => [
                'vehicle_active_status' => 1,
                'is_detached'           => 2,
            ]
        ]);

        $driver = \App\Models\Driver::find($driverId);

        if ($driverVehicle->is_fleet_ready == 1) {
            // Docs + Segment done → sync segments to driver then update step
            $this->syncVehicleSegmentsToDriver($driverVehicle, $driver);
            if ($driver->signupStep == 6) {
                // $autoVerify = get_driver_auto_verify_status($driver->id);
        
                // $signupStatus = $autoVerify == 1 ? 9 : 8;
                $signupStatus = 9;
                $driver->signupStep = $signupStatus;
                $driver->free_busy = 2;
                $driver->save();
            }

        } elseif ($driverVehicle->docs_completed == 1) {
            // Docs done but segment not yet
            if ($driver->signupStep == 5) {
                $driver->signupStep     = 6;
                $driver->online_offline = 2;
                $driver->save();
            }
        }
        // else → nothing done yet, keep current step

        return $driverVehicle;

    } catch (\Exception $e) {
        throw $e;
    }
}

    private function syncVehicleSegmentsToDriver(\App\Models\DriverVehicle $driverVehicle,\App\Models\Driver $driver) 
    {
        $vehicleServiceTypes = $driverVehicle->ServiceTypes()
            ->withPivot('segment_id', 'driver_vehicle_id')
            ->get();
    
        if ($vehicleServiceTypes->isEmpty()) {
            return;
        }
    
        //  Clear ALL existing segments and service types for this driver
        // Don't just clear specific segment — clear everything
        // $driver->Segment()->detach();
        $driver->ServiceType()->detach();
    
        //  Also clear driver_service_type table completely for this driver
        \DB::table('driver_service_type')
            ->where('driver_id', $driver->id)
            ->delete();
    
        //  Re-attach only THIS vehicle's segments and service types
        $grouped = $vehicleServiceTypes->groupBy(function ($item) {
            return $item->pivot->segment_id;
        });
    
        foreach ($grouped as $segmentId => $services) {
            // Attach segment
            $driver->Segment()->attach($segmentId);
    
            // Attach service types with correct driver_vehicle_id
            foreach ($services as $service) {
                $driver->ServiceType()->attach(
                    $service->id,
                    [
                        'segment_id'        => $segmentId,
                        'driver_vehicle_id' => $driverVehicle->id, // exact vehicle
                    ]
                );
            }
        }
    
        // Sync driver_online table
        $this->syncDriverOnlineTable($driver, $driverVehicle);
    }

    private function syncDriverOnlineTable(\App\Models\Driver $driver,\App\Models\DriverVehicle $driverVehicle) 
    {
        $vehicleServiceTypes = $driverVehicle->ServiceTypes()
            ->withPivot('segment_id')
            ->get();
    
        //  Clear ALL existing online records for this driver
        \DB::table('driver_online')
            ->where('driver_id', $driver->id)
            ->delete();
    
        if ($vehicleServiceTypes->isEmpty()) {
            return;
        }
    
        // Insert only this vehicle's service types
        $insertData = [];
        foreach ($vehicleServiceTypes as $service) {
            $insertData[] = [
                'driver_id'         => $driver->id,
                'service_type_id'   => $service->id,
                'segment_id'        => $service->pivot->segment_id,
                'driver_vehicle_id' => $driverVehicle->id,
            ];
        }
    
        if (!empty($insertData)) {
            \DB::table('driver_online')->insert($insertData);
        }
    }
    
    public function getFleetVehicleDriver($fleet_id)
    {
        // Active assignments
        $activeAssignments = DriverVehicleAssignment::where('fleet_id', $fleet_id)
            ->whereNull('unassigned_at')
            ->get();
    
        $assignedVehicleIds = $activeAssignments->pluck('vehicle_id')->filter()->toArray();
        $assignedDriverIds  = $activeAssignments->pluck('driver_id')->filter()->toArray();
    
        // Pre-fetch driver_vehicles
        $driverVehiclesAll = \App\Models\DriverVehicle::where('fleet_id', $fleet_id)->get();
    
        $driverVehiclesByFleetVehicleId = $driverVehiclesAll->keyBy('fleet_vehicle_id');
    
        $driverVehiclesByDriverId = $driverVehiclesAll
            ->filter(function ($dv) {
                return !is_null($dv->driver_id);
            })
            ->keyBy('driver_id');
    
        // Fleet Vehicles
        $fleetVehicles = FleetVehicle::where('fleet_id', $fleet_id)
            ->where('vehicle_status', 1)
            ->with([
                'VehicleType',
                'VehicleMake',
                'VehicleModel',
            ])
            ->get()
            ->map(function ($fv) use (
                $activeAssignments,
                $assignedVehicleIds,
                $driverVehiclesByFleetVehicleId
            ) {
                $assignment    = $activeAssignments->where('vehicle_id', $fv->id)->first();
                $driverVehicle = $driverVehiclesByFleetVehicleId[$fv->id] ?? null;
                $documentVerified = !$driverVehicle?->DriverVehicleDocument->contains('document_verification_status', 4);
                
                return [
                    'fleet_vehicle_id'   => $fv->id,
                    'driver_vehicle_id'  => $driverVehicle->id      ?? null,
                    'vehicle_type_id'    => (string) ($fv->VehicleType->id ?? ''),
                    'vehicle_type'       => $fv->VehicleType->VehicleTypeName  ?? '',
                    'vehicle_make_id'    => (string) ($fv->VehicleMake->id ?? ''),
                    'vehicle_make'       => $fv->VehicleMake->VehicleMakeName  ?? '',
                    'vehicle_model_id'   => (string) ($fv->VehicleModel->id ?? ''),
                    'vehicle_model'      => $fv->VehicleModel->vehicleModelName ?? '',
                    'vehicle_status'     => $fv->vehicle_status,
                    'is_assigned'        => in_array($fv->id, $assignedVehicleIds),
                    'assigned_driver_id' => $assignment->driver_id  ?? null,
                    'is_doc_uploaded'=> ($driverVehicle?->docs_completed == 1 && $documentVerified) ? true :false
                ];
            });
    
        // Fleet Drivers
        $fleetDrivers = FleetDriver::where('fleet_id', $fleet_id)
            ->where('status', 1)
            ->with([
                'Driver' => function ($q) {
                    $q->select(
                        'id',
                        'first_name',
                        'last_name',
                        'phoneNumber',
                        'online_offline',
                        'free_busy',
                        'signupStep',
                        'driver_admin_status',
                        'driver_verify_status',
                        'total_trips',
                        'current_latitude',
                        'current_longitude'
                    );
                }
            ])
            ->get()
            ->map(function ($fd) use (
                $activeAssignments,
                $assignedDriverIds,
                $driverVehiclesByDriverId
            ) {
                $driver            = $fd->Driver;
                $driverId          = $driver->id          ?? null;
                $vehicleAssignment = $activeAssignments->where('driver_id', $driverId)->first();
                $driverVehicle     = $driverId
                                        ? ($driverVehiclesByDriverId[$driverId] ?? null)
                                        : null;
    
                return [
                    'driver_id'           => $driverId,
                    'name'                => $driver
                                                ? $driver->first_name . ' ' . $driver->last_name
                                                : '',
                    'phone'               => $driver->phoneNumber    ?? null,
                    'online_offline'      => $driver->online_offline ?? null,
                    'free_busy'           => $driver->free_busy      ?? null,
                    'signupStep'           => $driver->signupStep      ?? null,
                    'fleet_signup_step'           => $fd->fleet_signup_step      ?? 1,
                    'is_approved'           => (string)$fd->is_approved,
                    'reject_reason'           => $fd->is_approved == 2 ? $fd->reject_reason : "",
                    'is_active'           => $driver->driver_admin_status      ?? null,
                    'total_trips'           => $driver->total_trips      ?? null,
                    'current_latitude'      => $driver->current_latitude      ?? null,
                    'current_longitude'     => $driver->current_longitude      ?? null,
                    'is_assigned'         => in_array($driverId, $assignedDriverIds),
                    'assigned_vehicle_id' => $vehicleAssignment->vehicle_id ?? null,
                    'driver_vehicle_id'   => $driverVehicle->id             ?? null,
                ];
            });
    
        return [
            'fleet_vehicles' => $fleetVehicles,
            'fleet_drivers'  => $fleetDrivers,
        ];
    }

    // =====================================================
    // Private Helper: Bulk Trips
    // =====================================================
    private function getBulkTrips(array $driverIds)
    {
        return [
            'booking' => Booking::whereIn('driver_id', $driverIds)
                ->where('booking_status', 1005)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id'),

            'order' => Order::whereIn('driver_id', $driverIds)
                ->where('order_status', 11)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id'),

            'handyman' => HandymanOrder::whereIn('driver_id', $driverIds)
                ->where('order_status', 7)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id'),

            'laundry' => LaundryOutletOrder::whereIn('driver_id', $driverIds)
                ->where('order_status', 14)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id'),
        ];
    }

    // =====================================================
    // Private Helper: Bulk Ratings
    // =====================================================
    private function getBulkRatings(array $driverIds)
    {
        return DB::table('booking_ratings as br')
            ->leftJoin('bookings as b',              'br.booking_id',          '=', 'b.id')
            ->leftJoin('orders as o',                'br.order_id',            '=', 'o.id')
            ->leftJoin('handyman_orders as h',       'br.handyman_order_id',   '=', 'h.id')
            ->leftJoin('laundry_outlet_orders as l', 'br.laundry_outlet_order_id', '=', 'l.id')
            ->whereIn('b.driver_id', $driverIds)
            ->orWhereIn('o.driver_id', $driverIds)
            ->orWhereIn('h.driver_id', $driverIds)
            ->orWhereIn('l.driver_id', $driverIds)
            ->select(
                DB::raw('COALESCE(b.driver_id, o.driver_id, h.driver_id, l.driver_id) as driver_id'),
                DB::raw('AVG(br.driver_rating_points) as avg_rating')
            )
            ->groupBy('driver_id')
            ->pluck('avg_rating', 'driver_id');
    }
    
    public function getFleetVehicleDriverData($fleet_id)
    {
        $fleetVehicles = FleetVehicle::where('fleet_id', $fleet_id)
            ->where('vehicle_status', 1)
            ->with([
                'VehicleType',
                'VehicleMake',
                'VehicleModel',
                'DriverVehicleAssignment' => function($q) {
                    $q->whereNull('unassigned_at')
                      ->with([
                          'Driver' => function($q) {
                                $q->select('id','first_name','last_name','phoneNumber','online_offline','free_busy')
                                  ->with(['DriverActiveVehicle' => function($q) {
                                      $q->select(
                                          'driver_vehicles.id',
                                          'driver_vehicles.vehicle_number'
                                      );
                                 }]);
                            }
                      ]);
                }
            ])
            ->get();
    
        // Collect all assigned driver IDs
        $driverIds = $fleetVehicles
            ->pluck('DriverVehicleAssignment.driver_id')
            ->filter()
            ->toArray();
    
        // Bulk trips queries for all drivers at once
        $trips = [];
        if (!empty($driverIds)) {
            $bookingTrips = Booking::whereIn('driver_id', $driverIds)
                ->where('booking_status', 1005)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id');
    
            $orderTrips = Order::whereIn('driver_id', $driverIds)
                ->where('order_status', 11)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id');
    
            $handymanTrips = HandymanOrder::whereIn('driver_id', $driverIds)
                ->where('order_status', 7)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id');
    
            $laundryTrips = LaundryOutletOrder::whereIn('driver_id', $driverIds)
                ->where('order_status', 14)
                ->select('driver_id', DB::raw('count(*) as count'))
                ->groupBy('driver_id')
                ->pluck('count', 'driver_id');
    
            foreach ($driverIds as $driverId) {
                $trips[$driverId] =
                    ($bookingTrips[$driverId]  ?? 0) +
                    ($orderTrips[$driverId]    ?? 0) +
                    ($handymanTrips[$driverId] ?? 0) +
                    ($laundryTrips[$driverId]  ?? 0);
            }
        }
        
        
    
        return $fleetVehicles->map(function($vehicle) use ($trips) {
            $assignment    = $vehicle->DriverVehicleAssignment;
            $driver_id     = $assignment->driver_id ?? null;
            $driver        = $assignment->Driver ?? null;
            $driverVehicle = $driver ? $driver->DriverActiveVehicle->first() : null;
    
            return [
                'fleet_vehicle_id' => $vehicle->id,
                'vehicle_type'     => $vehicle->VehicleType->VehicleTypeName ?? '',
                'vehicle_make'     => $vehicle->VehicleMake->VehicleMakeName ?? '',
                'vehicle_model'    => $vehicle->VehicleModel->vehicleModelName ?? '',
                'vehicle_status'   => $vehicle->vehicle_status,
                'assigned_driver'  => $assignment ? [
                    'id'             => $driver->id,
                    'name'           => $driver->first_name . ' ' . $driver->last_name,
                    'phone'          => $driver->phoneNumber,
                    'online_offline' => $driver->online_offline,
                    'free_busy'      => $driver->free_busy,
                    'assigned_at'    => $assignment->assigned_at,
                    'trips_count'    => $trips[$driver_id] ?? 0,
                    'vehicle_number' => $driverVehicle->vehicle_number ?? null,
                ] : null,
            ];
        });
    }

    
    
    // =====================================================
    // Unassign Vehicle from Driver
    // =====================================================
    public function unassignVehicleFromDriver($fleet_id, $driver_id,$string_file = NULL)
    {
        // Check driver belongs to this fleet
        $fleetDriver = FleetDriver::where('fleet_id', $fleet_id)
            ->where('driver_id', $driver_id)
            ->where('status', 1)
            ->first();
    
        if (empty($fleetDriver)) {
            return ['success' => false, 'message' => trans("$string_file.driver_not_found")];
        }
    
        // Check active assignment exists
        $assignment = DriverVehicleAssignment::where('fleet_id', $fleet_id)
            ->where('driver_id', $driver_id)
            ->whereNull('unassigned_at')
            ->first();
    
        if (empty($assignment)) {
            return ['success' => false, 'message' => trans("$string_file.no_vehicle_assigned_driver")];
        }
        // Unassign
        $assignment->update(['unassigned_at' => Carbon::now('UTC')]);
        
        FleetDriverRent::where('fleet_id', $fleet_id)
                    ->where('driver_id', $driver_id)
                    ->where('status', 1)
                    ->update(['status' => 4]); // waived
    
        $this->resetDriverOnUnassign($driver_id, $assignment->vehicle_id);
        
        // Deactivate driver_vehicle + driver_driver_vehicle
        $this->deactivateDriverVehicleRecord(
            $driver_id,
            $assignment->vehicle_id
        );
        return ['success' => true];
    }
    
    public function deactivateDriverVehicleRecord(int $driverId, int $fleetVehicleId)
    {
        $driverVehicle = \App\Models\DriverVehicle::where('driver_id', $driverId)
            ->where('fleet_vehicle_id', $fleetVehicleId)
            ->first();
    
        if (!$driverVehicle) {
            return null;
        }
    
        //Update pivot table using updateExistingPivot
        $driverVehicle->Drivers()->updateExistingPivot($driverId, [
            'vehicle_active_status' => 2, // 2=Deactive
            'is_detached'           => 2,
        ]);
        
        $driverVehicle->driver_id = NULL;
        $driverVehicle->owner_id = NULL;
        $driverVehicle->save();
    
        return $driverVehicle;
    }
    
    // =====================================================
    // When driver unassigned OR vehicle removed from fleet
    // =====================================================
    public function resetDriverOnUnassign(int $driverId, int $fleetVehicleId)
    {
        $driverVehicle = \App\Models\DriverVehicle::where('driver_id', $driverId)
            ->where('fleet_vehicle_id', $fleetVehicleId)
            ->first();
    
        if (!$driverVehicle) return;
    
        $driver = \App\Models\Driver::find($driverId);
        if (!$driver) return;
    
        // Deactivate pivot
        $driverVehicle->Drivers()->updateExistingPivot($driverId, [
            'vehicle_active_status' => 2,
            'is_detached'           => 2,
        ]);
    
        // Detach service types from vehicle
        $driverVehicle->ServiceTypes()->detach();
        //  Reset driver signup step back to pending
        // Only if driver was approved via this vehicle
        if ($driver->signupStep == 9) {
            $driver->signupStep  = 5;
            $driver->free_busy = 2;
            $driver->online_offline = 2;
            // $driver->is_approved = 2;
            $driver->save();
        }
    }
    
    private function segmentLabel($segment_id)
    {
        return config('custom.segment_slug.' . $segment_id) ?? 'Unknown';
    }
    
    // =====================================================
    // Segment Type Resolver
    // Tells us which model/handler to use for a segment
    // =====================================================
    private function resolveSegmentType($segment_id)
    {
        // Taxi
        if ($segment_id == 1) {
            return 'booking';
        }
    
        // Delivery, Food, Grocery — all use Order model
        if (in_array($segment_id, [2, 3, 4])) {
            return 'order';
        }
    
        // Handyman-type services — Towing, Salon, Plumber, Electrician, Lawyer, Real Estate
        if (in_array($segment_id, [5, 6, 7, 8, 9, 10])) {
            return 'handyman';
        }
    
        return null;
    }
    
    // =====================================================
    // Check Driver Belongs to Fleet
    // =====================================================
    public function driverBelongsToFleet($fleet_id = NULL, $driver_id)
    {
        
        $query = FleetDriver::where('fleet_id', $fleet_id)
            ->where('driver_id', $driver_id)
            ->where('status', 1);
            if($fleet_id){
                $query->where('fleet_id', $fleet_id);
            }
        return $query->exists();
    }
    
    //Driver Ride History
    public function getDriverRideHistory($driver_id,$segment_id,$perPage     = 15,$page        = 1,$string_file = "") {
        $records = collect();
        $summary = [];
    
        // =====================================================
        // Status Groups
        // =====================================================
        $bookingOngoing   = [1002, 1003, 1004, 1012];
        $bookingCompleted = [1005];
        $bookingCancelled = [1006, 1007, 1008, 1016];
    
        $orderOngoing   = [4, 6, 7, 9, 10];
        $orderCompleted = [11];
        $orderCancelled = [2, 3, 5, 8, 12];
    
        $handymanOngoing   = [4, 6, 9];
        $handymanCompleted = [7];
        $handymanCancelled = [2, 3, 5, 8];
    
        switch ($segment_id) {
    
            // -------------------------------------------------
            // Segment 1,2— TAXI/Delivery (Bookings)
            // -------------------------------------------------
            case 1:
            case 2:
                $typeLabel = $this->segmentLabel($segment_id);
                $data = Booking::where('driver_id', $driver_id)
                    ->where('segment_id', $segment_id)
                    ->with('CountryArea:id,timezone')
                    ->select('id', 'booking_status', 'created_at', 'pickup_location', 'drop_location', 'final_amount_paid','segment_id','merchant_id','payment_method_id','country_area_id','travel_distance','travel_time')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($b) use ($typeLabel,$string_file, $bookingOngoing, $bookingCompleted, $bookingCancelled) {
                        $timezone = $b->countryArea->timezone ?? 'UTC';        // ← resolve tz
                        $formatted = $b->created_at
                            ? \Carbon\Carbon::parse($b->created_at)
                                ->setTimezone($timezone)
                                ->format('d M Y, h:i A')
                            : null;
                        return [
                            'id'           => $b->id,
                            'type'         => 'BOOKING',
                            'type_label'   => $typeLabel,
                            'Segment'   => $b->Segment->Name(),
                            'status'       => $b->booking_status,
                            'status_label' => CommonController::BookingStatus($b->booking_status, $string_file),
                            'is_ongoing'   => in_array($b->booking_status, $bookingOngoing),
                            'is_completed' => in_array($b->booking_status, $bookingCompleted),
                            'is_cancelled' => in_array($b->booking_status, $bookingCancelled),
                            'pickup'       => $b->pickup_location   ?? null,
                            'drop'         => $b->drop_location     ?? null,
                            'amount'       => $b->final_amount_paid ?? 0,
                            'created_at'   => $formatted,
                            'payment_method'=> $b->PaymentMethod->MethodName($b->merchant_id),
                            'travel_distance'=> $b->travel_distance,
                            'travel_time'=> $b->travel_time,
                            'raw_date'     => $b->created_at,
                        ];
                    });
    
                $summary = [
                    'segment'        => $this->segmentLabel($segment_id),
                    'total_rides'    => $data->count(),
                    'ongoing'        => $data->where('is_ongoing', true)->count(),
                    'completed'      => $data->where('is_completed', true)->count(),
                    'cancelled'      => $data->where('is_cancelled', true)->count(),
                    'total_earnings' => round($data->where('is_completed', true)->sum('amount'), 2),
                ];
    
                $records = $data;
                break;
    
            // -------------------------------------------------
            // Segments 3,4 — FOOD / GROCERY
            // -------------------------------------------------
            // case 3:
            // case 4:
            //     $typeLabel = $this->segmentLabel($segment_id);
    
            //     $data = Order::where('driver_id', $driver_id)
            //         ->where('segment_id', $segment_id)
            //         ->select('id', 'order_status', 'created_at', 'final_amount_paid', 'segment_id')
            //         ->orderBy('created_at', 'desc')
            //         ->get()
            //         ->map(function ($o) use ($typeLabel, $orderOngoing, $orderCompleted, $orderCancelled) {
            //             return [
            //                 'id'           => $o->id,
            //                 'type'         => 'ORDER',
            //                 'type_label'   => $typeLabel,
            //                 'status'       => $o->order_status,
            //                 'status_label' => $this->orderStatusLabel($o->order_status),
            //                 'is_ongoing'   => in_array($o->order_status, $orderOngoing),
            //                 'is_completed' => in_array($o->order_status, $orderCompleted),
            //                 'is_cancelled' => in_array($o->order_status, $orderCancelled),
            //                 'pickup'       => null,
            //                 'drop'         => null,
            //                 'amount'       => $o->total_amount ?? 0,
            //                 'created_at'   => $o->created_at ? $o->created_at->format('d M Y, h:i A') : null,
            //                 'raw_date'     => $o->created_at,
            //             ];
            //         });
    
            //     $summary = [
            //         'segment'        => $typeLabel,
            //         'total_orders'   => $data->count(),
            //         'ongoing'        => $data->where('is_ongoing', true)->count(),
            //         'completed'      => $data->where('is_completed', true)->count(),
            //         'cancelled'      => $data->where('is_cancelled', true)->count(),
            //         'total_earnings' => round($data->where('is_completed', true)->sum('amount'), 2),
            //     ];
    
            //     $records = $data;
            //     break;
    
            // // -------------------------------------------------
            // // Segments 5–10 — HANDYMAN type services
            // // -------------------------------------------------
            // case 5:
            // case 6:
            // case 7:
            // case 8:
            // case 9:
            // case 10:
            //     $typeLabel = $this->segmentLabel($segment_id);
    
            //     $data = HandymanOrder::where('driver_id', $driver_id)
            //         ->where('segment_id', $segment_id)
            //         ->select('id', 'order_status', 'created_at', 'final_amount_paid', 'segment_id')
            //         ->orderBy('created_at', 'desc')
            //         ->get()
            //         ->map(function ($h) use ($typeLabel, $handymanOngoing, $handymanCompleted, $handymanCancelled) {
            //             return [
            //                 'id'           => $h->id,
            //                 'type'         => 'HANDYMAN',
            //                 'type_label'   => $typeLabel,
            //                 'status'       => $h->order_status,
            //                 'status_label' => $this->handymanStatusLabel($h->order_status),
            //                 'is_ongoing'   => in_array($h->order_status, $handymanOngoing),
            //                 'is_completed' => in_array($h->order_status, $handymanCompleted),
            //                 'is_cancelled' => in_array($h->order_status, $handymanCancelled),
            //                 'pickup'       => null,
            //                 'drop'         => null,
            //                 'amount'       => $h->total_amount ?? 0,
            //                 'created_at'   => $h->created_at ? $h->created_at->format('d M Y, h:i A') : null,
            //                 'raw_date'     => $h->created_at,
            //             ];
            //         });
    
            //     $summary = [
            //         'segment'        => $typeLabel,
            //         'total_orders'   => $data->count(),
            //         'ongoing'        => $data->where('is_ongoing', true)->count(),
            //         'completed'      => $data->where('is_completed', true)->count(),
            //         'cancelled'      => $data->where('is_cancelled', true)->count(),
            //         'total_earnings' => round($data->where('is_completed', true)->sum('amount'), 2),
            //     ];
    
            //     $records = $data;
            //     break;
    
            default:
                return [
                    'summary'          => [],
                    'ongoing_trips'    => [],
                    'completed_trips'  => [],
                    'cancelled_trips'  => [],
                    'current_page'     => 1,
                    'per_page'         => $perPage,
                    'total'            => 0,
                    'last_page'        => 1,
                ];
        }
    
        // =====================================================
        // Reusable paginator closure
        // Same page + perPage applied to each section
        // =====================================================
        $paginate = function ($collection) use ($page, $perPage) {
            $total  = (int) $collection->count();
            $offset = ((int) $page - 1) * (int) $perPage;
            $items  = $collection
                ->slice($offset, (int) $perPage)
                ->values()
                ->map(function ($item) {
                    unset($item['raw_date'], $item['is_ongoing'], $item['is_completed'], $item['is_cancelled']);
                    return $item;
                });
        
            return [
                'items'     => $items,
                'total'     => $total,
                'last_page' => $total > 0 ? (int) ceil($total / (int) $perPage) : 1,
            ];
        };
    
        // =====================================================
        // Apply same page/perPage to all 3 sections
        // =====================================================
        $ongoing   = $paginate($records->where('is_ongoing', true)->sortByDesc('raw_date')->values());
        $completed = $paginate($records->where('is_completed', true)->sortByDesc('raw_date')->values());
        $cancelled = $paginate($records->where('is_cancelled', true)->sortByDesc('raw_date')->values());
    
        return [
            'summary'      => (object)$summary,
            'current_page' => (int) $page,
            'per_page'     => (int) $perPage,
        
            'ongoing_trips'       => $ongoing['items'],
            'ongoing_total'       => (int) $ongoing['total'],
            // 'ongoing_last_page'   => (int) $ongoing['last_page'],
        
            'completed_trips'     => $completed['items'],
            'completed_total'     => (int) $completed['total'],
            // 'completed_last_page' => (int) $completed['last_page'],
        
            'cancelled_trips'     => $cancelled['items'],
            'cancelled_total'     => (int) $cancelled['total'],
            // 'cancelled_last_page' => (int) $cancelled['last_page'],
        ];
    }
    
    // =====================================================
    // Fleet Ride History — All drivers in fleet combined
    // Same structure as getDriverRideHistory
    // =====================================================
    public function getFleetRideHistory($fleet_id, $perPage = 15, $page = 1, $string_file = "")
    {
        $driverIds = FleetDriver::where('fleet_id', $fleet_id)
            ->where('status', 1)
            ->pluck('driver_id')
            ->toArray();
    
        if (empty($driverIds)) {
            return $this->emptyRideHistoryResponse($perPage, $page);
        }
    
        $driversMap = Driver::whereIn('id', $driverIds)
            ->select('id', 'first_name', 'last_name', 'phoneNumber')
            ->get()
            ->keyBy('id');
    
        // =====================================================
        // Status Groups
        // =====================================================
        $bookingOngoing   = [1002, 1003, 1004, 1012];
        $bookingCompleted = [1005];
        $bookingCancelled = [1006, 1007, 1008, 1016];
    
        $orderOngoing   = [4, 6, 7, 9, 10];
        $orderCompleted = [11];
        $orderCancelled = [2, 3, 5, 8, 12];
    
        $handymanOngoing   = [4, 6, 9];
        $handymanCompleted = [7];
        $handymanCancelled = [2, 3, 5, 8];
    
        $laundryOngoing   = [4, 6, 7, 9, 13, 15];
        $laundryCompleted = [14];
        $laundryCancelled = [2, 3, 5, 8, 12];
    
        // =====================================================
        // Pre-fetch earnings from booking_transactions
        // =====================================================
    
        // Booking transactions
        $bookingIds = Booking::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleet_id)
            ->where('booking_status', 1005)
            ->pluck('id')
            ->toArray();
    
        $bookingEarnings = \App\Models\BookingTransaction::whereIn('booking_id', $bookingIds)
        ->whereNotNull('fleet_earning')
        ->select('booking_id', DB::raw('SUM(fleet_earning) as total_amount'))
        ->groupBy('booking_id')
        ->pluck('total_amount', 'booking_id');
    
        // Order transactions
        $orderIds = Order::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleet_id)
            ->where('order_status', 11)
            ->pluck('id')
            ->toArray();
        $orderEarnings = collect([]);
        // $orderEarnings = \App\Models\BookingTransaction::whereIn('order_id', $orderIds)
        // ->whereNotNull('fleet_earning')
        // ->select('order_id', DB::raw('SUM(fleet_earning) as total_amount'))
        // ->groupBy('order_id')
        // ->pluck('total_amount', 'order_id');
        
        
        // Handyman transactions
        $handymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleet_id)
            ->where('order_status', 7)
            ->pluck('id')
            ->toArray();
        $handymanEarnings = collect([]);
        // $handymanEarnings = \App\Models\BookingTransaction::whereIn('handyman_order_id', $handymanIds)
        // ->whereNotNull('fleet_earning')
        // ->select('handyman_order_id', DB::raw('SUM(fleet_earning) as total_amount'))
        // ->groupBy('handyman_order_id')
        // ->pluck('total_amount', 'handyman_order_id');
        
        // Laundry transactions
        $laundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleet_id)
            ->where('order_status', 14)
            ->pluck('id')
            ->toArray();
        $laundryEarnings = collect([]);
        // $laundryEarnings = \App\Models\BookingTransaction::whereIn('laundry_outlet_order_id', $laundryIds)
        // ->whereNotNull('fleet_earning')
        // ->select('laundry_outlet_order_id', DB::raw('SUM(fleet_earning) as total_amount'))
        // ->groupBy('laundry_outlet_order_id')
        // ->pluck('total_amount', 'laundry_outlet_order_id');
    
        // =====================================================
        // Fetch Records
        // =====================================================
    
        // Bookings
        $bookings = Booking::where(function ($q) use ($driverIds) {
                $q->whereIn('driver_id', $driverIds)
                  ->orWhereNull('driver_id')
                  ->orWhere('driver_id', 0);   // include if "empty" is stored as 0, not NULL
            })
            ->where('fleet_id', $fleet_id)
            ->with('CountryArea:id,timezone')
            ->with('Segment')
            ->select('id', 'driver_id', 'booking_status', 'booking_type','created_at', 'pickup_location', 'drop_location', 'final_amount_paid','country_area_id','segment_id','travel_distance','travel_time','payment_method_id','merchant_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) use (
                $string_file,
                $bookingOngoing,
                $bookingCompleted,
                $bookingCancelled,
                $driversMap,
                $bookingEarnings  //
            ) {
                $timezone = $b->countryArea->timezone ?? 'UTC';        // ← resolve tz
                $formatted = $b->created_at
                    ? \Carbon\Carbon::parse($b->created_at)
                        ->setTimezone($timezone)
                        ->format('d M Y, h:i A')
                    : null;
                $driver = $driversMap[$b->driver_id] ?? null;
    
                //  Get earning from booking_transactions
                $earning = $bookingEarnings[$b->id] ?? $b->final_amount_paid ?? 0;
    
                return [
                    'id'           => $b->id,
                    'type'         => 'BOOKING',
                    'type_label'   => 'Ride',
                    'Segment'     => $b->Segment ? $b->Segment->Name() : null,
                    'driver_id'    => $b->driver_id,
                    'driver_name'  => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
                    'driver_phone' => $driver->phoneNumber ?? null,
                    'status'       => $b->booking_status,
                    'status_label' => CommonController::BookingStatus($b->booking_status, $string_file),
                    'is_ongoing'   => in_array($b->booking_status, $bookingOngoing),
                    'is_completed' => in_array($b->booking_status, $bookingCompleted),
                    'is_cancelled' => in_array($b->booking_status, $bookingCancelled),
                    'pickup'       => $b->pickup_location   ?? null,
                    'drop'         => $b->drop_location     ?? null,
                    'amount'       => number_format($earning,2),      //  from transactions
                    'created_at'   => $formatted,
                    'payment_method'=> $b->PaymentMethod->MethodName($b->merchant_id),
                    'travel_distance'=> $b->travel_distance,
                    'travel_time'=> $b->travel_time,
                    'raw_date'     => $b->created_at,
                    'booking_type'  => $b->booking_type,
                    'is_scheduled'  => (int) $b->booking_type === 2,
                ];
            });
    
        $orders = collect([]);
        // Orders
        // $orders = Order::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleet_id)
        //     ->select('id', 'driver_id', 'order_status', 'created_at', 'final_amount_paid', 'segment_id')
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($o) use (
        //         $orderOngoing,
        //         $orderCompleted,
        //         $orderCancelled,
        //         $driversMap,
        //         $orderEarnings  // 
        //     ) {
        //         $driver    = $driversMap[$o->driver_id] ?? null;
        //         $typeLabel = $this->segmentLabel($o->segment_id);
        //         $earning   = $orderEarnings[$o->id] ?? $o->final_amount_paid ?? 0;
    
        //         return [
        //             'id'           => $o->id,
        //             'type'         => 'ORDER',
        //             'type_label'   => $typeLabel,
        //             'driver_id'    => $o->driver_id,
        //             'driver_name'  => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //             'driver_phone' => $driver->phoneNumber ?? null,
        //             'status'       => $o->order_status,
        //             'status_label' => $this->orderStatusLabel($o->order_status),
        //             'is_ongoing'   => in_array($o->order_status, $orderOngoing),
        //             'is_completed' => in_array($o->order_status, $orderCompleted),
        //             'is_cancelled' => in_array($o->order_status, $orderCancelled),
        //             'pickup'       => null,
        //             'drop'         => null,
        //             'amount'       => number_format($earning,2),      //
        //             'created_at'   => $o->created_at ? $o->created_at->format('d M Y, h:i A') : null,
        //             'raw_date'     => $o->created_at,
        //         ];
        //     });
    
        $handymanOrders = collect([]);
        // Handyman
        // $handymanOrders = HandymanOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleet_id)
        //     ->select('id', 'driver_id', 'order_status', 'created_at', 'final_amount_paid', 'segment_id')
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($h) use (
        //         $handymanOngoing,
        //         $handymanCompleted,
        //         $handymanCancelled,
        //         $driversMap,
        //         $handymanEarnings  // 
        //     ) {
        //         $driver    = $driversMap[$h->driver_id] ?? null;
        //         $typeLabel = $this->segmentLabel($h->segment_id);
        //         $earning   = $handymanEarnings[$h->id] ?? $h->final_amount_paid ?? 0;
    
        //         return [
        //             'id'           => $h->id,
        //             'type'         => 'HANDYMAN',
        //             'type_label'   => $typeLabel,
        //             'driver_id'    => $h->driver_id,
        //             'driver_name'  => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //             'driver_phone' => $driver->phoneNumber ?? null,
        //             'status'       => $h->order_status,
        //             'status_label' => $this->handymanStatusLabel($h->order_status),
        //             'is_ongoing'   => in_array($h->order_status, $handymanOngoing),
        //             'is_completed' => in_array($h->order_status, $handymanCompleted),
        //             'is_cancelled' => in_array($h->order_status, $handymanCancelled),
        //             'pickup'       => null,
        //             'drop'         => null,
        //             'amount'       => number_format($earning,2),      // 
        //             'created_at'   => $h->created_at ? $h->created_at->format('d M Y, h:i A') : null,
        //             'raw_date'     => $h->created_at,
        //         ];
        //     });
    
        $laundryOrders = collect([]);
        // Laundry
        // $laundryOrders = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleet_id)
        //     ->select('id', 'driver_id', 'order_status', 'created_at', 'final_amount_paid')
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($l) use (
        //         $laundryOngoing,
        //         $laundryCompleted,
        //         $laundryCancelled,
        //         $driversMap,
        //         $laundryEarnings  // 
        //     ) {
        //         $driver  = $driversMap[$l->driver_id] ?? null;
        //         $earning = $laundryEarnings[$l->id]   ?? $l->final_amount_paid ?? 0;
    
        //         return [
        //             'id'           => $l->id,
        //             'type'         => 'LAUNDRY',
        //             'type_label'   => 'Laundry',
        //             'driver_id'    => $l->driver_id,
        //             'driver_name'  => $driver ? $driver->first_name . ' ' . $driver->last_name : 'N/A',
        //             'driver_phone' => $driver->phoneNumber ?? null,
        //             'status'       => $l->order_status,
        //             'status_label' => $this->laundryStatusLabel($l->order_status),
        //             'is_ongoing'   => in_array($l->order_status, $laundryOngoing),
        //             'is_completed' => in_array($l->order_status, $laundryCompleted),
        //             'is_cancelled' => in_array($l->order_status, $laundryCancelled),
        //             'pickup'       => null,
        //             'drop'         => null,
        //             'amount'       => number_format($earning,2),
        //             'created_at'   => $l->created_at ? $l->created_at->format('d M Y, h:i A') : null,
        //             'raw_date'     => $l->created_at,
        //         ];
        //     });
    
        // =====================================================
        // Total Earnings from booking_transactions
        // =====================================================
        $totalBookingEarnings  = $bookingEarnings->sum();
        $totalOrderEarnings    = $orderEarnings->sum();
        $totalHandymanEarnings = $handymanEarnings->sum();
        $totalLaundryEarnings  = $laundryEarnings->sum();
        $totalFleetEarnings    = $totalBookingEarnings + $totalOrderEarnings + $totalHandymanEarnings + $totalLaundryEarnings;
    
        // Merge all
        $allRecords = $bookings
            ->concat($orders)
            ->concat($handymanOrders)
            ->concat($laundryOrders);
    
        // Summary
        $summary = [
            'total'          => $allRecords->count(),
            'ongoing'        => $allRecords->where('is_ongoing', true)->count(),
            'completed'      => $allRecords->where('is_completed', true)->count(),
            'cancelled'      => $allRecords->where('is_cancelled', true)->count(),
            'scheduled'      => $allRecords->where('is_scheduled', true)->count(),
    
            // Earnings from booking_transactions
            'total_earnings' => number_format($totalFleetEarnings, 2),
    
            'breakdown' => [
                'bookings' => [
                    'total'          => $bookings->count(),
                    'ongoing'        => $bookings->where('is_ongoing', true)->count(),
                    'completed'      => $bookings->where('is_completed', true)->count(),
                    'cancelled'      => $bookings->where('is_cancelled', true)->count(),
                    'scheduled'      => $bookings->where('is_scheduled', true)->count(),
                    'total_earnings' => number_format($totalBookingEarnings, 2), 
                ],
                'orders' => [
                    'total'          => $orders->count(),
                    'ongoing'        => $orders->where('is_ongoing', true)->count(),
                    'completed'      => $orders->where('is_completed', true)->count(),
                    'cancelled'      => $orders->where('is_cancelled', true)->count(),
                    'total_earnings' => number_format($totalOrderEarnings, 2), 
                ],
                'handyman' => [
                    'total'          => $handymanOrders->count(),
                    'ongoing'        => $handymanOrders->where('is_ongoing', true)->count(),
                    'completed'      => $handymanOrders->where('is_completed', true)->count(),
                    'cancelled'      => $handymanOrders->where('is_cancelled', true)->count(),
                    'total_earnings' => number_format($totalHandymanEarnings, 2),
                ],
                'laundry' => [
                    'total'          => $laundryOrders->count(),
                    'ongoing'        => $laundryOrders->where('is_ongoing', true)->count(),
                    'completed'      => $laundryOrders->where('is_completed', true)->count(),
                    'cancelled'      => $laundryOrders->where('is_cancelled', true)->count(),
                    'total_earnings' => number_format($totalLaundryEarnings, 2),
                ],
            ],
        ];
    
        // Paginate
        $paginate = function ($collection) use ($page, $perPage) {
            $total  = (int) $collection->count();
            $offset = ((int) $page - 1) * (int) $perPage;
            $items  = $collection
                ->slice($offset, (int) $perPage)
                ->values()
                ->map(function ($item) {
                    unset($item['raw_date'], $item['is_ongoing'], $item['is_completed'], $item['is_cancelled'],$item['is_scheduled']);
                    return $item;
                });
    
            return [
                'items'     => $items,
                'total'     => $total,
                'last_page' => $total > 0 ? (int) ceil($total / (int) $perPage) : 1,
            ];
        };
    
        $ongoing   = $paginate($allRecords->where('is_ongoing',   true)->sortByDesc('raw_date')->values());
        $completed = $paginate($allRecords->where('is_completed', true)->sortByDesc('raw_date')->values());
        $cancelled = $paginate($allRecords->where('is_cancelled', true)->sortByDesc('raw_date')->values());
        $scheduled = $paginate($allRecords->where('is_scheduled', true)->sortByDesc('raw_date')->values());
    
        return [
            'summary'      => $summary,
            'current_page' => (int) $page,
            'per_page'     => (int) $perPage,
    
            'ongoing_trips'       => $ongoing['items'],
            'ongoing_total'       => $ongoing['total'],
            'ongoing_last_page'   => $ongoing['last_page'],
    
            'completed_trips'     => $completed['items'],
            'completed_total'     => $completed['total'],
            'completed_last_page' => $completed['last_page'],
    
            'cancelled_trips'     => $cancelled['items'],
            'cancelled_total'     => $cancelled['total'],
            'cancelled_last_page' => $cancelled['last_page'],
            
            'scheduled_trips'     => $scheduled['items'],
            'scheduled_total'     => $scheduled['total'],
            'scheduled_last_page' => $scheduled['last_page'],
        ];
    }
    
    public function getFleetEarningsPage(int $fleetId, string $filter = 'month',$string_file = NULL)
    {
        // Get all driver IDs in this fleet
        $driverIds = FleetDriver::where('fleet_id', $fleetId)
            ->where('status', 1)
            ->pluck('driver_id')
            ->toArray();
    
        if (empty($driverIds)) {
            return $this->emptyEarningsPageResponse();
        }
    
        // =====================================================
        // Date Ranges
        // =====================================================
        $currentMonthStart  = Carbon::now()->startOfMonth();
        $currentMonthEnd    = Carbon::now()->endOfMonth();
        $lastMonthStart     = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd       = Carbon::now()->subMonth()->endOfMonth();
        $weekStart          = Carbon::now()->startOfWeek(); // Monday
        $weekEnd            = Carbon::now()->endOfWeek();   // Sunday
    
        // =====================================================
        // Get Completed IDs for current month
        // =====================================================
        $currentMonthBookingIds = Booking::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleetId)
            ->where('booking_status', 1005)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->pluck('id')->toArray();
    
        // $currentMonthOrderIds = Order::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 11)
        //     ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
        //     ->pluck('id')->toArray();
    
        // $currentMonthHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 7)
        //     ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
        //     ->pluck('id')->toArray();
    
        // $currentMonthLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 14)
        //     ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
        //     ->pluck('id')->toArray();
    
        // =====================================================
        // Get Completed IDs for last month
        // =====================================================
        $lastMonthBookingIds = Booking::whereIn('driver_id', $driverIds)
            ->where('fleet_id', $fleetId)
            ->where('booking_status', 1005)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->pluck('id')->toArray();
    
        // $lastMonthOrderIds = Order::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 11)
        //     ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
        //     ->pluck('id')->toArray();
    
        // $lastMonthHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 7)
        //     ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
        //     ->pluck('id')->toArray();
    
        // $lastMonthLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
        //     ->where('fleet_id', $fleetId)
        //     ->where('order_status', 14)
        //     ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
        //     ->pluck('id')->toArray();
    
        // =====================================================
        // Current Month Earnings (fleet_earning)
        // =====================================================
        $currentMonthEarning = $this->sumFleetEarning(
            $currentMonthBookingIds,
            // $currentMonthOrderIds,
            // $currentMonthHandymanIds,
            // $currentMonthLaundryIds
        );
    
        // =====================================================
        // Last Month Earnings
        // =====================================================
        $lastMonthEarning = $this->sumFleetEarning(
            $lastMonthBookingIds,
            // $lastMonthOrderIds,
            // $lastMonthHandymanIds,
            // $lastMonthLaundryIds
        );
    
        // =====================================================
        // % Change from last month
        // =====================================================
        $percentChange     = 0;
        $percentDirection  = 'same'; // up, down, same
        if ($lastMonthEarning > 0) {
            $percentChange    = round((($currentMonthEarning - $lastMonthEarning) / $lastMonthEarning) * 100, 1);
            $percentDirection = $percentChange > 0 ? 'up' : ($percentChange < 0 ? 'down' : 'same');
        }
    
        // =====================================================
        // Total Fare Collected (final_amount_paid)
        // =====================================================
        $totalFareCollected = 0;
        if (!empty($currentMonthBookingIds)) {
            $totalFareCollected += Booking::whereIn('id', $currentMonthBookingIds)->sum('final_amount_paid');
        }
        if (!empty($currentMonthOrderIds)) {
            $totalFareCollected += Order::whereIn('id', $currentMonthOrderIds)->sum('final_amount_paid');
        }
        if (!empty($currentMonthHandymanIds)) {
            $totalFareCollected += HandymanOrder::whereIn('id', $currentMonthHandymanIds)->sum('final_amount_paid');
        }
        if (!empty($currentMonthLaundryIds)) {
            $totalFareCollected += LaundryOutletOrder::whereIn('id', $currentMonthLaundryIds)->sum('final_amount_paid');
        }
    
        // =====================================================
        // Platform Commission = Total Fare - Fleet Earning
        // =====================================================
        $platformCommission = round($totalFareCollected - $currentMonthEarning, 2);
        $commissionPercent  = $totalFareCollected > 0
            ? round(($platformCommission / $totalFareCollected) * 100, 1)
            : 0;
    
        // =====================================================
        // Weekly Overview (Mon-Sun) — daily earnings
        // =====================================================
        $weeklyData = [];
        $days       = [trans("$string_file.mon"),trans("$string_file.tue"), trans("$string_file.wed"), trans("$string_file.thurs"), trans("$string_file.fri"), trans("$string_file.sat"), trans("$string_file.sun")];
    
        for ($i = 0; $i < 7; $i++) {
            $dayStart = Carbon::now()->startOfWeek()->addDays($i)->startOfDay();
            $dayEnd   = Carbon::now()->startOfWeek()->addDays($i)->endOfDay();
    
            $dayBookingIds = Booking::whereIn('driver_id', $driverIds)
                ->where('fleet_id', $fleetId)
                ->where('booking_status', 1005)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->pluck('id')->toArray();
    
            // $dayOrderIds = Order::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 11)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            // $dayHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 7)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            // $dayLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 14)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            $dayEarning = $this->sumFleetEarning(
                $dayBookingIds,
                // $dayOrderIds,
                // $dayHandymanIds,
                // $dayLaundryIds
            );
    
            $dayTrips = count($dayBookingIds);
            // + count($dayOrderIds) + count($dayHandymanIds) + count($dayLaundryIds);
    
            $weeklyData[] = [
                'day'      => $days[$i],
                'date'     => $dayStart->format('d M Y'),
                'earning'  => number_format($dayEarning, 2),
                'trips'    => $dayTrips,
                'is_today' => $dayStart->isToday(),
            ];
        }
    
        // =====================================================
        //Recent Transactions — last 30 days daily
        // =====================================================
        $recentTransactions = [];
        $avgDailyEarning    = $currentMonthEarning > 0
            ? $currentMonthEarning / Carbon::now()->day
            : 0;
    
        for ($i = 0; $i < 30; $i++) {
            $dayStart = Carbon::now()->subDays($i)->startOfDay();
            $dayEnd   = Carbon::now()->subDays($i)->endOfDay();
    
            $dayBookingIds = Booking::whereIn('driver_id', $driverIds)
                ->where('fleet_id', $fleetId)
                ->where('booking_status', 1005)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->pluck('id')->toArray();
    
            // $dayOrderIds = Order::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 11)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            // $dayHandymanIds = HandymanOrder::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 7)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            // $dayLaundryIds = LaundryOutletOrder::whereIn('driver_id', $driverIds)
            //     ->where('fleet_id', $fleetId)
            //     ->where('order_status', 14)
            //     ->whereBetween('created_at', [$dayStart, $dayEnd])
            //     ->pluck('id')->toArray();
    
            $dayEarning = $this->sumFleetEarning(
                $dayBookingIds,
                // $dayOrderIds,
                // $dayHandymanIds,
                // $dayLaundryIds
            );
    
            $dayTrips = count($dayBookingIds);
            // + count($dayOrderIds) + count($dayHandymanIds) + count($dayLaundryIds);
    
            // Skip days with no activity
            if ($dayTrips == 0 && $dayEarning == 0) continue;
    
            //Trend label
            $trend = 'avg';
            if ($avgDailyEarning > 0) {
                if ($dayEarning >= $avgDailyEarning * 1.1) {
                    $trend = 'high';
                } elseif ($dayEarning <= $avgDailyEarning * 0.9) {
                    $trend = 'low';
                }
            }
    
            $recentTransactions[] = [
                'date'        => $dayStart->format('M d, Y'),
                'day'         => $dayStart->format('d'),
                'month'       => $dayStart->format('M'),
                'day_name'    => $dayStart->format('D'),
                'trips'       => $dayTrips,
                'earning'     => number_format($dayEarning, 2),
                'trend'       => $trend,         // high, avg, low
                'trend_label' => ucfirst($trend),
            ];
        }
    
        // =====================================================
        // Total trips this month
        // =====================================================
        $totalTripsMonth = count($currentMonthBookingIds);
        // + count($currentMonthOrderIds) + count($currentMonthHandymanIds) + count($currentMonthLaundryIds);
    
        return [
            // ---- This Month ----
            'this_month' => [
                'period'              => Carbon::now()->format('F Y'),
                'total_earning'       => number_format($currentMonthEarning, 2),
                'total_trips'         => $totalTripsMonth,
                'percent_change'      => abs($percentChange),
                'percent_direction'   => $percentDirection, // up, down, same
                'percent_label'       => ($percentChange >= 0 ? '+' : '') . $percentChange . '%' .trans("$string_file.from_last_month"),
                'last_month_earning'  => round($lastMonthEarning, 2),
            ],
    
            // ---- Earnings Breakdown ----
            'earnings_breakdown' => [
                'total_fare_collected'  => number_format($totalFareCollected, 2),
                'platform_commission'   => number_format($platformCommission, 2),
                'commission_percent'    => $commissionPercent,
                'net_earning'           => number_format($currentMonthEarning, 2),
            ],
    
            // ---- Weekly Overview ----
            'weekly_overview' => $weeklyData,
    
            // ---- Recent Transactions (last 30 days) ----
            'recent_transactions' => $recentTransactions,
        ];
    }

    // =====================================================
    // Helper — Sum fleet_earning from transactions
    // =====================================================
    private function sumFleetEarning(array $bookingIds,$orderIds = [], $handymanIds = [],$laundryIds = []) 
    {
        $total = 0;
    
        if (!empty($bookingIds)) {
            $total += \App\Models\BookingTransaction::whereIn('booking_id', $bookingIds)
                ->whereNotNull('fleet_earning')
                ->sum('fleet_earning');
        }
    
        if (!empty($orderIds)) {
            $total += \App\Models\BookingTransaction::whereIn('order_id', $orderIds)
                ->whereNotNull('fleet_earning')
                ->sum('fleet_earning');
        }
    
        if (!empty($handymanIds)) {
            $total += \App\Models\BookingTransaction::whereIn('handyman_order_id', $handymanIds)
                ->whereNotNull('fleet_earning')
                ->sum('fleet_earning');
        }
    
        if (!empty($laundryIds)) {
            $total += \App\Models\BookingTransaction::whereIn('laundry_outlet_order_id', $laundryIds)
                ->whereNotNull('fleet_earning')
                ->sum('fleet_earning');
        }
    
        return number_format($total, 2, '.', '');
    }

    // =====================================================
    // Empty Response
    // =====================================================
    private function emptyEarningsPageResponse(): array
    {
        return [
            'this_month'          => [
                'period'            => Carbon::now()->format('F Y'),
                'total_earning'     => 0,
                'total_trips'       => 0,
                'percent_change'    => 0,
                'percent_direction' => 'same',
                'percent_label'     => '0% from last month',
                'last_month_earning'=> 0,
            ],
            'earnings_breakdown'  => [
                'total_fare_collected' => 0,
                'platform_commission'  => 0,
                'commission_percent'   => 0,
                'net_earning'          => 0,
            ],
            'weekly_overview'     => [],
            'recent_transactions' => [],
        ];
    }
    
    public function getFleetDriverPerformance(int $fleetId, int $driverId = null,$string_file = NULL)
    {
    // =====================================================
    // Get driver IDs
    // =====================================================
    if ($driverId) {
        $belongsToFleet = FleetDriver::where('fleet_id', $fleetId)
            ->where('driver_id', $driverId)
            ->where('status', 1)
            ->exists();

        if (!$belongsToFleet) {
            return ['success' => false, 'message' => trans("$string_file.driver_not_found")];
        }

        $driverIds = [$driverId];
    } else {
        $driverIds = FleetDriver::where('fleet_id', $fleetId)
            ->where('status', 1)
            ->pluck('driver_id')
            ->toArray();
    }

    if (empty($driverIds)) {
        return $this->emptyPerformanceResponse();
    }

    // =====================================================
    // Date Ranges
    // =====================================================
    $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d H:i:s');
    $thisWeekEnd   = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');
    $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d H:i:s');
    $lastWeekEnd   = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d H:i:s');

    // =====================================================
    //Acceptance Rate
    // =====================================================
    $thisWeekTotalRequests = \App\Models\BookingRequestDriver::whereIn('driver_id', $driverIds)
        ->whereHas('Booking', function ($q) use ($thisWeekStart, $thisWeekEnd) {
            $q->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd]);
        })
        ->count();

    $thisWeekCompletedRequests = \App\Models\BookingRequestDriver::whereIn('driver_id', $driverIds)
        ->whereHas('Booking', function ($q) use ($driverIds) {
            $q->whereIn('driver_id', $driverIds)->where('booking_status', 1005);
        })
        ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
        ->count();

    $thisWeekAcceptanceRate = $thisWeekTotalRequests > 0
        ? round(($thisWeekCompletedRequests / $thisWeekTotalRequests) * 100, 1)
        : 0;

    $lastWeekTotalRequests = \App\Models\BookingRequestDriver::whereIn('driver_id', $driverIds)
        ->whereHas('Booking', function ($q) use ($lastWeekStart, $lastWeekEnd) {
            $q->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd]);
        })
        ->count();

    $lastWeekCompletedRequests = \App\Models\BookingRequestDriver::whereIn('driver_id', $driverIds)
        ->whereHas('Booking', function ($q) use ($driverIds) {
            $q->whereIn('driver_id', $driverIds)->where('booking_status', 1005);
        })
        ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
        ->count();

    $lastWeekAcceptanceRate = $lastWeekTotalRequests > 0
        ? round(($lastWeekCompletedRequests / $lastWeekTotalRequests) * 100, 1)
        : 0;

    $acceptanceDiff      = round($thisWeekAcceptanceRate - $lastWeekAcceptanceRate, 1);
    $acceptanceDirection = $acceptanceDiff >= 0 ? 'up' : 'down';

    // =====================================================
    // Cancellation Rate
    // =====================================================
    $thisWeekCancelled = Booking::whereIn('driver_id', $driverIds)
        ->whereIn('booking_status', [1006, 1007, 1008, 1016])
        ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
        ->count();

    $thisWeekTotalBookings = Booking::whereIn('driver_id', $driverIds)
        ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
        ->count();

    $thisWeekCancellationRate = $thisWeekTotalBookings > 0
        ? round(($thisWeekCancelled / $thisWeekTotalBookings) * 100, 1)
        : 0;

    $lastWeekCancelled = Booking::whereIn('driver_id', $driverIds)
        ->whereIn('booking_status', [1006, 1007, 1008, 1016])
        ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
        ->count();

    $lastWeekTotalBookings = Booking::whereIn('driver_id', $driverIds)
        ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
        ->count();

    $lastWeekCancellationRate = $lastWeekTotalBookings > 0
        ? round(($lastWeekCancelled / $lastWeekTotalBookings) * 100, 1)
        : 0;

    $cancellationDiff      = round($thisWeekCancellationRate - $lastWeekCancellationRate, 1);
    $cancellationDirection = $cancellationDiff <= 0 ? 'up' : 'down';

    // =====================================================
    //Avg Rating
    // =====================================================
    $avgRating = \App\Models\BookingRating::whereHas('Booking', function ($q) use ($driverIds, $thisWeekStart, $thisWeekEnd) {
            $q->whereIn('driver_id', $driverIds)
              ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd]);
        })
        ->avg('driver_rating_points');

    $avgRating   = round($avgRating ?? 0, 2);
    $ratingLabel = match(true) {
        $avgRating >= 4.5 => 'Excellent',
        $avgRating >= 4.0 => 'Good',
        $avgRating >= 3.0 => 'Average',
        default           => 'Poor',
    };

    // =====================================================
    //Online Hours
    // =====================================================
    $driverOnlineData = \App\Models\DriverOnlineTime::whereIn('driver_id', $driverIds)
        ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
        ->selectRaw('COALESCE(SUM(hours), 0) as total_hours, COALESCE(SUM(minutes), 0) as total_minutes')
        ->first();

    $totalHours   = $driverOnlineData->total_hours  ?? 0;
    $totalMinutes = $driverOnlineData->total_minutes ?? 0;
    $totalHours  += intdiv($totalMinutes, 60);
    $totalMinutes = $totalMinutes % 60;

    $onlineHoursFormatted = round($totalHours + ($totalMinutes / 60), 1);

    // =====================================================
    //  Completion Rate
    // ====================================================
    $completionRate = $thisWeekTotalRequests > 0
        ? round(($thisWeekCompletedRequests / $thisWeekTotalRequests) * 100, 1)
        : 0;

    // =====================================================
    // Weekly Trips (Mon-Sun)
    // =====================================================
    $weeklyTrips = [];
    $days       = [trans("$string_file.mon"),trans("$string_file.tue"), trans("$string_file.wed"), trans("$string_file.thurs"), trans("$string_file.fri"), trans("$string_file.sat"), trans("$string_file.sun")];
    $peakDay     = ['day' => '', 'trips' => 0];

    for ($i = 0; $i < 7; $i++) {
        $dayStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays($i)->startOfDay();
        $dayEnd   = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays($i)->endOfDay();

        $dayTrips = Booking::whereIn('driver_id', $driverIds)
            ->where('booking_status', 1005)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->count();

        if ($dayTrips > $peakDay['trips']) {
            $peakDay = ['day' => $days[$i], 'trips' => $dayTrips];
        }

        $weeklyTrips[] = [
            'day'      => $days[$i],
            'date'     => $dayStart->format('d M'),
            'trips'    => $dayTrips,
            'is_today' => $dayStart->isToday(),
        ];
    }

    $totalWeeklyTrips = array_sum(array_column($weeklyTrips, 'trips'));

    // =====================================================
    // Top Performers
    // =====================================================
    $topPerformers = [];

    foreach ($driverIds as $dId) {
        $trips = Booking::where('driver_id', $dId)
            ->where('booking_status', 1005)
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->count();

        //Only calculate earning if specific driver_id passed
        $earning = null;
        if ($driverId) {
            $bookingIds = Booking::where('driver_id', $dId)
                ->where('booking_status', 1005)
                ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
                ->pluck('id')->toArray();

            $earning = !empty($bookingIds)
                ? round(\App\Models\BookingTransaction::whereIn('booking_id', $bookingIds)
                    ->whereNotNull('fleet_earning')
                    ->sum('fleet_earning'), 2)
                : 0;
        }

        $rating = \App\Models\BookingRating::whereHas('Booking', function ($q) use ($dId, $thisWeekStart, $thisWeekEnd) {
                $q->where('driver_id', $dId)
                  ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd]);
            })
            ->avg('driver_rating_points');

        $topPerformers[$dId] = [
            'trips'   => $trips,
            'earning' => $earning, // null when all drivers
            'rating'  => round($rating ?? 0, 1),
        ];
    }

    uasort($topPerformers, fn($a, $b) => $b['trips'] <=> $a['trips']);
    $topPerformers = array_slice($topPerformers, 0, 10, true);

    $rank         = 1;
    $formattedTop = [];

    foreach ($topPerformers as $dId => $stats) {
        $driver = Driver::select('id', 'first_name', 'last_name', 'phoneNumber', 'profile_image')
            ->find($dId);

        if (!$driver) continue;

        $item = [
            'rank'            => $rank++,
            'driver_id'       => $driver->id,
            'name'            => $driver->first_name . ' ' . $driver->last_name,
            'phone'           => $driver->phoneNumber,
            'profile_picture' => $driver->profile_picture ?? null,
            'trips'           => $stats['trips'],
            'rating'          => $stats['rating'],
        ];

        //Only add earning if specific driver selected
        if ($driverId) {
            $item['earning'] = $stats['earning'];
        }

        $formattedTop[] = $item;
    }

    // =====================================================
    // Rating Distribution
    // =====================================================
    $ratingDistribution = [];
    $totalRatings       = 0;

    for ($star = 5; $star >= 1; $star--) {
        $count = \App\Models\BookingRating::whereHas('Booking', function ($q) use ($driverIds, $thisWeekStart, $thisWeekEnd) {
                $q->whereIn('driver_id', $driverIds)
                  ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd]);
            })
            ->where('driver_rating_points', $star)
            ->count();

        $ratingDistribution[$star] = $count;
        $totalRatings             += $count;
    }

    $formattedRatings = [];
    foreach ($ratingDistribution as $star => $count) {
        $formattedRatings[] = [
            'star'       => $star,
            'count'      => $count,
            'percentage' => $totalRatings > 0
                ? round(($count / $totalRatings) * 100, 1)
                : 0,
        ];
    }

    // =====================================================
    // Earning Summary — only when driver_id passed
    // =====================================================
    $earningSummary = null;
    if ($driverId) {
        $thisWeekBookingIds = Booking::where('driver_id', $driverId)
            ->where('booking_status', 1005)
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
            ->pluck('id')->toArray();

        $thisWeekEarning = !empty($thisWeekBookingIds)
            ? round(\App\Models\BookingTransaction::whereIn('booking_id', $thisWeekBookingIds)
                ->whereNotNull('fleet_earning')
                ->sum('fleet_earning'), 2)
            : 0;

        $earningSummary = [
            'this_week_earning' => $thisWeekEarning,
            'total_trips'       => $totalWeeklyTrips,
            'completion_rate'   => $completionRate,
            'online_time'       => $totalHours . ' hrs ' . $totalMinutes . ' min',
        ];
    }

    return [
        'filter' => [
            'driver_id'   => $driverId,
            'driver_name' => $driverId
                ? (Driver::find($driverId)?->first_name . ' ' . Driver::find($driverId)?->last_name)
                : 'All Drivers',
            'period'      => [
                'from' => Carbon::parse($thisWeekStart)->format('d M Y'),
                'to'   => Carbon::parse($thisWeekEnd)->format('d M Y'),
            ],
        ],

        'acceptance_rate' => [
            'this_week'  => $thisWeekAcceptanceRate,
            'last_week'  => $lastWeekAcceptanceRate,
            'diff'       => abs($acceptanceDiff),
            'direction'  => $acceptanceDirection,
            'label'      => ($acceptanceDiff >= 0 ? '+' : '') . $acceptanceDiff . '% '.trans("$string_file.vs_last_week"),
        ],

        'cancellation_rate' => [
            'this_week'  => $thisWeekCancellationRate,
            'last_week'  => $lastWeekCancellationRate,
            'diff'       => abs($cancellationDiff),
            'direction'  => $cancellationDirection,
            'label'      => ($cancellationDiff >= 0 ? '+' : '') . $cancellationDiff . '% '. trans("$string_file.vs_last_week"),
        ],

        'avg_rating' => [
            'rating' => $avgRating,
            'label'  => $ratingLabel,
        ],

        'online_hours' => [
            'hours'           => $onlineHoursFormatted,
            'hours_formatted' => $totalHours . ' ' . trans("$string_file.hrs") . $totalMinutes . ' '.trans("$string_file.min"),
            'label'           => trans("$string_file.this_week"),
        ],

        'completion_rate' => $completionRate,

        // Only present when driver_id passed
        'earning_summary' => $earningSummary,

        'weekly_trips' => [
            'total_trips' => $totalWeeklyTrips,
            'peak_day'    => $peakDay,
            'daily'       => $weeklyTrips,
        ],

        'top_performers'      => $formattedTop,
        'rating_distribution' => $formattedRatings,

        'performance_tips' => [
            // 'Maintain 95%+ acceptance rate for bonus rewards.',
            // 'Keep cancellation rate below 3%.',
            // 'Stay online during peak hours (8-10 AM, 6-9 PM)',
            trans("$string_file.performance_tips_content")
        ],
    ];
}

    // =====================================================
    // Empty Response
    // =====================================================
    private function emptyPerformanceResponse(): array
    {
        return [
            'filter'              => ['driver_id' => null, 'driver_name' => 'All Drivers'],
            'acceptance_rate'     => ['this_week' => 0, 'last_week' => 0, 'diff' => 0, 'direction' => 'same', 'label' => '0% vs last week'],
            'cancellation_rate'   => ['this_week' => 0, 'last_week' => 0, 'diff' => 0, 'direction' => 'same', 'label' => '0% vs last week'],
            'avg_rating'          => ['rating' => 0, 'label' => 'No ratings'],
            'online_hours'        => ['hours' => 0, 'label' => 'This week'],
            'weekly_trips'        => ['total_trips' => 0, 'peak_day' => ['day' => '', 'trips' => 0], 'daily' => []],
            'top_performers'      => [],
            'rating_distribution' => [],
            'performance_tips'    => [
                'Maintain 95%+ acceptance rate for bonus rewards.',
                'Keep cancellation rate below 3%.',
                'Stay online during peak hours (8-10 AM, 6-9 PM)',
            ],
        ];
    }
    
    public function getFleetDriverDetail($fleetId,$driverId){
        $driver = Driver::select('id','first_name','current_latitude','current_longitude')->where('id',$driverId)->first();
        return $driver;
    }
    
    // FleetService
    // FleetService — fetch the fleet model from the ID first
    public function getFleetVehicleDocuments($fleet_id,$string_file)
    {
        // Fetch full fleet model here, same pattern as other service methods
        $fleet = Fleet::find($fleet_id);
    
        if (empty($fleet)) {
            return [];
        }
    
        $merchant_id        = $fleet->merchant_id;
        $currentDate        = date('Y-m-d');
        $reminder_days      = $fleet->Merchant->Configuration->reminder_doc_expire;
        $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days . ' days'));
    
        $status_name = [
            0 => trans("$string_file.pending"),
            1 => trans("$string_file.pending_for_verification"),
            2 => trans("$string_file.verified"),
            3 => trans("$string_file.rejected"),
            4 => trans("$string_file.expired"),
        ];
    
        // Area document config
        $country_area_id = $fleet->country_area_id;
        $query = CountryArea::where('id', $country_area_id);
        $query->with(['VehicleDocuments' => function ($q) {
            $q->addSelect(
                'documents.id',
                'expire_date as expire_status',
                'documentNeed as document_mandatory',
                'document_number_required'
            );
            $q->where('documentStatus', 1);
        }]);
        $area_document = $query->first();
    
        if (empty($area_document)) {
            return [];
        }
    
        // All vehicles for this fleet
        $fleet_vehicles = FleetVehicle::where('fleet_id', $fleet->id)->get();
    
        if ($fleet_vehicles->isEmpty()) {
            return [];
        }
    
        $arr_vehicle_document_list = [];
    
        
        foreach ($fleet_vehicles as $vehicle) {
            if (empty($vehicle->id)) {
                continue;
            }
        
            $vehicle_type = $vehicle->vehicle_type_id;
        
            $vehicle_documents = $area_document->VehicleDocuments()
                ->wherePivot('vehicle_type_id', '=', $vehicle_type)
                ->get();
        
            // Step 1: Get driver_vehicle using fleet_vehicle_id column
            $driver_vehicle = \App\Models\DriverVehicle::where('fleet_vehicle_id', $vehicle->id)->first();
        
            // Step 2: Get documents using driver_vehicle.id
            $driver_vehicle_doc = [];
            $driver_vehicle_doc_id = [];
        
            if (!empty($driver_vehicle)) {
                $driver_vehicle_doc = \App\Models\DriverVehicleDocument::where([
                    ['status',            '=', 1],
                    ['driver_vehicle_id', '=', $driver_vehicle->id], // ← driver_vehicles.id not fleet_vehicles.id
                ])
                ->whereNotNull('document_id')
                ->get();
        
                $driver_vehicle_doc    = array_column($driver_vehicle_doc->toArray(), NULL, 'document_id');
                $driver_vehicle_doc_id = array_keys($driver_vehicle_doc);
            }
        
            $arr_vehicle_doc_list = [];
        
            foreach ($vehicle_documents as $keys => $values) {
                if ($vehicle_type == $values['pivot']->vehicle_type_id) {
        
                    $document_id                   = $values->id;
                    $doc_image                     = "";
                    $temp_verification_status_text = '';
                    $temp_doc_image                = "";
                    $temp_doc_status               = 0;
                    $verification_status           = 0;
                    $verification_status_text      = $status_name[$verification_status];
                    $expire_date_text              = '';
        
                    if (in_array($document_id, $driver_vehicle_doc_id)) {
        
                        $verification_status      = $driver_vehicle_doc[$document_id]['document_verification_status'] ?? 0;
                        $verification_status_text = $status_name[$verification_status];
                        $doc_image = get_image(
                            $driver_vehicle_doc[$document_id]['document'],
                            'vehicle_document', $merchant_id, true, false
                        );
        
                        $temp_document_file   = $driver_vehicle_doc[$document_id]['temp_document_file'];
                        $temp_document_status = $driver_vehicle_doc[$document_id]['temp_doc_verification_status'];
                        $temp_doc_image = !empty($temp_document_file)
                            ? get_image($temp_document_file, 'vehicle_document', $merchant_id, true, false)
                            : '';
        
                        switch ($temp_document_status) {
                            case 1:
                                $temp_verification_status_text = trans("$string_file.pending_for_verification");
                                break;
                            case 2:
                                $temp_verification_status_text = trans("$string_file.verified");
                                break;
                            case 3:
                                $temp_verification_status_text = trans("$string_file.rejected");
                                break;
                            case 4:
                                $temp_verification_status_text = trans("$string_file.expired");
                                break;
                        }
        
                        $expire_date      = $driver_vehicle_doc[$document_id]['expire_date'];
                        $expire_date_text = $expire_date ?? '';
        
                        if (
                            !empty($expire_date) &&
                            $expire_date >= $currentDate &&
                            $expire_date <= $reminder_last_date &&
                            $verification_status == 2
                        ) {
                            $verification_status_text = trans("$string_file.expire_date") . ' ' . $expire_date;
        
                            if (empty($temp_document_file) || in_array($temp_document_status, [3, 4])) {
                                $temp_doc_status = 1;
                            } elseif ($temp_document_status == 1) {
                                $temp_doc_status = 2;
                            }
                        }
                    }
        
                    $arr_vehicle_doc_list[] = [
                        "id"                                => $values->id,
                        "expire_status"                     => $values->expire_date,
                        "document_mandatory"                => $values->documentNeed,
                        "document_number_required"          => $values->document_number_required,
                        "document_file"                     => !empty($doc_image) ? $doc_image : get_image('stub_document'),
                        "documentname"                      => $values->DocumentName,
                        "document_verification_status"      => $verification_status_text,
                        "document_status_int"               => $verification_status,
                        'expire_date'                       => $expire_date_text,
                        "temp_doc_status"                   => $temp_doc_status,
                        'temp_document_file'                => $temp_doc_image,
                        'temp_document_verification_status' => $temp_verification_status_text,
                    ];
                }
            }
        
            $arr_vehicle_document_list[] = [
                'vehicle_id'         => $vehicle->id,
                'vehicle_type'       => $vehicle->VehicleType->VehicleTypeName,
                'vehicle_make'       => $vehicle->VehicleMake->VehicleMakeName,
                'vehicle_model'       => $vehicle->VehicleModel->VehicleModelName,
                'driver_vehicle_id'=> $driver_vehicle->id,
                'vehicle_type_image' => get_image(
                    $vehicle->VehicleType->vehicleTypeImage,
                    'vehicle', $merchant_id, true, false
                ),
                'vehicle_number'     => $driver_vehicle->vehicle_number,
                'document_list'      => $arr_vehicle_doc_list,
            ];
        }
    
        return $arr_vehicle_document_list;
    }
    
    public function getFleetScheduledRides($fleet, string $string_file, $requestType = 'TODAY')
    {
        // $rejectedBookingIds = \App\Models\FleetBookingRejection::where('fleet_id', $fleetId)
        //     ->pluck('booking_id');
    
        $bookings_query = Booking::where('booking_status', 1001) 
            ->where('booking_type', 2)
            ->where('country_area_id', $fleet->country_area_id)
            // ->whereNull('fleet_claimed_by')
            ->whereNull('driver_id')
            // ->whereNotIn('id', $rejectedBookingIds)
            ->with('Segment', 'CountryArea:id,timezone','BookingDeliveryDetail');
    
        // if (!empty($date)) {
        //     $bookings_query->where('later_booking_date', $date);
        // }
        $fleetTimezone = $fleet->CountryArea->timezone ?? 'UTC';
        $today = Carbon::now($fleetTimezone)->format('Y-m-d');
        if (!empty($requestType)) {
            if (strtoupper($requestType) == 'TODAY') {
                $bookings_query->where('later_booking_date', $today);
            } elseif (strtoupper($requestType) == 'UPCOMING') {
                $bookings_query->where('later_booking_date', '>', $today);
            }
        }
    
        $bookings = $bookings_query
            ->orderBy('later_booking_date', 'asc')
            ->orderBy('later_booking_time', 'asc')
            ->get();
    
        return $bookings->map(function ($b) use($fleet){
            $timezone = $b->CountryArea->timezone ?? 'UTC';
            
            $packageDetails = [];
            if ($b->segment_id == 2 && $b->BookingDeliveryDetail->isNotEmpty()) {
                $packageDetails = $b->BookingDeliveryDetail->map(function ($delivery) {
                    $items = !empty($delivery->vehicle_delivery_package_data)
                        ? json_decode($delivery->vehicle_delivery_package_data, true)
                        : [];
    
                    return [
                        'stop_no'        => $delivery->stop_no,
                        'drop_location'  => $delivery->drop_location,
                        'receiver_name'  => $delivery->receiver_name,
                        'receiver_phone' => $delivery->receiver_phone,
                        'package_items'  => $items, // no_of_box, length, width, height, weight, product_name
                    ];
                })->values();
            }
    
            return [
                'booking_id'          => $b->id,
                'merchant_booking_id' => $b->merchant_booking_id,
                'segment_id'          => $b->segment_id,
                'segment_name'        => $b->Segment->Name() ?? null,
                'pickup'              => $b->pickup_location,
                'drop'                => $b->drop_location,
                'scheduled_date'      => $b->later_booking_date,
                'scheduled_time'      => $b->later_booking_time,
                'scheduled_at_display'=> Carbon::parse($b->later_booking_date . ' ' . $b->later_booking_time, $timezone)->format('d M Y, h:i A'),
                'estimate_bill'       => $b->estimate_bill ? number_format($b->estimate_bill, 2) : 0,
                'pickup_latitude'=> $b->pickup_latitude,
                'pickup_longitude'=> $b->pickup_longitude,
                'currency'=> $fleet->CountryArea->Country->isoCode,
                "fleet_claimed_by"=> empty($b->fleet_claimed_by) ? false : true,
                'package_details'     => $packageDetails,
            ];
        })->values();
    }
    
    public function acceptFleetScheduledRide(int $fleetId, int $bookingId)
    {
        $fleet = Fleet::find($fleetId);
        
        $claimed = Booking::where('id', $bookingId)
            ->where('country_area_id', $fleet->country_area_id)
            ->where('booking_status', 1001)
            ->whereNull('fleet_claimed_by')
            ->whereNull('driver_id')
            ->update([
                'fleet_claimed_by' => $fleetId,
                'fleet_claimed_at' => Carbon::now('UTC'),
                'fleet_id'         => $fleetId,
            ]);
        
        $bookingAlreadyAccepted = Booking::where('id', $bookingId)
            ->where('country_area_id', $fleet->country_area_id)
            ->where('booking_status', 1012)
            ->exists();
    
        if ($claimed === 0) {
            return ['success' => false, 'message' => 'already_claimed'];
        }elseif($bookingAlreadyAccepted){
            return ['success' => false, 'message' => 'already_accepted'];
        }
    
        return ['success' => true, 'message' => 'accepted'];
    }
    
    public function getEligibleDriversForFleetBooking(int $fleetId, int $bookingId)
    {
        $fleet = Fleet::find($fleetId);
        $booking = Booking::find($bookingId);
    
        if (!$fleet || !$booking) {
            return ['success' => false, 'message' => 'not_found', 'data' => []];
        }
    
        if ($booking->fleet_claimed_by != $fleetId) {
            return ['success' => false, 'message' => 'not_accepted_by_you', 'data' => []];
        }
    
        $fleetDriverIds = FleetDriver::where('fleet_id', $fleetId)
            ->where('status', 1)
            ->where('fleet_signup_step',3)
            ->pluck('driver_id')
            ->toArray();
    
        if (empty($fleetDriverIds)) {
            return ['success' => true, 'data' => []];
        }
    
        // Exclude drivers who already declined this exact booking
        $declinedDriverIds = DB::table('booking_request_drivers')
            ->where('booking_id', $bookingId)
            ->where('request_status',3)
            ->pluck('driver_id')
            ->toArray();
    
        $fleetDriverIds = array_diff($fleetDriverIds, $declinedDriverIds);
        // ===== Segment 1 (Taxi) — no package matching =====
        if ($booking->segment_id == 1) {
            $driverVehiclesForTaxi = \App\Models\DriverVehicle::whereIn('driver_id', $fleetDriverIds)
                ->whereNotNull('driver_id')
                ->with('VehicleType', 'VehicleMake', 'VehicleModel')
                ->get()
                ->keyBy('driver_id');

            $drivers = Driver::whereIn('id', $fleetDriverIds)
                ->where('online_offline', 1)
                ->select('id', 'first_name', 'last_name', 'phoneNumber', 'profile_image', 'online_offline', 'free_busy')
                ->get()
                ->map(function ($driver) use ($driverVehiclesForTaxi) {
                    $vehicle = $driverVehiclesForTaxi[$driver->id] ?? null;

                    $driver->vehicle = $vehicle ? [
                        'driver_vehicle_id' => $vehicle->id,
                        'vehicle_number'    => $vehicle->vehicle_number,
                        'vehicle_type'      => $vehicle->VehicleType->VehicleTypeName ?? null,
                        'vehicle_make'      => $vehicle->VehicleMake->VehicleMakeName ?? null,
                        'vehicle_model'     => $vehicle->VehicleModel->vehicleModelName ?? null
                    ] : null;
                    $driver->profile_image = get_image($driver->profile_image, 'driver', $driver->merchant_id, true, false, 'driver');
                    return $driver;
                });
    
            return ['success' => true, 'data' => $drivers];
        }
    
        // ===== Segment 2 (Delivery) — package capacity matching =====
        $deliveryDetails = \App\Models\BookingDeliveryDetails::where('booking_id', $booking->id)->get();
    
        if ($deliveryDetails->isEmpty()) {
            return ['success' => false, 'message' => 'delivery_data_not_found', 'data' => []];
        }
    
        $merchant = $fleet->Merchant;
        $volumetricDivisor = isset($merchant->BookingConfiguration->volumetric_capacity_calculation)
            ? (float) $merchant->BookingConfiguration->volumetric_capacity_calculation
            : 1.00;
    
        $driverVehicles = \App\Models\DriverVehicle::whereIn('driver_id', $fleetDriverIds)
            ->whereNotNull('driver_id')
            ->with('VehicleType', 'VehicleMake', 'VehicleModel')
            ->get()
            ->keyBy('driver_id');
    
        $eligibleDriverIds = [];
    
        foreach ($driverVehicles as $driverId => $driverVehicle) {
            $vehicleType = $driverVehicle->VehicleType;
            if (!$vehicleType) continue;
    
            $maxVehicleWeight = (float) $vehicleType->package_weight_range;
            $volCapRange = $vehicleType->volumetric_capacity;
    
            $min = 0;
            $max = 0;
            if ($volCapRange) {
                list($min, $max) = explode('-', $volCapRange);
                $min = (float) $min;
                $max = (float) $max;
            }
    
            $vehicleMatches = true;
    
            foreach ($deliveryDetails as $delivery) {
                $jsonData = $delivery->vehicle_delivery_package_data;
                if (empty($jsonData)) continue;
    
                $packageItems = json_decode($jsonData, true);
    
                foreach ($packageItems as $item) {
                    $no_of_box = $item['no_of_box'];
                    $volCap = (float)$item['length'] * (float)$item['width'] * (float)$item['height'];
                    if ($volCap == 0.0) {
                        if ($vehicleType->engine_type != 2) {
                            $vehicleMatches = false;
                            break 2;
                        }
                    } else {
                        $volumetric_capacity = $volCap / $volumetricDivisor;
                        $totalWeight = (float)$item['weight'] * (float)$no_of_box;
                        $totalVolCapacity = (float)$volumetric_capacity * (float)$no_of_box;
                        if ($maxVehicleWeight < $totalWeight || $max < $totalVolCapacity) {
                            $vehicleMatches = false;
                            break 2;
                        }
                    }
                }
            }
    
            if ($vehicleMatches) {
                $eligibleDriverIds[] = $driverId;
            }
        }
        $drivers = Driver::whereIn('id', $eligibleDriverIds)
            ->where('online_offline', 1)
            ->select('id', 'first_name', 'last_name', 'phoneNumber', 'profile_image', 'online_offline', 'free_busy')
            ->get()
            ->map(function ($driver) use ($driverVehicles) {
                $vehicle = $driverVehicles[$driver->id] ?? null;
                $merchant_id = $vehicle->merchant_id;
                $vehicleImage = get_image($vehicle->vehicle_image, 'vehicle_document',NULL , true, false);
                $driver->vehicle = $vehicle ? [
                    'driver_vehicle_id' => $vehicle->id,
                    'vehicle_image'     => $vehicleImage,
                    'vehicle_number'    => $vehicle->vehicle_number,
                    'vehicle_type'      => $vehicle->VehicleType->VehicleTypeName ?? null,
                    'vehicle_make'      => $vehicle->VehicleMake->VehicleMakeName ?? null,
                    'vehicle_model'     => $vehicle->VehicleModel->vehicleModelName ?? null,
                    'package_weight_range'=> $vehicle->VehicleType->package_weight_range,
                    'volumetric_capacity'=> $vehicle->VehicleType->volumetric_capacity
                ] : null;
                $driver->profile_image = get_image($driver->profile_image, 'driver', $merchant_id, true, false, 'driver');
                return $driver;
            });
            
        return ['success' => true, 'data' => $drivers];
    }
    
    public function assignDriverToFleetBooking(int $fleetId, int $bookingId, int $driverId)
    {
        $belongsToFleet = FleetDriver::where('fleet_id', $fleetId)
            ->where('driver_id', $driverId)
            ->where('status', 1)
            ->where('fleet_signup_step',3)
            ->exists();
    
        if (!$belongsToFleet) {
            return ['success' => false, 'message' => 'driver_not_in_fleet'];
        }
    
        $booking = Booking::find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'booking_not_found'];
        }
    
        if ($booking->fleet_claimed_by != $fleetId) {
            return ['success' => false, 'message' => 'not_accepted_by_you'];
        }
    
        if ($booking->segment_id == 2) {
            $eligibleResult = $this->getEligibleDriversForFleetBooking($fleetId, $bookingId);
            $eligibleIds = collect($eligibleResult['data'])->pluck('id')->toArray();
    
            if (!in_array($driverId, $eligibleIds)) {
                return ['success' => false, 'message' => 'driver_not_eligible'];
            }
        }
        
        $driverVehicle = \App\Models\DriverVehicle::where('driver_id',$driverId)->where('fleet_id',$fleetId)->first();
        Booking::where('id', $bookingId)
        ->where('fleet_claimed_by', $fleetId)
        ->update([
            'fleet_assigned_driver_id' => $driverId,
            'booking_status'=> 1013,
            'driver_id'=> $driverId,
            'driver_vehicle_id'=> $driverVehicle->id
        ]);
        
        $driver = Driver::find($driverId);
        $driver->driver_id = $driver->id;
        $drivers = collect([$driver]); // AssignRequest/SendNotificationToDrivers expect a collection
        (new \App\Http\Controllers\Helper\FindDriverController())->AssignRequest($drivers, $booking->id);
        (new \App\Http\Controllers\Helper\BookingDataController())->SendNotificationToDrivers($booking, $drivers);
    
    
        return ['success' => true, 'message' => 'request_sent'];
    }
    
    public function getFleetAssignedBookings(int $fleetId, string $string_file, $date = null)
    {
        $fleet = Fleet::find($fleetId);
        if (!$fleet) {
            return [];
        }
    
        $bookings = Booking::where('fleet_claimed_by', $fleetId)
            ->whereNotNull('fleet_assigned_driver_id')
            ->where('booking_type', 2)
            ->with(['Segment', 'CountryArea:id,timezone', 'BookingDeliveryDetail'])
            ->orderBy('later_booking_date', 'desc')
            ->orderBy('later_booking_time', 'desc');
    
        if (!empty($date)) {
            $bookings->where('later_booking_date', $date);
        }
    
        $bookings = $bookings->get();
    
        // Preload driver + vehicle info for all assigned drivers in one shot
        $driverIds = $bookings->pluck('fleet_assigned_driver_id')->unique()->filter()->values();
    
        $drivers = Driver::whereIn('id', $driverIds)
            ->select('id', 'first_name', 'last_name', 'phoneNumber', 'profile_image', 'merchant_id', 'online_offline', 'free_busy')
            ->get()
            ->keyBy('id');
    
        $driverVehicles = \App\Models\DriverVehicle::whereIn('driver_id', $driverIds)
            ->whereNotNull('driver_id')
            ->with('VehicleType', 'VehicleMake', 'VehicleModel')
            ->get()
            ->keyBy('driver_id');
    
        return $bookings->map(function ($b) use ($fleet, $drivers, $driverVehicles) {
            $timezone = $b->CountryArea->timezone ?? 'UTC';
    
            $driver = $drivers[$b->fleet_assigned_driver_id] ?? null;
            $vehicle = $driver ? ($driverVehicles[$driver->id] ?? null) : null;
    
            $driverData = null;
            if ($driver) {
                $driverData = [
                    'driver_id'      => $driver->id,
                    'name'           => $driver->first_name . ' ' . $driver->last_name,
                    'phone'          => $driver->phoneNumber,
                    'profile_image'  => get_image($driver->profile_image, 'driver', $driver->merchant_id, true, false, 'driver'),
                    'online_offline' => $driver->online_offline,
                    'free_busy'      => $driver->free_busy,
                    'vehicle'        => $vehicle ? [
                        'driver_vehicle_id' => $vehicle->id,
                        'vehicle_image' => get_image($vehicle->vehicle_image, 'vehicle_document',NULL , true, false),
                        'vehicle_number'    => $vehicle->vehicle_number,
                        'vehicle_type'      => $vehicle->VehicleType->VehicleTypeName ?? null,
                        'vehicle_make'      => $vehicle->VehicleMake->VehicleMakeName ?? null,
                        'vehicle_model'     => $vehicle->VehicleModel->vehicleModelName ?? null,
                    ] : null,
                ];
            }
    
            $packageDetails = [];
            if ($b->segment_id == 2 && $b->BookingDeliveryDetail->isNotEmpty()) {
                $packageDetails = $b->BookingDeliveryDetail->map(function ($delivery) {
                    $items = !empty($delivery->vehicle_delivery_package_data)
                        ? json_decode($delivery->vehicle_delivery_package_data, true)
                        : [];
    
                    return [
                        'stop_no'        => $delivery->stop_no,
                        'drop_location'  => $delivery->drop_location,
                        'receiver_name'  => $delivery->receiver_name,
                        'receiver_phone' => $delivery->receiver_phone,
                        'package_items'  => $items,
                    ];
                })->values();
            }
    
            return [
                'booking_id'          => $b->id,
                'merchant_booking_id' => $b->merchant_booking_id,
                'segment_id'          => $b->segment_id,
                'segment_name'        => $b->Segment->Name() ?? null,
                'booking_status'      => $b->booking_status,
                'pickup'              => $b->pickup_location,
                'drop'                => $b->drop_location,
                'scheduled_date'      => $b->later_booking_date,
                'scheduled_time'      => $b->later_booking_time,
                'scheduled_at_display'=> Carbon::parse($b->later_booking_date . ' ' . $b->later_booking_time, $timezone)->format('d M Y, h:i A'),
                'estimate_bill'       => $b->estimate_bill ? number_format($b->estimate_bill, 2) : 0,
                'final_amount'        => $b->final_amount_paid ? number_format($b->final_amount_paid, 2) : 0,
                'currency'            => $fleet->CountryArea->Country->isoCode ?? null,
                'driver'              => $driverData,
                'package_details'     => $packageDetails,
            ];
        })->values();
    }
}
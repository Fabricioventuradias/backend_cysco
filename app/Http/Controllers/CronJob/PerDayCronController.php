<?php

namespace App\Http\Controllers\CronJob;

use App\Http\Controllers\Helper\DriverRecords;
use App\Http\Controllers\Merchant\ExpireDocumentController;
use App\Http\Controllers\Merchant\ReferralSystemController;
use App\Models\BookingTransaction;
use App\Models\BusinessSegment\BusinessSegmentConfigurations;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\CarpoolingConfiguration;
use App\Models\Configuration;
use App\Models\Corporate;
use App\Models\CorporateInvoice;
use App\Models\CorporateInvoiceDetail;
use App\Models\CorporateSettlementLog;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverRenewableSubscriptionRecord;
use App\Models\DriverSubscriptionRecord;
use App\Models\DriverVehicle;
use App\Models\DriverVehicleDocument;
use App\Models\HandymanBiddingOrder;
use App\Models\HandymanOrder;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Traits\MerchantTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ExpireDocument;
use App\Models\BusinessSegment\Order;
use DB;
use App\Models\User;
use App\Http\Controllers\Helper\WalletTransaction;
//use App\Traits\HandymanTrait;
use App\Traits\OrderTrait;
use Illuminate\Support\Facades\Redis;
use App\Models\Booking;
use App\Models\ThirdPartyIntegration;
use App\Models\ThirdPartyIntegrationConfiguration;
use Firebase\JWT\JWT;
use App\Models\GiftCard;
use App\Models\CarpoolingRide;
use App\Models\CarpoolingRideDetail;
use App\Jobs\RunPerDayCronJob;


class PerDayCronController extends Controller
{
    /*************** Document expire cron start **************/
    // call cron function
    use ExpireDocument, MerchantTrait,OrderTrait;
    
    public function document()
    {
//         $this->dailyreport();
//         $this->processScheduledGiftCards();
//         $this->expirepreviousdayrides();
// //        $this->checkPersonalDocument();
// //        $this->checkVehicleDocument();
//         $this->getExpiredDocument();
//         $this->getDocumentExpireReminder();
//         $this->reminderNotificationForOrderDelivery(); //today's delivery reminder
//         $this->expireAcceptedOrders(); //expire accepted orders which were not delivered on delivery date

//         // User Documents
//         $this->getExpiredUserDocument();
//         $this->getDocumentUserExpireReminder();
//         $this->OfflineExpiredRenewableSubscribedDrivers();
//         $this->ApiUsageTrackingFromRedis();
//         $this->corporateSettlement();
//         $this->OfflineOlineForTimeRecord();
//         $this->BookingUpdateStatusForThirdParty();

//         //fleet vehicle doc expired
//         $this->getFleetExpiredVehicleDocument();       // mark expired fleet driver docs
//         $this->getFleetVehicleDocumentExpireReminder(); // remind upcoming expiry
        
        RunPerDayCronJob::dispatch();
        return response()->json(['status' => 'dispatched']);
    }
    
    public function runDailyTasks()
    {
        $startTime = microtime(true);
        \Log::channel('per_day_cron_log')->info('===== runDailyTasks STARTED =====');
    
        $tasks = [
            'dailyreport',
            'processScheduledGiftCards',
            'expirepreviousdayrides',
            'getExpiredDocument',
            'getDocumentExpireReminder',
            'reminderNotificationForOrderDelivery',
            'expireAcceptedOrders',
            'getExpiredUserDocument',
            'getDocumentUserExpireReminder',
            'OfflineExpiredRenewableSubscribedDrivers',
            'corporateSettlement',
            'BookingUpdateStatusForThirdParty',
            'getFleetExpiredVehicleDocument',
            'getFleetVehicleDocumentExpireReminder',
            'ApiUsageTrackingFromRedis',
            'OfflineOlineForTimeRecord',
            // 'chargeFleetRent'
        ];
    
        $results = [];
    
        foreach ($tasks as $task) {
            $taskStart = microtime(true);
            try {
                $this->$task();
                $duration = round(microtime(true) - $taskStart, 2);
                \Log::channel('per_day_cron_log')->info("[OK] {$task} completed in {$duration}s");
                $results[$task] = 'OK';
            } catch (\Throwable $e) {
                $duration = round(microtime(true) - $taskStart, 2);
                \Log::channel('per_day_cron_log')->error("[FAILED] {$task} after {$duration}s: " . $e->getMessage(), [
                    'exception' => $e->getTraceAsString(),
                ]);
                $results[$task] = 'FAILED: ' . $e->getMessage();
                // continue to next task instead of stopping the whole run
            }
        }
    
        $totalDuration = round(microtime(true) - $startTime, 2);
        \Log::channel('per_day_cron_log')->info('===== runDailyTasks COMPLETE in ' . $totalDuration . 's =====', $results);
    }

    public function getExpiredDocument()
    {
        $drivers = $this->getAllExpireDriversDocument(NULL, 2, false);
        if (!empty($drivers) && $drivers->count() > 0) {
            foreach ($drivers as $driver) {
                // personal document case
                $notification_status = false;
                if ($driver->DriverDocument->count() > 0) {
                    $notification_status = false;
                    foreach ($driver->DriverDocument as $driverDoc) {
                        if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                            if($driverDoc->temp_doc_verification_status == 1) {
                                $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                $driver->is_approved = 2;
                                $driver->save();
                            }
                            $driverDoc->document_file = $driverDoc->temp_document_file;
                            $driverDoc->expire_date = $driverDoc->temp_expire_date;
                            $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                            $driverDoc->temp_document_file = null;
                            $driverDoc->temp_expire_date = null;
                            $driverDoc->temp_doc_verification_status = null;
                            $driverDoc->save();
                        } else {
                            $notification_status = true;
                            $driverDoc->document_verification_status = 4;
                            $driverDoc->save();
                            $driver->online_offline = 2;
                            $driver->save();
                        }
                    }
                }

                if ($driver->segment_group_id == 2) {
                    // segment document case
                    if ($driver->DriverSegmentDocument->count() > 0) {
                        $notification_status = false;
                        foreach ($driver->DriverSegmentDocument as $driverDoc) {
                            if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                                if($driverDoc->temp_doc_verification_status == 1) {
                                    $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                    $driver->is_approved= 2;
                                    $driver->save();
                                }
                                $driverDoc->document_file = $driverDoc->temp_document_file;
                                $driverDoc->expire_date = $driverDoc->temp_expire_date;
                                $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                                $driverDoc->temp_document_file = null;
                                $driverDoc->temp_expire_date = null;
                                $driverDoc->temp_doc_verification_status = null;
                                $driverDoc->save();
                            } else {
                                $notification_status = true;
                                $driverDoc->document_verification_status = 4;
                                $driverDoc->save();
                                $driver->online_offline = 2;
                                $driver->save();
                            }
                        }
                    }
                } else {
                    // vehicle document
                    if ($driver->DriverVehicles->count() > 0) {
                        $notification_status = false;
                        foreach ($driver->DriverVehicles as $driverVehicle) {
                            foreach ($driverVehicle->DriverVehicleDocument as $driverDoc) {
                                if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                                    if($driverDoc->temp_doc_verification_status == 1) {
                                        $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                        $driver->is_approved = 2;
                                        $driver->save();
                                    }
                                    $driverDoc->document = $driverDoc->temp_document_file;
                                    $driverDoc->expire_date = $driverDoc->temp_expire_date;
                                    $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                                    $driverDoc->temp_document_file = null;
                                    $driverDoc->temp_expire_date = null;
                                    $driverDoc->temp_doc_verification_status = null;
                                    $driverDoc->save();
                                } else {
                                    $notification_status = true;
                                    $driverDoc->document_verification_status = 4;
                                    $driverDoc->save();
                                    $driver->online_offline = 2;
                                    $driver->save();
                                }
                            }
                        }
                    }
                }

                if ($notification_status == true) {
                    $string_file = $this->getStringFile($driver->merchant_id);
                    setLocal($driver->language);
                    $data['notification_type'] = "DOCUMENT_EXPIRED";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['driver_id' => $driver->id, 'data' => $data, 'message' => trans("$string_file.document_expired_error"), 'merchant_id' => $driver->merchant_id, 'title' => trans("$string_file.document_expired")];
                    $a = Onesignal::DriverPushMessage($arr_param);
                }
            }
        }
    }

    public function getDocumentExpireReminder()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id', '=', 0)->get();
        $expire_class = new ExpireDocumentController;
        foreach ($merchants as $merchant) {
            $reminder_days = Configuration::where('merchant_id', '=', $merchant->id)->select('reminder_doc_expire')->first();
            $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days->reminder_doc_expire . ' days'));
            $drivers = $expire_class->getDocumentGoingToExpire($currentDate, $reminder_last_date, $merchant->id)->get();
            $Ids = array();
            foreach ($drivers as $driver) {
                if (!empty($driver->player_id) && $driver->player_id != null) {
                    $Ids[] = $driver->id; // send driver id
                }
            }
            if (count($Ids) > 0) {
                $string_file = $this->getStringFile($merchant->id);
                foreach($Ids as $id){
                    $driver = Driver::find($id);
                    setLocal($driver->language);
                    $data['notification_type'] = "DOCUMENT_EXPIRE_REMINDER";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['driver_id' => $id, 'data' => $data, 'message' => trans("$string_file.document_expire_warning"), 'merchant_id' => $merchant->id, 'title' => trans("$string_file.document_expire")];
                    Onesignal::DriverPushMessage($arr_param);
                }
            }
        }
    }

    public function getExpiredUserDocument()
    {
        $users = $this->getAllExpireUserDocument(NULL, 2,false);

        if(!empty($users) && $users->count() > 0)
        {
            foreach ($users as $user)
            {
                $notification_status = false;

                // personal document case
                if($user->Merchant->ApplicationConfiguration->user_document == 1){
                    if($user->UserDocument->count() > 0)
                    {
                        $notification_status = false;
                        foreach ($user->UserDocument as $UserDoc)
                        {
                            if ($UserDoc->temp_document_file != null)
                            {
                                $UserDoc->document_file =$UserDoc->temp_document_file;
                                $UserDoc->expire_date = $UserDoc->temp_expire_date;
                                $UserDoc->document_verification_status = $UserDoc->temp_doc_verification_status;
                                $UserDoc->temp_document_file = null;
                                $UserDoc->temp_expire_date = null;
                                $UserDoc->temp_doc_verification_status = null;
                                $UserDoc->save();
                            }
                            else{
                                $notification_status = true;
                                $UserDoc->document_verification_status = 4;
                                $UserDoc->save();
                            }
                        }
                    }
                }

                // vehicle document
                if($user->Merchant->Segment->where("segment_group_id",3)->count() > 0){
                    if($user->UserVehicles->count() > 0)
                    {
                        $notification_status = false;
                        foreach ($user->UserVehicles as $userVehicle)
                        {
                            foreach ($userVehicle->UserVehicleDocument as $UserDoc)
                            {
                                if ($UserDoc->temp_document_file != null)
                                {
                                    $UserDoc->document = $UserDoc->temp_document_file;
                                    $UserDoc->expire_date = $UserDoc->temp_expire_date;
                                    $UserDoc->document_verification_status = $UserDoc->temp_doc_verification_status;
                                    $UserDoc->temp_document_file = null;
                                    $UserDoc->temp_expire_date = null;
                                    $UserDoc->temp_doc_verification_status = null;
                                    $UserDoc->save();
                                }
                                else{
                                    $notification_status = true;
                                    $UserDoc->document_verification_status = 4;
                                    $UserDoc->save();
                                }
                            }
                        }
                    }
                }

                if($notification_status == true)
                {
                    $data['notification_type'] = "DOCUMENT_EXPIRED";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['user_id' => $user->id, 'data' => $data, 'message' => trans("common.document_expired_error"), 'merchant_id' => $user->merchant_id, 'title' => trans("common.document").' '.trans("common.expired")];
                    Onesignal::UserPushMessage($arr_param);
                }
            }
        }
    }

    public function getDocumentUserExpireReminder()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id','=',0)->get();
        $expire_class = new ExpireDocumentController;
        foreach ($merchants as $merchant) {
            $reminder_days = Configuration::where('merchant_id','=',$merchant->id)->select('reminder_doc_expire')->first();
            $carpooling_config = CarpoolingConfiguration::where('merchant_id','=',$merchant->id)->select('user_document_reminder_time')->first();
            $vehicle_reminder_days = !empty($carpooling_config) ? $carpooling_config->user_document_reminder_time : $reminder_days->reminder_doc_expire;
            $reminder_last_date = date('Y-m-d',strtotime('+'.$reminder_days->reminder_doc_expire.' days'));
            $vehicle_reminder_last_date = date('Y-m-d',strtotime('+'.$vehicle_reminder_days.' days'));
            $users = $expire_class->getUserDocumentGoingToExpire($currentDate,$reminder_last_date,$vehicle_reminder_last_date,$merchant->id)->get();
            $Ids = array();
            foreach ($users as $user) {
                if (!empty($user->player_id) && $user->player_id != null){
                    $Ids[] = $user->id; // send driver id
                }
            }
            if(count($Ids) > 0)
            {
                $data['notification_type'] = "DOCUMENT_EXPIRE_REMINDER";
                $data['segment_sub_group'] = NULL;
                $data['segment_group_id'] = NULL;
                $data['segment_type'] = "";
                $data['segment_data'] = [];
                $arr_param = ['user_id' => $Ids, 'data' => $data, 'message' => trans("common.document_expire_warning"), 'merchant_id' => $merchant->id, 'title' => trans("common.document").' '.trans("common.expire")];
                Onesignal::UserPushMessage($arr_param);
            }
        }
    }

    public function getFleetExpiredVehicleDocument ()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id', 0)->with('Configuration')->get();
        foreach ($merchants as $merchant) {
            if (!isset($merchant->Configuration->fleet_company) 
                || $merchant->Configuration->fleet_company != 1) {
                continue;
            }
            $driverVehicleIds = DriverVehicle::whereHas('Fleet', function ($q) use ($merchant) {
                    $q->where('merchant_id', $merchant->id);
                })->pluck('id');
    
            if ($driverVehicleIds->isEmpty()) continue;
            // Fetch individually to send notification per document
            $expiredDocuments = DriverVehicleDocument::with(['DriverVehicle.Fleet', 'DriverVehicle.Driver'])
                ->whereIn('driver_vehicle_id', $driverVehicleIds)
                ->whereNotNull('expire_date')
                ->where('expire_date', '<', $currentDate)
                ->where('document_verification_status', '!=', 4)
                ->get();
            
            foreach ($expiredDocuments as $document) {
                    // Mark as expired
                    $document->update(['document_verification_status' => 4]);
                    // Send push notification
                    try {
                        $driverVehicle = $document->DriverVehicle;
                        $driver_id     = $driverVehicle->Driver->id ?? null;
                        $fleet_id      = $driverVehicle->fleet_id ?? null;
                        if ($fleet_id) {
                            setLocal($driverVehicle->Fleet->language);
                            $string_file = $this->getStringFile($merchant->id);
                            $msg   = trans("$string_file.your") . " " . trans("$string_file.document") . " " . trans("$string_file.has") . " " . trans("$string_file.been") . " " . trans("$string_file.expired");
                            $title = trans("$string_file.document") . " " . trans("$string_file.expired");
        
                            $data['notification_type'] = "DOCUMENT_EXPIRED";
        
                            $arr_param = [
                                'fleet_id'    => $fleet_id,
                                'driver_id'   => $driver_id,
                                'data'        => $data,
                                'message'     => $msg,
                                'merchant_id' => $merchant->id,
                                'title'       => $title,
                            ];
        
                            (new Onesignal())->FleetPushMessage($arr_param);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Fleet document expired notification failed: ' . $e->getMessage());
                    }
            }
        }
    }

    public function getFleetVehicleDocumentExpireReminder()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id', 0)->with('Configuration')->get();
        foreach ($merchants as $merchant) {
            if (!isset($merchant->Configuration->fleet_company) 
                || $merchant->Configuration->fleet_company != 1) {
                continue;
            }
            $config = Configuration::where('merchant_id', $merchant->id)
                ->select('reminder_doc_expire')
                ->first();
    
            if (!$config || empty($config->reminder_doc_expire)) continue;
    
            $reminderLastDate = date('Y-m-d', strtotime('+' . $config->reminder_doc_expire . ' days'));
    
            $driverVehicleIds = DriverVehicle::whereHas('Fleet', function ($q) use ($merchant) {
                    $q->where('merchant_id', $merchant->id);
                })->pluck('id');
    
            if ($driverVehicleIds->isEmpty()) continue;
    
            $upcomingDocuments = DriverVehicleDocument::with(['DriverVehicle.Fleet', 'DriverVehicle.Driver'])
                ->whereIn('driver_vehicle_id', $driverVehicleIds)
                ->whereNotNull('expire_date')
                ->whereBetween('expire_date', [$currentDate, $reminderLastDate])
                ->whereNotIn('document_verification_status', [4, 5])
                ->get();
    
            foreach ($upcomingDocuments as $document) {
                // Send reminder push notification
                try {
                    $driverVehicle = $document->DriverVehicle;
                    $driver_id     = $driverVehicle->Driver->id ?? null;
                    $fleet_id      = $driverVehicle->fleet_id ?? null;
    
                    if ($fleet_id) {
                        setLocal($driverVehicle->Fleet->language);
                        $string_file = $this->getStringFile($merchant->id);
                        $msg   = trans("$string_file.your") . " " . trans("$string_file.document") . " " . trans("$string_file.has") . " " . trans("$string_file.been") . " " . trans("$string_file.expiring_soon");
                        $title = trans("$string_file.document") . " " . trans("$string_file.expiring_soon");
    
                        $data['notification_type'] = "DOCUMENT_EXPIRING_SOON";
                        $arr_param = [
                            'fleet_id'    => $fleet_id,
                            'driver_id'   => $driver_id,
                            'data'        => $data,
                            'message'     => $msg,
                            'merchant_id' => $merchant->id,
                            'title'       => $title,
                        ];
    
                        (new Onesignal())->FleetPushMessage($arr_param);
                    }
                } catch (\Exception $e) {
                    \Log::error('Fleet document expiry reminder notification failed: ' . $e->getMessage());
                }
            }
        }
    }
    /*************** Document expire cron end **************/


    /*************** Subscription Package expire cron start **************/
    public function subscriptionPackage()
    {
        $this->ExpireSubscriptionPackage();
    }

    public function ExpireSubscriptionPackage()
    {
        $active_packages = DriverSubscriptionRecord::select('id')->where([['status', '!=', 3], ['end_date_time', '<', date('Y-m-d H:i:s')]])->get();
        if ($active_packages->isNotEmpty()):
            DriverSubscriptionRecord::whereIn('id', $active_packages->toArray())
                ->update([
                    'status' => 3, // package expired
                ]);
        endif;
    }
    /*************** Subscription Package expire cron end **************/

    public function expireHandymanOrder()
    {
        $merchants = Merchant::whereHas("Segment", function ($query) {
            $query->where('segment_group_id', 2);
        })->get();
        if (!empty($merchants)) {
            foreach ($merchants as $merchant) {
                $handyman_orders = HandymanOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<',date('Y-m-d')]])->whereIn('order_status',[1,4])->get();
                if (!empty($handyman_orders)) {
                    HandymanOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<', date('Y-m-d')]])->whereIn('order_status',[1,4])->update(array('order_status' => 8));
                }
            }
        }
    }

    public function expireHandymanBiddingOrder()
    {
        $merchants = Merchant::whereHas("Segment", function ($query) {
            $query->where('segment_group_id', 2);
        })->get();
        if (!empty($merchants)) {
            foreach ($merchants as $merchant) {
                $handyman_bidding_orders = HandymanBiddingOrder::where([['merchant_id', '=', $merchant->id],['booking_date', '<',date('Y-m-d')], ['order_status', '=', 1]])->get();
                if (!empty($handyman_bidding_orders)) {
                    HandymanBiddingOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<', date('Y-m-d')],['order_status', '=', 1]])->update(array('order_status' => 3));
                }
            }
        }
    }

    /*************** Referral System expire cron end **************/

    public function expireReferralSystem()
    {
        $ref_controller = new ReferralSystemController();
        $ref_controller->checkExpireReferralSystem();
    }

    /*************** Referral System expire cron end **************/


    // order delivery reminder for the day
    public function reminderNotificationForOrderDelivery()
    {
        DB::beginTransaction();
        try {
            $current_date = date('Y-m-d');
            $yesterday_date = date('Y-m-d',strtotime("-1 days"));
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date','created_at')
                ->whereIn('order_status', [6,7,9,10])
                ->where(function($e) use($current_date,$yesterday_date){
                    $e->where('order_date','=',$current_date)
                        ->orWhere('order_date','=',$yesterday_date);
                })
                ->where('is_order_completed','!=',1)
//                ->where('segment_id','!=',3)
                ->get();
//            p($all_orders);
            if ($all_orders->isNotEmpty())
            {
                foreach ($all_orders as $order)
                {
                    $string_file = $this->getStringFile($order->merchant_id);
                    // reminder notification driver
                    $segment_data = [
                        'id'=>$order->id,
                        'order_status'=>$order->order_status,
                    ];
                    $data = array('order_id' => $order->id, 'notification_type' => 'ORDER_DELIVERY_REMINDER', 'segment_type' => $order->Segment->slag,'segment_data' => $segment_data);
                    $arr_param = array(
                        'driver_id' => $order->driver_id,
                        'data'=>$data,
                        'message'=>trans("$string_file.today_order_delivery_title"),
                        'merchant_id'=>$order->merchant_id,
                        'title' => trans("$string_file.today_order_delivery_message").' '.'#'.$order->merchant_order_id,
                    );
//                        p($arr_param);
                    Onesignal::DriverPushMessage($arr_param);
                }
            }
            DB::commit();
        }catch (\Exception $e)
        {
            $message = $e->getMessage();
            DB::rollBack();
        }
    }

    // expire accepted orders which were not delivered on time
    public function expireAcceptedOrders()
    {
        DB::beginTransaction();
        try {
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date','created_at')
                ->whereIn('order_status', [1,6,7,9,10])->get();
            if ($all_orders->isNotEmpty())
            {
                $order_ids = $all_orders->map(function ($item, $key) {
                    if($item->segment_id == 3) { // for food
                        $current_date =  date('Y-m-d');
                        $order_date = date('Y-m-d',strtotime($item->created_at));
                    }
                    else{
                        $current_date =  date('Y-m-d');
                        $order_date = $item->order_date;
                        // next date of order delivery date
                        $order_date = date("Y-m-d", strtotime("+1 day",strtotime($order_date)));
                    }
                    return ($current_date > $order_date  ) ? $item->id : null;
                })->filter()->values();
                // p($order_ids->toArray());
                if($order_ids->count() > 0)
                {
                    $log_data =[
                        'order_id'=>$order_ids->toArray(),
                        'hit_time'=> date('Y-m-d H:i:s'),
                        'request_type'=>"ongoing order expire"
                    ];
                    \Log::channel('per_day_cron_log')->emergency($log_data);

                    Order::whereIn('id', $order_ids->toArray())
                        ->update([
                            'order_status' => '12', //auto expired
                        ]);
                    $arr_orders = Order::
//                    select('id','merchant_id','driver_id','merchant_order_id','order_status','segment_id','user_id','payment_method_id','payment_option_id')->
                    whereIn('id', $order_ids->toArray())->get();
                    // p($arr_orders);
                    foreach ($arr_orders as $order)
                    {
                        // p($order);
                        // send notification to user like your order has been expired
                        $this->sendNotificationToUser($order);
// p($order);
                        // refund credit to user wallet if payment done while placing order
                        if(!empty($order->payment_method_id) && in_array($order->payment_method_id,[2,4,3]))
                        {
                            // p('z');
                            $user = User::select('wallet_balance','merchant_id','id')->where('id',$order->user_id)->first();
                            // p($user);
                            $user->wallet_balance = $user->wallet_balance + $order->final_amount_paid;
                            $user->save();
                            // p($user);
                            // send wallet credit notification
                            $paramArray = array(
                                'user_id' => $user->id,
                                'merchant_id' => $user->merchant_id,
                                'booking_id' => NULL,
                                'amount' => $order->final_amount_paid,
                                'order_id' => $order->id,
                                'narration' => 11,
                                'platform' => 2,
                                'payment_method' => $order->payment_method_id,
                                'payment_option_id' => $order->payment_option_id,
                                'transaction_id' => NULL
                            );
                            // p($paramArray);
                            WalletTransaction::UserWalletCredit($paramArray);
                        }

                        // make driver free once order expired
                        if ($order->order_status != 1 && isset($order->Driver)){
                            $driver = $order->Driver;
                            $driver->free_busy = 2;
                            $driver->save();
                        }
                        // p($driver);
                    }
                }
            }
            DB::commit();
        }catch (\Exception $e)
        {
            $message = $e->getMessage();
            DB::rollBack();
        }
    }

    public function closeLowWalletBalanceStore(): void
    {
        $business_segments = BusinessSegment::where("status", 1)->get();
        foreach ($business_segments as $bs){
            $wallet_balance_low = ($bs->wallet_amount <= 0|| empty($bs->wallet_amount));
            if($wallet_balance_low && $bs->Merchant->Configuration->check_wallet_for_order_receiving == 1){
                BusinessSegmentConfigurations::where("business_segment_id", $bs->id)
                    ->update([
                        "is_open" => 2
                    ]);
            }
        }
    }
    // public function chargeFleetRent()
    // {
    //     $nowUtc = Carbon::now('UTC');

    //     $dueRents = \App\Models\FleetDriverRent::where('status', 1)
    //         ->where('due_date', '<=', $nowUtc)
    //         ->get();

    //     foreach ($dueRents as $rent) {
    //         DB::beginTransaction();
    //         try {
    //             $driverBalance = Driver::find($rent->driver_id)->wallet_money ?? 0;

    //             if ($driverBalance >= $rent->amount) {
    //                 $ref = "RENT_" . time() . "_" . $rent->id;

    //                 WalletTransaction::WalletDeduct([
    //                     'driver_id'     => $rent->driver_id,
    //                     'amount'        => $rent->amount,
    //                     'wallet_status' => 'DEBIT',
    //                     'narration'     => 30,
    //                 ]);

    //                 WalletTransaction::FleetWalletCredit([
    //                     'fleet_id'  => $rent->fleet_id,
    //                     'amount'    => $rent->amount,
    //                     'receipt'   => $ref,
    //                     'narration' => 2,
    //                 ]);

    //                 $rent->update([
    //                     'status'  => 2,
    //                     'paid_at' => now(),
    //                     'wallet_transaction_ref' => $ref,
    //                 ]);
    //             } else {
    //                 $rent->update(['status' => 3]); // overdue

    //                 $driver = $rent->Driver;
    //                 $fleet = $rent->Fleet;
    //                 $merchant_id = $fleet->merchant_id;
    //                 $fleetTimezone = $fleet->CountryArea->timezone ?? 'UTC';

    //                 // Notify driver
    //                 setLocal($driver->language);
    //                 $string_file = $this->getStringFile($merchant_id);
    //                 $driverMsg = trans("$string_file.rent_payment_failed_message", ['amount' => $rent->amount]);
    //                 $driverTitle = trans("$string_file.rent_payment_failed_title");
    //                 $data1['notification_type'] = "RENT_PAYMENT_FAILED";
    //                 $data1['segment_type'] = "";
    //                 $data1['segment_data'] = [];
    //                 $arr_param = [
    //                     'driver_id'   => $rent->driver_id,
    //                     'data'        => $data1,
    //                     'message'     => $driverMsg,
    //                     'merchant_id' => $merchant_id,
    //                     'title'       => $driverTitle,
    //                     'large_icon'  => '',
    //                 ];
    //                 Onesignal::DriverPushMessage($arr_param);

    //                 // Notify fleet owner
    //                 setLocal($fleet->language);
    //                 $driverName = $driver->first_name . ' ' . $driver->last_name;
    //                 $fleetMsg = trans("$string_file.driver_rent_overdue_message", [
    //                     'driver_name' => $driverName,
    //                     'amount'      => $rent->amount,
    //                     'date'        => Carbon::parse($rent->due_date, 'UTC')->setTimezone($fleetTimezone)->format('d M Y, h:i A'),
    //                 ]);
    //                 $fleetTitle = trans("$string_file.driver_rent_overdue_title");

    //                 $data2['notification_type'] = "DRIVER_RENT_OVERDUE";
    //                 $arr_param2 = [
    //                     'fleet_id'    => $rent->fleet_id,
    //                     'merchant_id' => $merchant_id,
    //                     'data'        => $data2,
    //                     'message'     => $fleetMsg,
    //                     'title'       => $fleetTitle,
    //                     'driver_id'   => $rent->driver_id,
    //                 ];
    //                 Onesignal::FleetPushMessage($arr_param2);

    //                 setLocal();
    //             }

    //             // Create next period's rent row — rolling window from this cycle's due_date
    //             $nextDueUtc = $rent->frequency == 1
    //                 ? Carbon::parse($rent->due_date, 'UTC')->addDay()
    //                 : Carbon::parse($rent->due_date, 'UTC')->addWeek();

    //             FleetDriverRent::create([
    //                 'fleet_id'          => $rent->fleet_id,
    //                 'driver_id'         => $rent->driver_id,
    //                 'driver_vehicle_id' => $rent->driver_vehicle_id,
    //                 'amount'            => $rent->amount,
    //                 'frequency'         => $rent->frequency,
    //                 'due_date'          => $nextDueUtc,
    //                 'status'            => 1,
    //             ]);

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             \Log::channel('fleet_rent')->error($e->getMessage());
    //         }
    //     }
    // }




    public function OfflineExpiredRenewableSubscribedDrivers()
    {
        DB::beginTransaction();
        try{
            $driver_ids = [];
            DriverRenewableSubscriptionRecord::with('Driver')->chunk(500, function ($records) use (&$driver_ids) {
                foreach ($records as $record) {
                    $driver = $record->Driver;
                    if ( $driver && $driver->login_logout == 1 && !$driver->hasActiveRenewableSubscriptionRecord() && $driver->free_busy != 1 && $driver->online_offline == 1) {
                        $driver_ids[] = $driver->id;
                        $driver->online_offline = 2;
                        $newDriverRecord = new DriverRecords();
                        $newDriverRecord->OfflineTimeRecord($driver->id, $driver->merchant_id);
                        $driver->save();
                    }
                }
            });
            $log_data =[
                'driver_ids'=>$driver_ids,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"Offline Expired Renewable Subscribed Drivers"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        catch (\Exception $e){
            $message = $e->getMessage();
            DB::rollback();
            $log_data =[
                'exception'=>$message,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"Offline Expired Renewable Subscribed Drivers"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        DB::commit();
    }



    public function ApiUsageTrackingFromRedis()
    {
        DB::beginTransaction();
        try{
            $keys = Redis::keys('api_usage:*:*');
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                if (count($parts) !== 3) continue;
    
                [$static_key, $merchantId, $date] = $parts;
    
                // Guard against malformed/blank merchant IDs before hitting the DB
                if (empty($merchantId) || !is_numeric($merchantId)) {
                    \Log::channel('per_day_cron_log')->warning("Skipping malformed Redis key: {$key}");
                    continue;
                }
    
                $hash = Redis::hgetall($key);
                if (empty($hash)) continue;
    
                $structured = [];
                foreach ($hash as $field => $count) {
                    $segments = explode(':', $field);
                    if (count($segments) !== 3) continue;
                    [$mapType, $apiEndPoint, $providerEndPoint] = $segments;
                    $structured[] = [
                        'map_type'          => $mapType,
                        'api_end_point'     => $apiEndPoint,
                        'provider_end_point'=> $providerEndPoint,
                        'count'             => (int) $count,
                    ];
                }
    
                DB::table('api_usages')->updateOrInsert(
                    ['merchant_id' => $merchantId, 'date' => $date],
                    ['usage_record' => json_encode($structured), 'updated_at' => now()]
                );
            }
    
            \Log::channel('per_day_cron_log')->emergency([
                'message'=> "imported data from redis !",
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"ApiUsageTrackingFromRedis"
            ]);
        }
        catch(\Exception $e){   // <-- fixed: leading backslash, actually catches now
            $message = $e->getMessage();
            DB::rollback();
            \Log::channel('per_day_cron_log')->emergency([
                'exception'=>$message,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"ApiUsageTrackingFromRedis"
            ]);
        }
        DB::commit();
    }

    public function saveCorporateInvoice($admin, $totalCorporateCharges, $settlement_type, $transactions, $settlement_date_time, $last_settled_on)
    {
        try{
            $corporate_invoice = new CorporateInvoice();
            $corporate_invoice->corporate_id = $admin->id;
            $corporate_invoice->settlement_from_date= $last_settled_on;
            $corporate_invoice->settlement_date = $settlement_date_time;
            $corporate_invoice->settlement_type = $settlement_type;
            $corporate_invoice->status = 2;
            $corporate_invoice->settlement_amount = $totalCorporateCharges;
            $corporate_invoice->save();

            foreach ($transactions as $transaction) {
                $corporate_invoice_details = new CorporateInvoiceDetail();
                $corporate_invoice_details->corporate_invoice_id = $corporate_invoice->id;
                $corporate_invoice_details->booking_id =  $transaction->booking_id;
                $corporate_invoice_details->amount = isset($transaction->corporate_earning)?$transaction->corporate_earning : $transaction->Booking->final_amount_paid ;
                $corporate_invoice_details->save();
            }
        }
        catch (\Exception $e){
            return null;
        }
        return $corporate_invoice->id;
    }

    public function saveCorporateInvoiceSettlementLogs($corporate_id, $settlement_type, $settlement_date_time, $corporate_invoice_id, $settlement_amount): void
    {
        $invoice_log = new CorporateSettlementLog();
        $invoice_log->corporate_id = $corporate_id;
        $invoice_log->corporate_invoice_id = $corporate_invoice_id;
        $invoice_log->settlement_type = $settlement_type;
        $invoice_log->settlement_date = $settlement_date_time;
        $invoice_log->invoiced_amount = $settlement_amount;
        $invoice_log->save();
    }

    public function corporateSettlement()
    {
        DB::beginTransaction();
        try{
            $today = Carbon::today();

            $admins = Corporate::with("CorporateSettlementLogs")->whereIn('settlement_type', [1, 2, 3, 4])->get();

            foreach ($admins as $admin) {
                $logs = $admin->CorporateSettlementLogs()->orderby("id", "desc")->first();
                if (isset($logs) && $logs->settlement_date) {
                    $last_Settlement_date = Carbon::parse($logs->settlement_date)->format('Y-m-d H:i:s');
                } else {
                        switch ($admin->settlement_type) {
                            case 1: // weekly
                                $last_Settlement_date = now()->subDays(7)->format('Y-m-d H:i:s');
                                break;
                            case 2: // biweekly
                                $last_Settlement_date = now()->subDays(14)->format('Y-m-d H:i:s');
                                break;
                            case 3: // monthly
                                $last_Settlement_date = now()->subDays(30)->format('Y-m-d H:i:s');
                                break;
                            case 4: // custom
                                $last_Settlement_date = now()->subDays($admin->settlement_custom_days)->format('Y-m-d H:i:s');
                                break;
                            default:
                                $last_Settlement_date = now()->format('Y-m-d H:i:s');
                                break;
                        }
                    }
                $lastSettlement = isset($last_Settlement_date) ?  Carbon::parse($last_Settlement_date) : null;
                $search_from_date= isset($last_Settlement_date) ?  Carbon::parse($last_Settlement_date)->format('Y-m-d H:i:s') : null;
                $shouldSettle = false;
                $merchant_id = $admin->merchant_id;
                $corporate_id = $admin->id;
                $transactions = BookingTransaction::with(['Booking' => function ($q) use ($merchant_id, $corporate_id, $search_from_date) {
                    $q->where([
                        ['booking_status', '=', 1005],
                        ['merchant_id', '=', $merchant_id],
                        ['corporate_id', '=', $corporate_id],
                        ['segment_id', 1],
                    ]);
                    if(!empty($search_from_date)){
                        $q->whereDate('created_at', '>', $search_from_date);
                    }
                }])
                    ->whereHas('Booking', function ($q) use ($merchant_id, $corporate_id, $search_from_date) {
                        $q->where([
                            ['booking_status', '=', 1005],
                            ['merchant_id', '=', $merchant_id],
                            ['corporate_id', '=', $corporate_id],
                            ['segment_id', 1],
                        ]);
                        if(!empty($search_from_date)){
                            $q->whereDate('created_at', '>', $search_from_date);
                        }
                    })
                    ->get();
                // $totalCorporateCharges = $transactions->sum('corporate_earning') + $transactions->sum('customer_paid_amount');
                $totalCorporateCharges = $transactions->sum(function ($transaction) {
                                            $bookingDetail = $transaction->Booking->BookingDetail ?? null;
                                            return ($bookingDetail &&
                                                    $bookingDetail->is_instant_corporate_ride == 1 &&
                                                    isset($bookingDetail->manual_corporate_fee))
                                                ? $bookingDetail->manual_corporate_fee
                                                : ($transaction->Booking->final_amount_paid ?? 0);
                                        });
                if (round($totalCorporateCharges, 2) <= 0) {
                    DB::rollBack();
                    continue;
                }
                if($admin->billing_credit_limit<$totalCorporateCharges)
                {
                    $shouldSettle = true;
                }
                else
                {
                    switch ($admin->settlement_type) {
                    case 1: // weekly
                        if (!$lastSettlement || $lastSettlement->diffInDays($today) >= 6) {
                            $shouldSettle = true;
                        }
                        break;

                    case 2: // biweekly
                        // if (!$lastSettlement || $lastSettlement->diffInDays($today) >= 13) {
                        //     $shouldSettle = true;
                        // }
                        // break;
                        $todayDay = $today->day;
                        $lastDayOfMonth = $today->copy()->endOfMonth()->day;

                        if ($todayDay == 16 || $todayDay == 1) {
                            // Prevent duplicate settlement on same day
                            // if (!$lastSettlement || $lastSettlement->toDateString() != $today->toDateString()) {
                                $shouldSettle = true;
                            // }
                        }
                        break;

                    case 3: // monthly
                            $todayDay = $today->day;
                            $lastDayOfMonth = $today->copy()->endOfMonth()->day;
                            if ( $todayDay == 1) {
                                $shouldSettle = true;
                            }
                        // if (!$lastSettlement || $lastSettlement->diffInMonths($today) >= 1) {
                        //     $shouldSettle = true;
                        // }
                        break;

                    case 4: // after specific days
                        if (!$lastSettlement || $lastSettlement->diffInDays($today) >= $admin->settlement_custom_days) {
                            $shouldSettle = true;
                        }
                        break;
                    }
                }
                if ($shouldSettle) {
                    $last_settled_on = null;
                    if($lastSettlement){
                        $last_settled_on = $lastSettlement->format('Y-m-d H:i:s');
                    }
                    $settlement_date_time = Carbon::now()->format('Y-m-d H:i:s');
                    $invoice_id = $this->saveCorporateInvoice($admin, $totalCorporateCharges, $admin->settlement_type, $transactions, $settlement_date_time, $last_settled_on);
                    if(isset($invoice_id)){
                        $this->saveCorporateInvoiceSettlementLogs($admin->id, $admin->settlement_type, $settlement_date_time, $invoice_id , $totalCorporateCharges);
                    }
                }
                DB::commit();
            }
        }
        catch(\Exception $e){
            $message = $e->getMessage();
            DB::rollback();
            $log_data =[
                'exception'=>$message,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"corporateSettlement"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
    }


    public function OfflineOlineForTimeRecord(){
        DB::beginTransaction();

        try {
            $driver_ids = [];
            Driver::where([
                ['merchant_id', '=', 976],
                ['online_offline', '=', 1],
                ['free_busy', '=', 2],
                ['login_logout', '=', 1],
            ])->chunk(500, function ($drivers) use (&$driver_ids) {

                foreach ($drivers as $driver) {
                    $driver_ids[] = $driver->id;
                    $driver->online_offline = 2;
                    $newDriverRecord = new DriverRecords();
                    $newDriverRecord->OfflineTimeRecord($driver->id, $driver->merchant_id);
                    $driver->online_offline = 1;
                    $driver->save();
                }
            });

            DB::commit();

            $log_data = [
                'driver_ids'    => $driver_ids,
                'hit_time'      => now()->toDateTimeString(),
                'request_type'  => "OfflineOlineForTimeRecord",
            ];

            \Log::channel('per_day_cron_log')->info($log_data);
        } catch (\Exception $e) {
            DB::rollBack();

            $log_data = [
                'exception'     => $e->getMessage(),
                'hit_time'      => now()->toDateTimeString(),
                'request_type'  => "OfflineOlineForTimeRecord",
            ];

            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
    }


    public function BookingUpdateStatusForThirdParty()
    {
        $trips = [];
    
        // Wide safety buffer (48h) purely to reduce rows pulled from DB.
        // Exact "last 24 hours in booking's own timezone" check still happens per-row below, unchanged.
        $safetyCutoff = Carbon::now('UTC')->subHours(48)->timestamp; // integer unix timestamp
    
        Booking::with('BookingRating', 'Merchant.Configuration', 'CountryArea')
            ->select('id','pickup_latitude','pickup_longitude','drop_latitude','drop_longitude','final_amount_paid','booking_timestamp','country_area_id','merchant_id')
            ->where('booking_timestamp', '>=', $safetyCutoff)
            ->chunk(500, function ($bookings) use (&$trips) {
                foreach ($bookings as $booking) {
                    if (empty($booking->Merchant->Configuration) || $booking->Merchant->Configuration->wasl_integration != 1) {
                        continue;
                    }
    
                    // unchanged — existing precise per-booking timezone logic
                    $timezone = $booking->CountryArea['timezone'];
                    $nowLocal = Carbon::now($timezone);
                    $fromLocal = $nowLocal->copy()->subHours(24);
                    $fromUTC = $fromLocal->setTimezone('UTC');
                    $toUTC   = $nowLocal->setTimezone('UTC');
                    $bookingTimeUTC = Carbon::createFromTimestamp($booking->booking_timestamp, 'UTC');
    
                    if (!$bookingTimeUTC->between($fromUTC, $toUTC)) {
                        continue;
                    }
    
                    $trips[] = [
                        "tripId" => (string) $booking->id,
                        "customerRating" => (float) ($booking->BookingRating->user_rating_points ?? 0),
                        "originLatitude" => (double) $booking->pickup_latitude,
                        "originLongitude" => (double) $booking->pickup_longitude,
                        "destinationLatitude" => (double) $booking->drop_latitude,
                        "destinationLongitude" => (double) $booking->drop_longitude,
                        "tripCost" => (double) $booking->final_amount_paid
                    ];
                }
            });
    
        if (!empty($trips)) {
            $integration = new \App\Http\Controllers\Integrations\IntegrationController();
            $integration->proceedThirdPartyIntegrations('WASL', [
                'request' => $trips,
                'calling_for' => "TRIPS_UPDATE"
            ]);
        }
    }
   
    // latra daily report
    public function dailyreport()
    {
    
        $integration = ThirdPartyIntegration::where('name', 'LATRA')
                ->where('status', 1)
                ->firstOrFail();
        $data = Booking::where('booking_status', 1005)
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('merchant_id, COUNT(*) as total_bookings, SUM(final_amount_paid) as total_amount')
            ->groupBy('merchant_id')
            ->get();
    
        foreach($data as $val){
          
            $config = $integration->ThirdPartyIntegrationConfiguration($val['merchant_id'])->first();
            if(isset($config)){
                $url = "http://41.59.57.164:8080/api/v1/ticket-engine/rideReport/submitReport";
            
                $token = $this->generate_self_signed_jwt($config['auth_token']);
                $reportDate = Carbon::today()->format('Y-m-d');
            
                $payload = [
                    "totalTrip" => (int) $val['total_bookings'],
                    "totalPaidAmount" => (int) $val['total_amount'],
                    "reportDate" => $reportDate // format: YYYY-MM-DD
                ];
                 \Log::channel('latra')->info('latra_daily_report_cron_payload', ['response' => $payload]);
            
                $ch = curl_init($url);
            
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => [
                        "Accept: */*",
                        "Authorization: Bearer " . $token,
                        "Content-Type: application/json"
                    ],
                    CURLOPT_TIMEOUT => 30,
                ]);
            
                $response = curl_exec($ch);
                \Log::channel('latra')->info('latra_daily_report_cron', ['response' => $response]);
                if (curl_errno($ch)) {
                    throw new \Exception("Unknown Error Occured during Latra daily report submission");
                }
                curl_close($ch);
            }
        }
      
    }

  public  function generate_self_signed_jwt($iss)
    {
        $privateKey = file_get_contents(storage_path('oauth-private.key'));
        $payload = [
            "iss" => $iss, // client id provided by API provider
            "sub" => $iss,
            "iat" => time(),               // issued at
            "nbf" => time(),               // not before
            "exp" => time() + 3600,        // expiry (1 hour)
            "jti" => uniqid()              // unique token id
        ];
        $jwt = JWT::encode($payload, $privateKey, 'RS256');
        return $jwt;
    }



    public function processScheduledGiftCards()
    {
        try {
    
            $giftCards = GiftCard::with([
                    'User.countryArea'
                ])
                ->where('status', 'pending')
                ->where('is_scheduled', 1)
                ->whereNotNull('send_at')
                ->get();
    
            foreach ($giftCards as $giftCard) {
    
                try {
    
                    DB::beginTransaction();
    
                    // Lock current gift card row
                    $giftCard = GiftCard::with([
                            'User.countryArea'
                        ])
                        ->where('id', $giftCard->id)
                        ->lockForUpdate()
                        ->first();
    
                    if (!$giftCard) {
    
                        DB::rollBack();
                        continue;
                    }
    
                    // Already processed by another cron
                    if ($giftCard->status !== 'pending') {
    
                        DB::rollBack();
                        continue;
                    }
                    $timezone = optional(
                        isset($giftCard->User->country_area_id)
                            ? $giftCard->User->CountryArea
                            : optional($giftCard->User->Country)->CountryArea
                    )->timezone;
                   
                    
                    
    
                    if (empty($timezone)) {
    
                        \Log::warning(
                            'GiftCard timezone missing. GiftCard ID: ' . $giftCard->id
                        );
    
                        DB::rollBack();
                        continue;
                    }
    
                    /*
                    send_at stored in sender local timezone
                    convert local timezone datetime to UTC
                    */
                    $giftCardUtcTime = \Carbon\Carbon::parse(
                        $giftCard->send_at,
                        $timezone
                    )->utc();
    
                    $utcNow = now()->utc();
    
                    // Process only when scheduled UTC time reached
                   
                    if ($giftCardUtcTime->lte($utcNow)) {
    
                        $this->processGiftCard($giftCard);
                    }
    
                    DB::commit();
    
                } catch (\Throwable $e) {
    
                    DB::rollBack();
    
                    \Log::error([
                        'gift_card_id' => $giftCard->id ?? null,
                        'message' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                    ]);
                }
            }
    
        } catch (\Throwable $e) {
    
            \Log::error([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }



    public function processGiftCard(GiftCard $giftCard)
    {
        try {
    
            // Double safety check
            if ($giftCard->status !== 'pending') {
                return false;
            }
    
            // Credit receiver wallet
            $paramArray = [
                'sender_id' => $giftCard->sender_id,
                'user_id' => $giftCard->receiver_id,
                'amount' => $giftCard->remaining_amount,
                'narration' => 31,
                'platform' => 2,
                'payment_method' => 3,
                'receipt' => 'Gift Card Credit',
                'notification_type' => 'WALLET_RECEIVE_GIFTCARD',
                'sender_email' => $giftCard->sender_email,
                'sender_message' => $giftCard->sender_message
            ];
    
            WalletTransaction::UserWalletCredit($paramArray);
    
            // Update gift card
            $giftCard->update([
                'status' => 'sent',
                'remaining_amount' => 0,
                'sent_at' => now()->utc(),
            ]);
    
            \Log::info(
                'Gift card processed successfully. GiftCard ID: ' . $giftCard->id
            );
    
            return true;
    
        } catch (\Throwable $e) {
    
            \Log::error([
                'gift_card_id' => $giftCard->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
    
            throw $e;
        }
    }

 
    public function expirepreviousdayrides()
    {
        $rides = CarpoolingRide::whereIn('ride_status', [1, 2])
            ->with('CountryArea')
            ->get();
    
        $rideIds = [];
        $cutoffCache = [];
    
        foreach ($rides as $ride) {
            $timezone = $ride->CountryArea->timezone ?? 'UTC';
    
            // start of today in the ride's own region, as an epoch
            if (!isset($cutoffCache[$timezone])) {
                $cutoffCache[$timezone] = \Carbon\Carbon::now($timezone)->startOfDay()->timestamp;
            }
    
            if ($ride->ride_timestamp < $cutoffCache[$timezone]) {
                $rideIds[] = $ride->id;
            }
        }
    
        $parentUpdated = CarpoolingRide::whereIn('id', $rideIds)->update(['ride_status' => 9]);
        $detailUpdated = CarpoolingRideDetail::whereIn('carpooling_ride_id', $rideIds)->update(['ride_status' => 9]);
    
        \Log::channel('per_day_cron_log')->emergency([
            "count_rides"    => count($rideIds),
            "ride_ids"       => $rideIds,
            "parent_updated" => $parentUpdated,
            "detail_updated" => $detailUpdated,
        ]);
    }



}

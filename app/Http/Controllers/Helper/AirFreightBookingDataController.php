<?php

namespace App\Http\Controllers\Helper;

use App\Models\AdvertisementBanner;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Traits\ImageTrait;
use App\Traits\AirFreightBookingTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AirFreightServiceType;
use App\Models\AirFreightBooking;
use App\Models\AirFreightBookingDocument;
use App\Models\Segment;
use App\Models\User;
use App\Models\Onesignal;

class AirFreightBookingDataController extends Controller
{
    use MerchantTrait,ImageTrait;
    //air freight booking notification for user
    public function BookingNotificationForUser($booking, $notification_type = "", $message_otp = ""){
        $user = User::find($booking->user_id);
        setLocal($user->language);
        $booking_status = $booking->booking_status;
        $merchant_id = $booking->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $segmentId = $booking->segment_id;
        $segment = Segment::where('id',$segmentId)->first();
        $ride_string = trans("$string_file.ride");
        $notification_data['notification_type'] = $notification_type;
        $notification_data['segment_type'] = $segment->slag;
        $notification_data['segment_group_id'] = $segment->segment_group_id;
        $notification_data['segment_sub_group'] = $segment->sub_group_for_app; // its segment sub group for app
        $notification_data['segment_data'] = [
            'booking_id' => $booking->id,
            'booking_status' => $booking_status,
            'booking_type' => "",
            'username' => $booking->User->UserName,
            'email' => isset($booking->User->email) ? $booking->User->email : "",
            'user_image' => get_image($booking->User->UserProfileImage, 'user', $booking->merchant_id, true, false),
            'phone' => $booking->User->UserPhone,
        ];
        switch ($booking_status) {
            case "1001":
                $title = trans("$string_file.ride_booked_pending");
                $message = trans("$string_file.ride_booked_pending_from_admin");
                break;
            case "1002":
                $title = trans("$string_file.ride_booked");
                $message = trans("$string_file.ride_booked_confirm");
                break;
            case "1003":
                $title = "";
                $message = "";
                break;
            case "1004":
                $title = $ride_string . ' ' . trans("$string_file.rejected");
                $message = $ride_string . ' ' . trans("$string_file.ride_rejected_by_admin");
                break;
            case "1005":
                $title = $ride_string . ' ' . trans("$string_file.cancelled");
                $message = $ride_string . ' ' . trans("$string_file.ride_cancelled_by_admin");
                break;
            case "1016":
                $title = $ride_string . ' ' . trans("$string_file.auto_cancelled");
                $message = $ride_string . ' ' . trans("$string_file.auto_cancelled");
                break;
            case "1018":
                $title = $ride_string . ' ' . trans("$string_file.expired");
                $message = $ride_string . ' ' . trans("$string_file.expired_by_cron");
                break;
        }
        
        $arr_param = ['user_id' => $booking->user_id, 'data' => $notification_data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ""];
        Onesignal::UserPushMessage($arr_param);
        setLocal();
        return true;
    }
    
}
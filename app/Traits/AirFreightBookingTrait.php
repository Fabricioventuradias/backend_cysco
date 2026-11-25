<?php

namespace App\Traits;

use App\Models\BookingConfiguration;
use App\Models\BookingCoordinate;
use App\Models\CancelReason;
use App\Models\FailBooking;
use App\Http\Controllers\Helper\Merchant;
use Auth;
use App\Models\Booking;
use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\Models\Configuration;

trait AirFreightBookingTrait
{
    public function getBookingStatus($string_file)
    {
        return array(
        '1001' => trans("$string_file.new_ride"),//ride booked but pending from admin to complete ride
        '1002' => trans("$string_file.ride_completed"),// ride completed and aproved from admin
        '1003' => trans("$string_file.ride_cancelled_by_user"),
        '1004' => trans("$string_file.ride_rejected_by_admin"),
        '1005' => trans("$string_file.ride_cancelled_by_admin"),
        '1016' => trans("$string_file.auto_cancelled"),
        '1018' => trans("$string_file.expired_by_cron"),//Expired by cron,
    );
    }
    
    public function getBookingHistoryStatus($booking_status,$string_file){
            switch ($booking_status) {
                case "1001":
                    $title = trans("$string_file.new_ride");
                    $message = trans("$string_file.ride_booked_pending_from_admin");
                    break;
                case "1002":
                    $title = trans("$string_file.completed");
                    $message = trans("$string_file.ride_booked");
                    break;
                case "1003":
                    $title = "";
                    $message = "";
                    break;
                case "1004":
                    $title = trans("$string_file.rejected");
                    $message = trans("$string_file.ride_rejected_by_admin");
                    break;
                case "1005":
                    $title = trans("$string_file.cancelled");
                    $message = trans("$string_file.ride_cancelled_by_admin");
                    break;
                case "1016":
                    $title = trans("$string_file.auto_cancelled");
                    $message = trans("$string_file.auto_cancelled");
                    break;
                case "1018":
                    $title = trans("$string_file.expired");
                    $message = trans("$string_file.expired_by_cron");
                    break;
            }
            
            return ['message'=> $message,'title'=> $title];
        }
        
        public function PaymentOption($data, $userId, $currency = null, $min_wallet_bal = null, $ride_amount = null, $is_business_trip = false)
    {
        $user = User::select('merchant_id', 'corporate_id', 'wallet_balance')->find($userId);
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $return_option_list = [];
        $merchant_helper = new Merchant();
        foreach ($data as $paymentMethod) {
            $options = [];
            $icon = get_image($paymentMethod->payment_icon, 'payment_icon', $user->merchant_id, false);
            $merchant_payment = $paymentMethod->Merchant->where('id', $user->merchant_id);
            $merchant_payment = collect($merchant_payment->values());
            if (isset($merchant_payment) && !empty($merchant_payment[0]->pivot['icon'])) {
                $icon = get_image($merchant_payment[0]->pivot['icon'], 'p_icon', $user->merchant_id);
            }
            switch ($paymentMethod->id) {
                case "1":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'action' => true,
                        'icon' => $icon,
                        'message' => '',
                        'card_id' => "",
                    );
                    break;
                case "2":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'action' => true,
                        'icon' => $icon,
                        'message' => ''
                    );
                    $cardObj = new CardController();
                    $cardList = $cardObj->getUserAllCards($userId);
                    $arr_card = [];
                    if (!empty($cardList)) {
                        foreach ($cardList as $value) {
                            $arr_card[] = array(
                                'name' => "************" . $value['card_number'],
                                'card_id' => array_key_exists('id', $value) ? (string) $value['id'] : (string) $value['card_id'],
                                'action' => true,
                                'icon' => $icon,
                                'message' => ''
                            );
                        }
                    }
                    $options['arr_card'] = $arr_card;
                    break;
                case "3":
                    $config = Configuration::select('user_wallet_status')->where('merchant_id', $user->merchant_id)->first();
                    $action = false;
                    $msg = "";
                    $wallet_balance = ($is_business_trip == true && !empty($user->Corporate)) ? $user->Corporate->wallet_balance : $user->wallet_balance;
                    $corporate_name = ($is_business_trip == true && !empty($user->Corporate)) ? $user->Corporate->corporate_name : "";
                    $wallet = $wallet_balance ? $wallet_balance : '0.00';
                    if (!empty($ride_amount)) {
                        $msg = trans("$string_file.low_wallet_warning");
                        if (ceil($wallet_balance) >= ceil($ride_amount)) {
                            $action = true;
                        }
                    } else {
                        if (!empty($config) && $config->user_wallet_status == 1) {
                            $action = $wallet_balance >= $min_wallet_bal ? true : false;
                            $msg = ($is_business_trip == true && !empty($user->Corporate)) ? trans("$string_file.user_unauthorized_msg") : trans("$string_file.low_wallet_warning");
                        }
                    }
                    $paymentOPtion = $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method;
                    $name = !empty($corporate_name) ? $corporate_name.' '.$paymentOPtion." (".$currency." ".$wallet.")" : $paymentOPtion." (".$currency." ".$wallet.")";
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => !empty($wallet)  ? $name : $paymentOPtion,
                        'card_id' => "",
                        'action' => $action,
                        'icon' => $icon,
                        'message' => $msg
                    );
                    break;
                case "4":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'card_id' => "",
                        'icon' => $icon,
                        'action' => true,
                        'message' => ''
                    );
                    break;
                case "5":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'card_id' => "",
                        'action' => true,
                        'icon' => $icon,
                        'message' => ''
                    );
                    break;
                case "6":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'card_id' => "",
                        'action' => true,
                        'icon' => $icon,
                        'message' => ''
                    );
                    break;
                case "9":
                    $options = array(
                        'id' => $paymentMethod->id,
                        'name' => $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method,
                        'action' => true,
                        'icon' => $icon,
                        'message' => '',
                        'card_id' => "",
                    );
                    break;
            }
            if (!empty($options)) {
                $return_option_list[] = $options;
            }
        }
        return $return_option_list;
    }
}
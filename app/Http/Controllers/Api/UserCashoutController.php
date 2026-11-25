<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\Helper\Merchant;
use App\Models\CarpoolingConfigCountry;
use App\Models\Configuration;
use App\Models\UserCashout;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ApplicationConfiguration;
use App\Traits\MerchantTrait;
use App\Models\Onesignal;

class UserCashoutController extends Controller
{
    use ApiResponseTrait,MerchantTrait;

    // public function index(Request $request)
    // {
    //     $user = $request->user('api');
    //     $merchant_helper = new Merchant();
    //     DB::beginTransaction();
    //     try {
    //         $configuration = Configuration::where('merchant_id', $user->merchant_id)->first();
    //         $user_config = CarpoolingConfigCountry::where('merchant_id', $user->merchant_id)->first();
    //         if (isset($configuration->user_cashout_module) && $configuration->user_cashout_module != 1 && isset($user_config->user_cashout_min_amount) && $user_config->user_cashout_min_amount != 1) {
    //             return $this->failedResponse(trans("common.configuration_not_found"));
    //         } else {
    //             $data = [];
    //             $cashout_requests = UserCashout::where([['merchant_id', '=', $user->merchant_id], ['user_id', $user->id]])->latest()->get();
    //             //p(  $user);
    //             if ($cashout_requests->count() > 0) {
    //                 foreach ($cashout_requests as $cashout_request) {
    //                     $cashout_status = '';
    //                     switch ($cashout_request->cashout_status) {
    //                         case "0":
    //                             $cashout_status = trans('common.pending');
    //                             break;
    //                         case "1":
    //                             $cashout_status = trans('common.success');
    //                             break;
    //                         case "2":
    //                             $cashout_status = trans('common.rejected');
    //                             break;
    //                         default:
    //                             $cashout_status = trans('common.failed');

    //                     }
                      
    //                     array_push($data, array(
    //                         'id' => $cashout_request->id,
    //                         // 'amount' => $user->Country->isoCode . ' ' . $cashout_request->amount,
    //                         'amount' => $user->Country->isoCode . ' ' . $merchant_helper->PriceFormat($cashout_request->amount, $user->merchant_id),
    //                         'cashout_status' => $cashout_status,
    //                         'action_by' => $cashout_request->action_by,
    //                         'transaction_id' => $cashout_request->transaction_id,
    //                         'comment' => $cashout_request->comment,
    //                         'created_at' => strtotime($cashout_request->created_at),
    //                         'updated_at' => strtotime($cashout_request->updated_at),
                            
    //                     ));
    //                 }
    //             }
    //             $message = trans("common.cashout") . " " . trans("common.request") . " " . trans("common.user");
    //             return $this->successResponse($message, $data);
    //         }
    //     } catch (\Exception $e) {
    //         return $this->failedResponse($e->getMessage());
    //     }
    // }

//     public function request(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'amount' => 'required',
//         ]);
//         if ($validator->fails()) {
//             $errors = $validator->messages()->all();
//             return $this->failedResponse($errors[0]);
//         }
//         DB::beginTransaction();
//         try {
//             $user = $request->user('api');
//             $configuration = Configuration::where('merchant_id', $user->merchant_id)->first();
//             $user_config = CarpoolingConfigCountry::where('merchant_id', $user->merchant_id)->first();
//             if (isset($configuration->user_cashout_module) && $configuration->user_cashout_module != 1 && isset($user_config->user_cashout_min_amount) && $user_config->user_cashout_min_amount != 1) {
//                 return $this->failedResponse(trans("common.configuration_not_found"));
//             } else {
//                 if ($request->amount < $user_config->user_cashout_min_amount) {
//                     $amount = $user->CountryArea->Country->isoCode . ' ' . $user_config->user_cashout_min_amount;
//                     return $this->failedResponse(trans('api.cash_out_min_amount_requested') . ' ' . $amount);
//                 }
//                 if ($user->wallet_balance < $request->amount) {
//                     $message = trans("common.wallet") . " " . trans("common.balance") . " " . trans("common.is") . " " . trans("common.low");
//                     return $this->failedResponse($message);
//                 }
//                 $paramArray = array(
//                     'user_id' => $user->id,
//                     'booking_id' => null,
//                     'amount' => $request->amount,
//                     'payment_request'=>'Bank Transfer',
//                     'display_payment'=>'Bank Transfer',
//                     'narration' => 8,
//                 );
//                 WalletTransaction::UserWalletDebit($paramArray);
// //                \App\Http\Controllers\Helper\CommonController::WalletDeduct($driver->id,null,$request->amount,10,2,2);
//                 // UserCashout::create([
//                 //     'user_id' => $user->id,
//                 //     'merchant_id' => $user->merchant_id,
//                 //     'amount' => $request->amount,
//                 //     'action_by'=>trans("common.bank_transfer"),
//                 // ]);
//                 DB::commit();
//                 $message = trans("common.cashout") . " " . trans("common.out") . " " . trans("common.request") . " " . trans("common.successfully") . " " . trans("common.created");
//                 return $this->successResponse($message);
//             }
//         } catch (\Exception $e) {
//             DB::rollBack();
//             return $this->failedResponse($e->getMessage());
//         }
//     }

    public function index(Request $request)
    {
        $user = $request->user('api');
        $merchant_helper = new Merchant();
        try {
            $string_file = $this->getStringFile(NULL,$user->Merchant);
            $appconfiguration = ApplicationConfiguration::where('merchant_id', $user->merchant_id)->first();
            $userCashout = UserCashout::where(['user_id'=>$user->id,'cashout_status'=>0])->get();
            if (isset($appconfiguration->user_cashout_enable) && $appconfiguration->user_cashout_enable != 1) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            } else {
                $data = [];
                $cashout_requests = UserCashout::where([['merchant_id', '=', $user->merchant_id], ['user_id', $user->id]])->latest()->get();
                if($cashout_requests->count() > 0){
                    foreach ($cashout_requests as $cashout_request){
                        $cashout_status = '';
                        switch ($cashout_request->cashout_status){
                            case "0":
                                $cashout_status = trans("$string_file.pending");
                                break;
                            case "1":
                                $cashout_status = trans("$string_file.success");
                                break;
                            case "2":
                                $cashout_status = trans("$string_file.rejected");
                                break;
                            default:
                                $cashout_status ="";

                        }
                        array_push($data,array(
                            'id' => $cashout_request->id,
                            'amount' => $user->Country->isoCode . ' ' . $merchant_helper->PriceFormat($cashout_request->amount, $user->merchant_id),
                            'cashout_status' => $cashout_status,
                            'cashout_status_key'=> $cashout_request->cashout_status,
                            'action_by' => $cashout_request->action_by,
                            'transaction_id' => $cashout_request->transaction_id,
                            'comment' => $cashout_request->comment,
                            'created_at' =>  strtotime($cashout_request->created_at),
                            'updated_at' =>  strtotime($cashout_request->updated_at),
                        ));
                    }
                }
                $message = trans("$string_file.cash_out_request_driver");
                return $this->successResponse($message, $data);
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL,$user->Merchant);
            $appconfiguration = ApplicationConfiguration::where('merchant_id', $user->merchant_id)->first();
            $userCashout = UserCashout::where(['user_id'=>$user->id,'cashout_status'=>0])->get();
            $user_cashout_amount_requested = 0;
            foreach($userCashout as $cashout){
                $user_cashout_amount_requested += $cashout->amount;
            }

            $user_remaining_amount = $user->wallet_balance - $user_cashout_amount_requested;
            if($request->amount > $user_remaining_amount){
                return $this->failedResponse(trans("$string_file.some_pending_amount_which_already_exceeded_from_wallet_amount"));
            }
            
            if (isset($appconfiguration->user_cashout_enable) && $appconfiguration->user_cashout_enable != 1) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            } else {
                if ($user->wallet_balance < $request->amount) {
                    return $this->failedResponse(trans("$string_file.low_wallet_warning"));
                }
                
                $cashout = UserCashout::create([
                    'user_id' => $user->id,
                    'merchant_id' => $user->merchant_id,
                    'amount' => $request->amount,
                ]);
                $cashout_id = $cashout->id;

                $notification_data['notification_type'] = "CASHOUT_REQUEST_RAISED";
                $notification_data['segment_data'] = ['cashout_id'=>$cashout_id];
                $notification_data['segment_sub_group'] = NULL;
                $notification_data['segment_group_id'] = NULL;
                $arr_param = ['user_id' => $user->id, 'data' => $notification_data, 'message' => trans("$string_file.cashout_request_raised"), 'merchant_id' => $user->merchant_id, 'title' => trans("$string_file.cashout_request"), 'large_icon' => ""];
                Onesignal::UserPushMessage($arr_param);
                DB::commit();
                $message = trans("$string_file.cash_out_request_user_successfully");
                return $this->successResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }
}

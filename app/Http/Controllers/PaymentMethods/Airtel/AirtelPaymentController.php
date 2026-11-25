<?php

namespace App\Http\Controllers\PaymentMethods\Airtel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\BusinessSegment\Order;
use App\Models\HandymanOrder;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\DriverCashout;
use App\Models\BusinessSegment\BusinessSegmentCashout;

class AirtelPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getAirtelConfig($merchant_id, $type="payment"){
        if($type=="payment")
        {
            $payment_option = PaymentOption::where('slug', 'AIRTEL')->first();
            $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
            $string_file = $this->getStringFile($merchant_id);
            if (empty($paymentOption)) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
        }
        elseif($type=="cashout")
        {
            $payment_option = PaymentOption::where('slug', 'AIRTEL_CASHOUT')->first();
            $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
            $string_file = $this->getStringFile($merchant_id);
            if (empty($paymentOption)) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
        }
        return $paymentOption;
    }

    public function getBaseUrl($env){
        // return $env == 1 ? 'https://openapi.airtel.ug/' : 'https://openapiuat.airtel.ug/';
        // return $env == 1 ? 'https://openapi.airtel.africa/' : 'https://openapiuat.airtel.africa/';
        return $env->tokenization_url;
    }
    
    public function getAuthToken($clientId,$clientKey,$baseUrl){
        try{
            $data = [
                'client_id'=> $clientId,
                'client_secret'=> $clientKey,
                'grant_type'=> 'client_credentials'
            ];

            $curl = curl_init();
            

            curl_setopt_array($curl, array(
              CURLOPT_URL => $baseUrl.'auth/oauth2/token',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
              ),
            ));
            

            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response,true);
            if(isset($res['access_token'])){
                return $res['access_token'];
            }
            else{
                return '';
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function MakeAirtelPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'msisdn' => 'required',
            'currency'=> 'required',
            'calling_for' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try{
            $type = $request->type;
            if($type == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = isset($user->country) ? $user->country->country_code : $user->CountryArea->Country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $countryCode = isset($user->country) ? $user->country->country_code : $user->CountryArea->Country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $paymentConfig = $this->getAirtelConfig($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig);
            $clientKey = $paymentConfig->api_secret_key;
            $clientId = $paymentConfig->api_public_key;
            
            $string_file = $this->getStringFile($merchant_id);
            
            $token = $this->getAuthToken($clientId, $clientKey,$baseUrl);
            if(empty($token)){
                throw new \Exception('Token not generated');
            }
            
            $tscId = 'trans'.date('His');
            $merchantName = $user->Merchant->BusinessName;
            $data = [
                "reference"=>$merchantName .' '."Payment",
                 "subscriber"=> [
                    "msisdn"=>$request->msisdn
                 ],
                 "transaction"=>[
                    "amount"=> $request->amount,
                    "id"=> $tscId
                 ]
            ];
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
            //   CURLOPT_URL => $baseUrl.'merchant/v1/payments/',
              CURLOPT_URL => $baseUrl.'merchant/v1/payments/',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
                'X-Country: '. $countryCode,
                'X-Currency: '. $request->currency,
              ),
            ));

            $response = json_decode(curl_exec($curl),true);
            curl_close($curl);
            if(isset($response['status']['code']) && $response['status']['code'] == 200) {
                $transactionId = $response['data']['transaction']['id'];
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                DB::table('transactions')->insert([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $calling_for,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                DB::commit();
        
                return $this->successResponse(trans("$string_file.booking_confirm_transaction"),$response['data']['transaction']);

            }else{
                if(isset($response['status_message'])){
                    return $this->failedResponse($response['status_message']);
                }else{
                    if(isset($response['status']) && isset($response['status']['message'])){
                        return $this->failedResponse($response['status']['message']);
                    }
                }
                return $this->failedResponse("Some Issue In Response");
            }

        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
   public function Redirect(Request $request){
        \Log::channel('Airtel_redirect')->emergency($request->all());
        $data = $request->all();
        if(isset($data['transaction'])){
            $trans = DB::table('transactions')->where(['request_status' => 1, 'payment_transaction_id' => $data['transaction']['id']])->first();
            if (!empty($trans)) {
                $payment_status = $data['transaction']['status_code'] == 'TS' ? 2 : 3;
                if($payment_status == 2){
                    DB::table('transactions')
                    ->where(['payment_transaction_id' => $data['transaction']['id']])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    $receipt = "Application : " . $data['transaction']['id'];
                    $paramArray = array(
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $data['transaction']['id'],
                    );

                    if($trans->status == 1){
                        $paramArray['user_id'] = $trans->user_id;
                        WalletTransaction::UserWalletCredit($paramArray);
                    }elseif($trans->status == 2){
                        $paramArray['driver_id'] = $trans->driver_id;
                        WalletTransaction::WalletCredit($paramArray);
                    }else{
                        if(!empty($trans->booking_id)){
                            $booking = Booking::find($trans->booking_id);
                            $booking->payment_status = 1;
                            $booking->save();
                            $string_file = $this->getStringFile(NULL, $booking->Merchant);
                            $title = trans("$string_file.payment_success");
                            $message = trans("$string_file.payment_done");
                            $data['notification_type'] = 'PAYMENT_COMPLETE';
                            $data['segment_type'] = $booking->Segment->slag;
                            $data['segment_data'] = ['id'=>$booking->id,'handyman_order_id'=>NULL,'order_id'=> NULL];
                            $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                            $user_param = $arr_param;
                            $user_param['user_id'] = $booking->user_id;
                            Onesignal::UserPushMessage($user_param);
                            $driver_param = $arr_param;
                            $driver_param['driver_id'] = $booking->driver_id;
                            Onesignal::DriverPushMessage($driver_param);
                        }
                        elseif(!empty($trans->order_id)){
                            $order = Order::find($trans->order_id);
                            $order->payment_status = 1;
                            $order->save();
                            $string_file = $this->getStringFile(NULL, $order->Merchant);
                            $title = trans("$string_file.payment_success");
                            $message = trans("$string_file.payment_done");
                            $data['notification_type'] = 'PAYMENT_COMPLETE';
                            $data['segment_type'] = $order->Segment->slag;
                            $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>NULL,'order_id'=> $order->id];
                            $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $order->merchant_id, 'title' => $title, 'large_icon' => ""];
                            $user_param = $arr_param;
                            $user_param['user_id'] = $order->user_id;
                            Onesignal::UserPushMessage($user_param);
                            $driver_param = $arr_param;
                            $driver_param['driver_id'] = $order->driver_id;
                            Onesignal::DriverPushMessage($driver_param);
                            
                        }
                        else{
                            $handymanOrder = HandymanOrder::find($trans->handyman_order_id);
                            $handymanOrder->payment_status = 1;
                            $handymanOrder->save();
                            $string_file = $this->getStringFile(NULL, $handymanOrder->Merchant);
                            $title = trans("$string_file.payment_success");
                            $message = trans("$string_file.payment_done");
                            $data['notification_type'] = 'PAYMENT_COMPLETE';
                            $data['segment_type'] = $handymanOrder->Segment->slag;
                            $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>$handymanOrder->id,'order_id'=> NULL];
                            $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $handymanOrder->merchant_id, 'title' => $title, 'large_icon' => ""];
                            $user_param = $arr_param;
                            $user_param['user_id'] = $handymanOrder->user_id;
                            Onesignal::UserPushMessage($user_param);
                            $driver_param = $arr_param;
                            $driver_param['driver_id'] = $handymanOrder->driver_id;
                            Onesignal::DriverPushMessage($driver_param);
                        }
                    }
                }else{
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $data['transaction']['id']])
                        ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                }
                
            }else{
                \Log::channel('Airtel_redirect')->emergency(['message' => 'Transaction Not Found!']);
            }
        }
        
    }

    public function PaymentStatus(Request $request){
        $transactionId = $request->transaction_id; 
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        $data = [];
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
            $transaction_status = 1;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
            $transaction_status = 2;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else
        {
            $request_status_text = "failed";
            $transaction_status = 3;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            
        }
        return $this->successResponse($request_status_text,$data);
        // return ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
    }
    public function Cashout(Request $request){
        $validator = Validator::make($request->all(), [
                'calling_from' => 'required',
                'amount' => 'required',
                'phone' => 'required',
                'currency' => 'required',
            ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        $calling_from = $request->calling_from;
        
        if ($calling_from == "DRIVER") {
            $driver = $request->user('api-driver');
            $id = $driver->id;
            $merchant_id = $driver->merchant_id;
        }
        elseif ($calling_from == "BUSINESS_SEGMENT") {
            if ($request->from_admin == 1) {
                $bs = get_business_segment(false);
            } else {
                $bs = $request->user('business-segment-api');
            }
            $id = $bs->id;
            $merchant_id = $bs->merchant_id;
        }
        $payment_option_config = $this->getAirtelConfig($merchant_id, "cashout");
        $baseUrl = $this->getBaseUrl($payment_option_config);
        
        $url = $baseUrl."standard/v2/disbursements/";
        $clientId=$payment_option_config->api_public_key;
        $clientKey=$payment_option_config->api_secret_key;
        $pin=$payment_option_config->auth_token;
        // kyc api
        $token = $this->getAuthToken($clientId, $clientKey,$baseUrl);
        $kycUrl=$baseUrl."standard/v1/users/".$request->phone;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $kycUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Content-Type: application/json',
                'X-Country: UG',
                'X-Currency: '.$request->currency,
                'Authorization: Bearer ' . $token
            ),
        ));
            
        $kycResponse = curl_exec($curl);
        \Log::channel('Airtel_redirect')->emergency([
                'cashout_request' => $request->all(),
                'response' => $kycResponse,
                'time' => date('Y-m-d H:i:s')
            ]);
        if (curl_errno($curl)) {
            echo 'KYC API Error: ' . curl_error($curl);
            exit;
        }
    
        curl_close($curl);
        $kycResult = json_decode($kycResponse, true);
            
        if(isset($kycResult['status']['code']) && $kycResult['status']['code']==200){

            $token = $this->getAuthToken($clientId, $clientKey,$baseUrl);
            $transactionId = 'CASHOUT' . now()->format('YmdHis');
            $string_file = $this->getStringFile($merchant_id);
            
            $payload = [
                "payee" => [
                    "currency" => $request->currency,
                    "msisdn"   => $request->phone,
                    "name"     => "Bob"
                ],
                "reference" =>"GRAYRIDE Payment",
                "pin"       => $pin,
                "transaction" => [
                    "amount" =>  $request->amount,
                    "id"     => $transactionId, 
                    "type"   => "B2C"
                ]
            ];
        
            $headers = [
                "Content-Type: application/json",
                "Accept: */*",
                "X-Country: UG",
                "X-Currency: ".$request->currency,
                "Authorization: Bearer ".$token
            ];
            $ch = curl_init($url);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            
            $response = curl_exec($ch);
            $result=json_decode($response, true);
            \Log::channel('Airtel_redirect')->emergency([
                    'cashout_request' => $request->all(),
                    'response' => $result,
                    'time' => date('Y-m-d H:i:s')
                ]);
            if(isset($result['status']['success']) &&  $result['status']['success']){
                $success = $result['status']['success'];
                $code = $result['status']['code'];
                $message = $result['status']['message'];
                $transactionId = $result['data']['transaction']['id'];
                $referenceId = $result['data']['transaction']['reference_id'];
                $res = [
                            'transaction_id' => $transactionId,
                        ];
                if($success){
                        DB::table('transactions')->insert([
                            'user_id' => $calling_from == "USER" ? $id : NULL,
                            'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                            'merchant_id' => $merchant_id,
                            'payment_transaction_id' => $transactionId,
                            'reference_id' =>$referenceId,
                            'amount' => $request->amount,
                            'payment_option_id' => $payment_option_config->payment_option_id,
                            'request_status' => 2,
                            'status_message' => 'SUCCESS',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        if($calling_from == "DRIVER"){
                            DriverCashout::insertGetId([
                                'driver_id' => $driver->id,
                                'merchant_id' => $driver->merchant_id,
                                'amount' => $request->amount,
                                'cashout_status' => 1,
                                'action_by' => 'Online Payment',
                                'transaction_id' => $transactionId,
                                'comment' => trans("$string_file.cashout_request")
                            ]);
            
                            $paramArray = array(
                                'driver_id' => $driver->id,
                                'booking_id' => NULL,
                                'amount' => $request->amount,
                                'narration' => 28,
                            );
                            WalletTransaction::WalletDeduct($paramArray);
                            $title = trans("$string_file.cashout_request");
                            $message = trans("$string_file.cashout_request_sent");
            
                            $notification_data['notification_type'] = "WALLET_HISTORY";
                            $notification_data['segment_type'] = "WALLET_HISTORY";
                            $notification_data['segment_data'] = [];
                            $notification_data['segment_sub_group'] = null;
                            $arr_param = ['driver_id'=>$driver->id,'data'=>$notification_data,'message'=>$message,'merchant_id'=>$driver->merchant_id,'title'=>$title,'large_icon'=>""];
                            Onesignal::DriverPushMessage($arr_param);

                            }else{
                            BusinessSegmentCashout::create([
                                'business_segment_id' => $bs->id,
                                'merchant_id' => $bs->merchant_id,
                                'amount' => $request->amount,
                                'cashout_status' => 1,
                                'action_by' => 'Business Segment Online Payment',
                                'transaction_id' => $transactionId,
                                'comment' => trans("$string_file.cashout_request")
                            ]);
                            $paramArray = array(
                                'business_segment_id' => $bs->id,
                                'booking_id' => null,
                                'amount' => $request->amount,
                                'narration' => 4,
                            );
                            WalletTransaction::BusinessSegmntWalletDebit($paramArray);
                        }

                        if($request->from_admin == 1){
                            return ['message'=>trans("$string_file.cashout_request_registered_successfully"),'result'=>1];
                        }else{
                            return $this->successResponse(trans("$string_file.cashout_request_registered_successfully"),$res);
                        }
                    return $this->failedResponse($message);
                }
            }
            
            
            if (curl_errno($ch)) {
                echo 'cURL Error: ' . curl_error($ch);
            } else {
                return $this->failedResponse($result['status']['message']);
            }
            curl_close($ch);
        }
        else{
            return $this->failedResponse($kycResult['status']['message']);
        }
    }
 }

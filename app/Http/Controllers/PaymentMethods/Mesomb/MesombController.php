<?php

namespace App\Http\Controllers\PaymentMethods\Mesomb;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Order;
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
use MeSomb\Operation\PaymentOperation;
use MeSomb\Util\RandomGenerator;

class MesombController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getMesombConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'MESOMB')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function paymentRequest($request,$payment_option_config, $calling_from){
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $paymentOption=$this->getMesombConfig($merchant_id);
            $applicationKey = $paymentOption->api_public_key;
            $accessKey = $paymentOption->auth_token;
            $secretKey = $paymentOption->api_secret_key;
        
            $phone = $request->phone;
            $amount = $request->amount;
            $service = $request->service;
            $currency = $request->currency;
            $string_file = $this->getStringFile($merchant_id);
            
            $ref = date('YmdHis');
            $client = new PaymentOperation($applicationKey, $accessKey, $secretKey);
            $response = $client->makeCollect([
                // 'payer' => '237675776123',
                // 'payer' => '237650070963',
                'payer' => $phone,
                'amount' => $amount,
                'service' => $service,
                'country' => 'CM',
                'currency' => $currency,
            ]);
           
            \Log::channel('mesomb')->emergency(json_encode($response));
            // $response = json_decode($response,true);
            if($response && isset($response->transaction->status) &&  $response->transaction->status == "SUCCESS"){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=>$response->transaction->pk,
                    'amount' => $amount,
                    'reference_id'=> $ref,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'SUCCESS',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                return ['transaction_id'=>$response->transaction->pk,'message'=>$response->message,'status'=>$response->transaction->status];
            }else{
                // throw new \Exception("Something Went Wrong");
                 DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=>$response->transaction->pk,
                    'amount' => $amount,
                    'reference_id'=> $ref,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'request_status'=> 3,
                    'status_message'=> 'FAIL',
                    'payment_transaction' => json_encode($response),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                return ['transaction_id'=>$response->transaction->pk,'message'=>$response->message,'status'=>$response->transaction->status];
            }
    }
    
  
    public function getPaymentOption(Request $request){
        $validator = Validator::make($request->all(),[
            'calling_from' => 'required',
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_id = $request->calling_from == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
        $result = [
                    // [
                    //     'key' => 'AIRTEL',
                    //     'value' => 'AIRTEL'
                    // ],
                    // [
                    //     'key' => 'HALOTEL',
                    //     'value' => 'HALOTEL'
                    // ],
                    // [
                    //     'key' => 'MESOMB',
                    //     'value' => 'MESOMB'
                    // ],
                    // [
                    //     'key' => 'MOOV',
                    //     'value' => 'MOOV'
                    // ],
                    [
                        'key' => 'MTN',
                        'value' => 'MTN'
                    ],
                    [
                        'key' => 'ORANGE',
                        'value' => 'ORANGE'
                    ],
                    // [
                    //     'key' => 'TNM',
                    //     'value' => 'TNM'
                    // ],
                    
                ];
            $string_file = $this->getStringFile($merchant_id);
            
            return $this->successResponse(trans("$string_file.success"), $result);
    }
   
}
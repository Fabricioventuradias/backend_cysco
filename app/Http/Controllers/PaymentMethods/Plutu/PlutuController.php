<?php

namespace App\Http\Controllers\PaymentMethods\Plutu;

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

class PlutuController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getPlutuConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PLUTU')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function paymentRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'phone'         => 'required',
            'amount'        => 'required',
            'birth_year'    => 'required',
            'calling_from'  => 'required|in:DRIVER,USER'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new \Exception($errors[0]);
        }
        $calling_from=$request->calling_from;
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
            $paymentOption=$this->getPlutuConfig($merchant_id);
            $applicationKey = $paymentOption->api_public_key;
            $token = $paymentOption->auth_token;
        
            $phone = $request->phone;
            $amount = $request->amount;
            $birth_year = $request->birth_year;
            $string_file = $this->getStringFile($merchant_id);
            
            $ref = date('YmdHis');

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.plutus.ly/api/v1/transaction/sadadapi/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('mobile_number' =>  $phone,'amount' => $amount,'birth_year' => $birth_year),
            CURLOPT_HTTPHEADER => array(
                'X-API-KEY: '.$applicationKey,
                'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
           
            \Log::channel('plutu')->emergency('Plutu Verify API', [
                'request' => $request->all(), // Your request payload
                'response' => $response,    // API response
            ]);
            $responseData = json_decode($response, true);

            $processId = $responseData['result']['process_id'];
            $message = $responseData['message'];
            $status = $responseData['status'];
            if($response && isset($status) &&  $status == 200){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant_id,
                    'amount' => $amount,
                    'reference_id'=> $processId,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $return= ['process_id'=>$processId,'message'=>$message,'status'=>$status];
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
                    'payment_transaction' => $response,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $return= ['message'=>$message,'status'=>$status];
            }
            return $this->successResponse(trans("$string_file.success"), $return);
    }

     public function paymentConfirm(Request $request){
        $validator = Validator::make($request->all(), [
            'otp'       => 'required',
            'process_id'=> 'required',
            'amount' => 'required',
            'calling_from'  => 'required|in:DRIVER,USER'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new \Exception($errors[0]);
        }
        $calling_from=$request->calling_from;
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
            $paymentOption=$this->getPlutuConfig($merchant_id);
            // dd($paymentOption);
            $applicationKey = $paymentOption->api_public_key;
            $token = $paymentOption->auth_token;
        
            $otp = $request->otp;
            $process_id = $request->process_id;
            $amount= $request->amount;
            $string_file = $this->getStringFile($merchant_id);
            $ref = date('YmdHis');
            

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.plutus.ly/api/v1/transaction/sadadapi/confirm',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('code' => $otp,'amount' => $amount,'invoice_no' => $ref,'process_id' => $process_id),
            CURLOPT_HTTPHEADER => array(
                'X-API-KEY: '.$applicationKey,
                'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

           
            \Log::channel('plutu')->emergency('Plutu Confirm API', [
                'request' => $request->all(), // Your request payload
                'response' => $response,    // API response
            ]);
            $responseData = json_decode($response, true);

            $transaction_id = $responseData['result']['transaction_id'];
            $message = $responseData['message'];
            $status = $responseData['status'];
            if($response && isset($status) &&  $status == 200){
                DB::table('transactions')
                    ->where(['reference_id' => $process_id])
                    ->update(['request_status' => 2, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS', 'payment_transaction_id'=>$transaction_id]);
                    
            $request_status_text = "success";
            $transaction_status = 2;
            $return_data = ['payment_status' => true, 'request_status' => $request_status_text, 'transaction_status' => $status,'transaction_id'=>$transaction_id];
                
            }else{
               DB::table('transactions')
                    ->where(['reference_id' => $process_id])
                    ->update(['request_status' => 3, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL', 'payment_transaction_id'=>$transaction_id]);
                    
                
                $return_data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $status,'transaction_id'=>$transaction_id];
            }
            return $this->successResponse(trans("$string_file.success"), $return_data);
    }
    
  
  
   
}
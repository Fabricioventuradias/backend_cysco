<?php

namespace App\Http\Controllers\PaymentMethods\HyperPayNew;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\MerchantTrait;
use DB;

class HyperPaynewController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getHyperPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'HYPERPAY_NEW')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return response()->json(['result' => 0, 'message' => trans('api.message194')]);
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
            $paymentOption=$this->getHyperPayConfig($merchant_id);
            $access_token = $paymentOption->api_secret_key;
            $entity_id = $paymentOption->api_public_key;
        
            $phone = $request->phone;
            $amount = $request->amount;
            $currency = $request->currency;
            $string_file = $this->getStringFile($merchant_id);
            
            $ref = date('YmdHis');
            $url = $paymentOption->gateway_condition == 1 ? 'https://eu-prod.oppwa.com/v1/checkouts' :'https://eu-test.oppwa.com/v1/checkouts';
            $data = "entityId=" .$entity_id.
            "&amount=" .(int)$amount.
            "&currency=" .$currency.
            "&paymentType=DB" .
            "&integrity=true";
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$access_token,
                'Content-Type: application/x-www-form-urlencoded'
            ),
            ));

            $response = curl_exec($curl);


            \Log::channel('hyperpaynew')->emergency(json_decode($response,true));
            $response = json_decode($response,true);
            
            $checkoutId = $response['id'];
            $integrity  = $response['integrity'];
            if($response && isset($response['result']['code'] ) &&  $response['result']['code'] == "000.200.100"){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=>$response['id'],
                    'amount' => $amount,
                    'reference_id'=> $ref,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $checkoutId = $response['id'];
                $integrity  = $response['integrity'];
                $webViewUrl = route('hyperpaynew-webview', [
                    'checkout_id' => $checkoutId,
                    'integrity'   => base64_encode($integrity),
                ]);
                
                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' =>$webViewUrl,
                    'checkout_id' => $checkoutId ?? '',
                    'integrity' => $integrity ?? '',
                    'success_url' => route('hyperpay-success'),
                    'fail_url' => route('hyperpay-fail'),
                ];
            }else{
                throw new \Exception("Something Went Wrong");
            }
    }
    
    public function webView($checkout_id, $integrity){
        $integrity=base64_decode($integrity);
        $nonce = base64_encode(random_bytes(16));
        return view('payment/hyperPaynew/web', compact('checkout_id','integrity','nonce'));
    }





    public function PaymentStatus($request,$payment_option_config, $calling_from){
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
        $paymentOption=$this->getHyperPayConfig($merchant_id);
        $access_token = $paymentOption->api_secret_key;
        $entity_id = $paymentOption->api_public_key;
        $checkout_id=$request->transaction_id;
        $url = $paymentOption->gateway_condition == 1 ? 'https://eu-prod.oppwa.com/v1/checkouts/' :'https://eu-test.oppwa.com/v1/checkouts/';
        $curl = curl_init();
            
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url.$checkout_id.'/payment?entityId='.$entity_id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_POSTFIELDS => 'entityId='.$entity_id,
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$access_token,
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));
            
        $response = curl_exec($curl);
            
        curl_close($curl);


        \Log::channel('hyperpaynew')->emergency(json_decode($response,true));
        $data = json_decode($response,true);
        if ($data['result']['code'] == "000.100.110" || $data['result']['code'] == "000.000.000"){
            DB::table('transactions')
                    ->where(['payment_transaction_id' => $transaction_id])
                    ->update(['request_status' => 2, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    
            $request_status_text = "success";
            $transaction_status = 2;
            $return_data = ['payment_status' => true, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }else{
            DB::table('transactions')
                    ->where(['payment_transaction_id' => $transaction_id])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
            $request_status_text = "failed";
            $transaction_status = 3;
            $return_data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        return $return_data;
    }

    public function Success(Request $request){
        \Log::channel('hyperpaynew')->emergency('HyperPay Success Response', [
            'request' => $request->all(),
        ]);
        DB::table('transactions')
            ->where(['payment_transaction_id' => $request->id])
            ->update(['request_status' => 2, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);      
        $message = !empty($request->msg) ? $request->msg : "Success";
        return view('payment/hyperPay/response', compact('message'));
    }

    public function Fail(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Failed";
        return view('payment/hyperPay/response', compact('message'));
    }
}

<?php

namespace App\Http\Controllers\PaymentMethods\Tlync;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\MerchantTrait;
use DB;

class TlyncController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getTlyncPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'TLYNC')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return response()->json(['result' => 0, 'message' => trans('api.message194')]);
        }
        return $paymentOption;
    } 

    public function paymentRequest($request,$payment_option_config, $calling_from){
        try {
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $phone=$user->phoneNumber;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $phone=$user->UserPhone;
            }
            $paymentOption=$this->getTlyncPayConfig($merchant_id);
            $api_key = $paymentOption->auth_token;
            $store_id = $paymentOption->api_public_key;
        
            // $phone = "0911234567";
            $amount = $request->amount;
            $string_file = $this->getStringFile($merchant_id);
            
            $ref = "ORDER-".date('YmdHis');
            $url = $paymentOption->gateway_condition == 1 ? 'https://api.tlync.ly/api/v3/payment/initiate' :'https://uat-api.tlync.ly/api/v3/payment/initiate';
            $backend_url  = route('tlync.webhook');          
            $frontend_url = route('tlync.fronturl',['ref' => $ref,'success'=>"success"]);     
            $fail_url=route('tlync.fronturl',['ref' => $ref,'fail'=>"fail"]);
            

            $postFields = http_build_query([
                'id'           => $store_id,
                'amount'       => $amount,
                'phone'        => $phone,
                'email'        => $user->email,
                'backend_url'  => $backend_url,
                'frontend_url' => $frontend_url,
                'custom_ref'   => $ref,
            ]);
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
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer '.$api_key
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            


            \Log::channel('tlync')->emergency(json_decode($response,true));
            $response = json_decode($response,true);
            
            $checkoutId = $response['custom_ref'];
            $integrity  = $response['custom_ref'];
            $webViewUrl= $response['url'];
            if($response && isset($response['result'] ) &&  $response['result'] == "success"){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'merchant_id' => $merchant_id,
                    // 'payment_transaction_id'=>$response['id'],
                    'amount' => $amount,
                    'reference_id'=> $ref,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                // $checkoutId = $response['id'];
                // $integrity  = $response['integrity'];
               
                
                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' =>$webViewUrl,
                    'integrity' => $ref ?? '',
                    'success_url' => $frontend_url,
                    'fail_url' => $fail_url,
                ];
            }else{
                throw new \Exception("Something Went Wrong");
            }
        } catch (\Throwable $e) {
            \Log::channel('tlync')->error('TLYNC paymentRequest failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
    
            throw new \Exception("Something Went Wrong");
        }
    }

    public function PaymentStatus($reference_id){
       
        $transaction=DB::table('transactions')->where('reference_id',$reference_id)->first();
        $paymentOption=$this->getTlyncPayConfig($transaction->merchant_id);
        $api_key = $paymentOption->auth_token;
        $store_id = $paymentOption->api_public_key;
        $url = $paymentOption->gateway_condition == 1 ? 'https://api.tlync.ly/api/v3/receipt/transaction' :'https://uat-api.tlync.ly/api/v3/receipt/transaction';

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
        CURLOPT_POSTFIELDS => 'store_id='.$store_id.'&custom_ref='.$reference_id,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$api_key
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);



        \Log::channel('tlync')->info('TLYNC payment status', ['response' =>json_decode($response,true)]);
        $data = json_decode($response,true);
        if ($data['result'] == "success"){
            DB::table('transactions')
                    ->where(['reference_id' => $reference_id])
                    ->update(['request_status' => 2, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    
            $request_status_text = "success";
            $transaction_status = 2;
            $return_data = ['payment_status' => true, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }else{
            DB::table('transactions')
                    ->where(['reference_id' => $reference_id])
                    ->update(['request_status' => 3, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
            $request_status_text = "failed";
            $transaction_status = 3;
            $return_data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        return $response;
    }

    public function frontentPage(Request $request){
         $ref = $request->query('ref');
         $this->PaymentStatus($ref);
        return view('payment/tlync/success');
    }

   
    public function webhook(Request $request)
    {
        // Log everything first, before any processing, so nothing is lost even if
        // verification below fails or throws.
        \Log::channel('tlync')->info('TLYNC webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'ip'      => $request->ip(),
        ]);

        $customRef = $request->input('custom_ref');

        if (empty($customRef)) {
            \Log::channel('tlync')->warning('TLYNC webhook missing custom_ref', [
                'payload' => $request->all(),
            ]);
            return response()->json(['result' => 'received'], 200);
        }

        // The doc doesn't document backend_url's payload shape, so don't trust it
        // blindly — confirm real status via the receipt endpoint before crediting.
        // $tlync   = new TlyncPaymentService();
        // $receipt = $tlync->getTransactionReceipt(customRef: $customRef);

        \Log::channel('tlync')->info('TLYNC receipt verification', [
            'custom_ref' => $customRef,
            'receipt'    => $receipt,
        ]);

        // if ($receipt['success']) {
        //     DB::table('transactions')
        //     ->where(['payment_transaction_id' => $request->id])
        //     ->update(['request_status' => 2,'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);      
        // }

        return response()->json(['result' => 'received'], 200);
    }
    public function webhookchecker(Request $request)
    {
        \Log::channel('checkr')->info('Checkr webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'ip'      => $request->ip(),
        ]);
    
        return response()->json(['received' => true], 200);
    }

}

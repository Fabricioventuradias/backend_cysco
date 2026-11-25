<?php

namespace App\Http\Controllers\PaymentMethods\PayWay;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayWayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getAbaPayConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'PAYWAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id', '=', $payment_option->id],])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env)
    {
        return $env == 1 ? "https://checkout.payway.com.kh" : "https://checkout-sandbox.payway.com.kh";
    }

    public function paymentRequest($request, $payment_option_config, $calling_from)
    {
        try {
            $amount = $request->amount;
            $apiKey = $payment_option_config->api_secret_key;
            $abaMerchantId = $payment_option_config->api_public_key;
            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $currency = $payment_option_config->gateway_condition == 1 ? $request->currency : "USD";  //for test it is in USD
            $payment_option = $this->getAbaPayConfig($merchant_id);
            $base_url = $this->getUrl($payment_option->gateway_condition);

            $reqTime = gmdate('YmdHis');
            $tranId  = 'TRANS-' . time();
            $formattedAmount = number_format((float) $amount, 2, '.', '');

            $firstname = isset($user->first_name) ? $user->first_name : ($user->name ?? '');
            $lastname  = $user->last_name ?? '';
            $email     = $user->email ?? '';
            $phone     = $user->phone ?? $request->phone;

            $type = 'purchase';
            $paymentOptionValue = 'abapay_khqr_deeplink';

            $items = base64_encode(json_encode([
                ['name' => 'Order Payment', 'quantity' => 1, 'price' => (float) $formattedAmount],
            ]));

            $shipping = '';
            
            $domain = request()->getHost();
            $parts = explode('.', $domain);
            $appScheme = count($parts) >= 2 ? $parts[count($parts) - 2] : $domain;
            
            $returnUrl = base64_encode(route('payway-webhook'));
            $cancelUrl = '';
            $continueSuccessUrl = route('payway-success');
            $returnDeeplink = base64_encode(json_encode([
                'ios_scheme'     => $appScheme . '://payway-payment-result',
                'android_scheme' => $appScheme . '://payway-payment-result',
            ]));

            $customFields = '';
            $returnParams = (string) ($request->booking_id ?? $request->order_id ?? $request->handyman_order_id ?? '');
            $payout = '';
            $lifetime = '6';
            $additionalParams = '';
            $googlePayToken = '';
            $skipSuccessPage = '';

            
            $hashString = $reqTime . $abaMerchantId . $tranId . $formattedAmount . $items 
                . $firstname . $lastname . $email . $phone . $type . $paymentOptionValue
                . $returnUrl . $cancelUrl . $continueSuccessUrl . $returnDeeplink
                . $currency . $customFields . $returnParams . $payout . $lifetime
                . $additionalParams . $googlePayToken . $skipSuccessPage;

            $hash = base64_encode(hash_hmac('sha512', $hashString, $apiKey, true));

            $data = [
                'req_time'             => $reqTime,
                'merchant_id'          => $abaMerchantId,
                'tran_id'              => $tranId,
                'firstname'            => $firstname,
                'lastname'             => $lastname,
                'email'                => $email,
                'phone'                => $phone,
                'type'                 => $type,
                'payment_option'       => $paymentOptionValue,
                'items'                => $items,
                'amount'               => $formattedAmount,
                'currency'             => $currency,
                'return_url'           => $returnUrl,
                'cancel_url'           => $cancelUrl,
                'continue_success_url' => $continueSuccessUrl,
                'return_deeplink'      => $returnDeeplink,
                'custom_fields'        => $customFields,
                'return_params'        => $returnParams,
                'payout'               => $payout,
                'lifetime'             => $lifetime,
                'additional_params'    => $additionalParams,
                'google_pay_token'     => $googlePayToken,
                'skip_success_page'    => $skipSuccessPage,
                'hash'                 => $hash,
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url . '/api/payment-gateway/v1/payments/purchase',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json'
                ),
            ));

            $rawResponse = curl_exec($curl);
            $response = json_decode($rawResponse, true);
            $curlErrno   = curl_errno($curl);
            $curlError   = curl_error($curl);
            $httpCode     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $redirectUrl  = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
            curl_close($curl);
            // dd($base_url . '/api/payment-gateway/v1/payments/purchase',$data,$response);
            \Log::channel('pay_way')->emergency(["purchase_response"=>$response]);

            if (isset($response['status']['code']) && $response['status']['code'] === '00') {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $tranId,
                    'amount' => $amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'reference_id' => $tranId,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                throw new \Exception($response['status']['message'] ?? 'ABA PayWay request failed');
            }

            return [
                'transaction_id'  => $tranId,
                'qr_string'       => $response['qr_string'] ?? null,
                'abapay_deeplink' => $response['abapay_deeplink'] ?? null,
                'checkout_qr_url' => $response['checkout_qr_url'] ?? null,
                'success_url'=> route('payway-success'),
                'fail_url'=> route('payway-fail'),
                'payment_result'=> $appScheme . '://payway-payment-result'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    // public function checkTransaction($merchant_id, $tranId)
    // {
    //     $payment_option = $this->getAbaPayConfig($merchant_id);
    //     $base_url = $this->getUrl($payment_option->gateway_condition);
    //     $apiKey = $payment_option->api_secret_key;
    //     $abaMerchantId = $payment_option->merchant_key; // TODO: confirm actual column name

    //     $reqTime = gmdate('YmdHis');
    //     $hashString = $reqTime . $abaMerchantId . $tranId;
    //     $hash = base64_encode(hash_hmac('sha512', $hashString, $apiKey, true));

    //     $data = [
    //         'req_time'    => $reqTime,
    //         'merchant_id' => $abaMerchantId,
    //         'tran_id'     => $tranId,
    //         'hash'        => $hash,
    //     ];

    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $base_url . '/api/payment-gateway/v1/payments/check-transaction-2',
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_CUSTOMREQUEST => 'POST',
    //         CURLOPT_POSTFIELDS => json_encode($data),
    //         CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    //     ));

    //     $response = json_decode(curl_exec($curl), true);
    //     curl_close($curl);

    //     $paid = isset($response['status']['code']) && $response['status']['code'] === '00';

    //     if ($paid) {
    //         DB::table('transactions')
    //             ->where('reference_id', $tranId)
    //             ->update(['status_message' => 'SUCCESS', 'updated_at' => date('Y-m-d H:i:s')]);
    //     }

    //     return $this->successResponse(['paid' => $paid, 'raw' => $response]);
    // }

    
    public function Callback(Request $request)
    {
        \Log::channel('pay_way')->emergency(["webhook_response"=>$request->all()]);
        echo 'WEBHOOK_RESPONSE_SUCCESS';
        // $payload = json_decode($request->getContent(), true) ?? [];
        // $receivedSignature = $request->header('X-PAYWAY-HMAC-SHA512');

        // $tranId = $payload['tran_id'] ?? null;

        // $transaction = DB::table('transactions')->where('reference_id', $tranId)->first();
        // if (!$transaction) {
        //     Log::channel('aba_khqr')->warning('ABA PayWay webhook: unknown tran_id', ['payload' => $payload]);
        //     return response()->json(['message' => 'Unknown transaction'], 404);
        // }

        // $payment_option_config = PaymentOptionsConfiguration::where('id', $transaction->payment_option_id)->first();
        // $apiKey = $payment_option_config->api_secret_key ?? null;

        // $sorted = $payload;
        // ksort($sorted);
        // $b4hash = '';
        // foreach ($sorted as $value) {
        //     $b4hash .= is_array($value) ? json_encode($value) : $value;
        // }
        // $expectedSignature = base64_encode(hash_hmac('sha512', $b4hash, $apiKey, true));

        // if (!$receivedSignature || !hash_equals($expectedSignature, $receivedSignature)) {
        //     Log::channel('aba_khqr')->warning('ABA PayWay webhook signature mismatch', ['payload' => $payload]);
        //     return response()->json(['message' => 'Invalid signature'], 401);
        // }

        // $status = $payload['status'] ?? null; // "0" = success per PayWay docs

        // DB::table('transactions')
        //     ->where('reference_id', $tranId)
        //     ->update([
        //         'status_message' => $status === '0' ? 'SUCCESS' : 'FAILED',
        //         'updated_at' => date('Y-m-d H:i:s'),
        //     ]);

        // Log::channel('aba_khqr')->info('ABA PayWay webhook processed', ['tran_id' => $tranId, 'status' => $status]);

        // return response()->json(['message' => 'OK'], 200);
    }
    
    public function Success(Request $request){
        echo '<h3>SUCCESS</h3>';
    }
    
    public function Fail(Request $request){
        echo '<h3>FAIL</h3>';
    }
}
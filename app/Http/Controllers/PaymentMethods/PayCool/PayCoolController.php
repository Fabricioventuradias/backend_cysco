<?php

namespace App\Http\Controllers\PaymentMethods\PayCool;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\DB;

class PayCoolController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getPayCoolChannels(Request $request)
    {
        try {
            if ($request->calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $merchant_id = $driver->merchant_id;
            } elseif ($request->calling_from == "USER") {
                $user = $request->user('api');
                $merchant_id = $user->merchant_id;
            }

        $payment_option = PaymentOption::where('slug', 'PAYCOOL')->first();
        $paymenConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymenConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

            $appId = $paymenConfig->auth_token;

            $url = "https://api.paycools.com.ph/api/v1/payment/channels";  

            $privateKey = file_get_contents(
                storage_path('paycool-private.key')
            );

            $signPayload = [
                'appId' => $appId
            ];


            $sign = $this->generate_sign(
                $signPayload,
                $privateKey
            );

            $finalUrl = $url .
                '?appId=' . urlencode($appId) .
                '&sign=' . urlencode($sign);


            $ch = curl_init();

            curl_setopt_array($ch, [

                CURLOPT_URL => $finalUrl,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_CUSTOMREQUEST => "GET",

                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ],

                CURLOPT_TIMEOUT => 60,
            ]);

            $response = json_decode(curl_exec($ch), true);

            $error = curl_error($ch);

            curl_close($ch);

            if ($error) {

                return $this->failedResponse(trans("$string_file.driver_already_registered_for_stripe_connect"));
            }

            return $this->successResponse(trans("$string_file.success"), $response['data']);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    
    

    public function makePaymentUsingPayCool($request, $paymentConfig, $calling_from)
    {
        try {
        if ($calling_from == "DRIVER") {
            $driver = $request->user('api-driver');
            $id = $driver->id;
            $phone = $driver->phoneNumber;
            $merchant_id = $driver->merchant_id;
            $status = 2;
        } elseif ($calling_from == "USER") {
            $user = $request->user('api');
            $id = $user->id;
            $phone = $user->UserPhone;
            $merchant_id = $user->merchant_id;
            $status = 1;
        }
        $actualAmount = (float) $request['amount']; // original amount
        $amount = (int) round($actualAmount * 100);  // cents for API
        $phone = '09899530557';

            $amount = (int) round(
                $request['amount'] * 100
            );
            $orderId = 'PC' . time() . rand(1000, 9999);
            
                // Channel routing
        $paymentChannels = ['GCASH_URL', 'PAYMAYA_URL', 'GRPY_URL','BPIA_URL', 'UBPB_URL', 'RCBC_URL', 'CARD_URL', ];
        $qrCodeChannels  = ['QRPH_DYNAMIC_QR'];

        $channelCode = $request['channelCode'] ?? null;

        if (in_array($channelCode, $paymentChannels)) {
            $endpoint = 'payment';
        } elseif (in_array($channelCode, $qrCodeChannels)) {
            $endpoint = 'qrcode';
        } else {
            return [
                'status'  => false,
                'message' => 'Invalid or missing channelCode.'
            ];
        }

        // For qrcode, mobile is mandatory
        if ($endpoint === 'qrcode' && empty($phone)) {
            return [
                'status'  => false,
                'message' => 'Mobile number is required for QR code payments.'
            ];
        }

        $baseUrl     = "https://api.paycools.com.ph/api/v1/{$endpoint}";    //qrcode, payment
           
            
            $appId = $paymentConfig['auth_token'];
            $appName = "nRoyal Systems Corp.";
            $privateKey = file_get_contents(storage_path('paycool-private.key'));

            $payload = [
                'appId' => $appId,
                'appName' => $appName ?? "test",
                'mchOrderId' => $orderId,

                'amount' => $amount,

                'customerName' =>
                    $request['customer_name']
                    ?? 'Customer',

                'channelCode' =>  $request['channelCode']
                    ?? null,

                'email' =>
                    $request['email']
                    ?? "james@gmail.com",

                'mobile' =>
                    $phone
                    ?? '09899530557',

                'remark' =>
                    'Payment #' . $orderId, 

                'callbackUrl' =>
                    route('paycool-callback'),

                'redirectUrl' =>
                    route('paycool-redirect'),

                'expireSeconds' => 1800
            ];
            if ($endpoint === 'qrcode') {
                $payload['mobile'] = '';
            } 

            /**
             * Generate signature
             */
            $payload['sign'] = $this->generate_sign(
                $payload,
                $privateKey
            );
            
            

            
            \Log::channel('paycool')->emergency(['payload' => $payload]);
            
            // dd($payload);
            

            $ch = curl_init();

            curl_setopt_array($ch, [

                CURLOPT_URL => $baseUrl,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],

                CURLOPT_POSTFIELDS => json_encode($payload),

                CURLOPT_TIMEOUT => 60,
            ]);
            $res = curl_exec($ch);
            $result = json_decode($res, true);
           
            

            $error = curl_error($ch);

            curl_close($ch);
     \Log::channel('paycool')->emergency(['response' => $res]);

 
        if (isset($result['code']) && $result['code'] == 1000) {
            //   dd($result);
            $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                if ($endpoint === 'qrcode') {
                    $transactionId = $result['data']['qrcodeId'];
                } else {
                    $transactionId = $result['data']['transactionId'];
                }
            DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_for,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $transactionId,
                'amount' => $amount,
                'payment_option_id' => $request->payment_option_id ?? null,
                'request_status' => 1,
                'status_message' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
     return [
    'response' => array_merge($result['data'], [
        'appRedirectUrl' => route('paycool-redirect'),
    ]),
];

        } else {
             
            return [

                'response' => $result
            ];
        }
            return $responseData;

        } catch (\Throwable $e) {

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate PayCools RSA2 Signature
     *
     * Rules from docs:
     * - remove sign
     * - remove null / empty
     * - ASCII ascending sort
     * - remove billingAddress
     * - remove shippingAddress
     * - remove goodsDetails
     */
    public function generate_sign(
        array $data,
        string $privateKey
    ): string {

        unset(
            $data['sign'],
            $data['billingAddress'],
            $data['shippingAddress'],
            $data['goodsDetails']
        );

        /**
         * Remove empty values
         */
        $filtered = collect($data)
            ->reject(function ($value) {

                return $value === null
                    || $value === ''
                    || $value === [];
            })
            ->sortKeys()
            ->toArray();

        /**
         * Create sign string
         */
        $signString = urldecode(
            http_build_query($filtered)
        );

        Log::info('PayCools Sign String', [
            'sign_string' => $signString
        ]);

        /**
         * Normalize private key
         */
        $privateKey = trim($privateKey);

        /**
         * Convert string to OpenSSL private key resource
         */
        $privateKeyResource = openssl_pkey_get_private(
            $privateKey
        );

        if (!$privateKeyResource) {
            throw new \Exception(
                'Invalid private key'
            );
        }

        /**
         * Generate RSA SHA256 signature
         */
        $signature = '';

        $result = openssl_sign(
            $signString,
            $signature,
            $privateKeyResource,
            OPENSSL_ALGO_SHA256
        );

        if (!$result) {

            throw new \Exception(
                'Unable to generate signature'
            );
        }

        /**
         * Free key resource
         */
        openssl_free_key($privateKeyResource);

        return base64_encode($signature);
    }




public function PayCoolCallBack(Request $request)
{
    \Log::channel('paycool')->emergency(['callback_url' => $request->all()]);
    $data = $request->all();

    if (!isset($data['transactionStatus']) || !isset($data['transactionId'])) {
        return "fail";
    }

    $status = $data['transactionStatus'];

    if ($status === 'COMPLETE') {
        $requestStatus = 2; // SUCCESS
    } elseif ($status === 'FAILED') {
        $requestStatus = 3; // FAILED
    } elseif ($status === 'CLOSED') {
        $requestStatus = 4; // OTHER
    } else {
        $requestStatus = 4; // OTHER
    }

    DB::table('transactions')
        ->where('payment_transaction_id', $data['transactionId'])
        ->update([
            'request_status'      => $requestStatus,
            'payment_transaction' => json_encode($data),
            'reference_id'        => $data['mchOrderId'],
            'updated_at'          => now(),
            'status_message'      => $status,
        ]);

    \Log::channel('paycool')->emergency([
        'callback_updated' => [
            'transactionId'  => $data['transactionId'],
            'status'         => $status,
            'request_status' => $requestStatus,
        ]
    ]);

    return "success";
}


    public function PayCoolRedirect()
    {
        echo "<h1>Redirecting......</h1>";
    }
}
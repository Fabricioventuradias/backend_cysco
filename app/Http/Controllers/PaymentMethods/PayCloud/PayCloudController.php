<?php

namespace App\Http\Controllers\PaymentMethods\PayCloud;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayCloudController extends Controller
{
    public function makePaymentUsingPayCloud($request, $paymentConfig, $calling_from)
    {
        try {

            // Credentials
            $appId = $paymentConfig->api_public_key;
            $merchantNo = $paymentConfig->auth_token; //merchant no
            $storeNo = $paymentConfig->store_no;

            // Private Key
            $appRsaPrivateKey = $this->reformatPemKey($paymentConfig->api_secret_key); //private key

            // Gateway URL
            $gatewayUrl = "https://open.paycloud.africa/api/entry";

            // Create Order Number
            $merchantOrderNo = 'ORD_' . time();

            // Request Parameters
            $parameters = [
                "app_id"            => $appId,
                "merchant_no"       => $merchantNo,
                "store_no"          => $storeNo,
                "merchant_order_no" => $merchantOrderNo,
                "price_currency"    => "ZAR",
                "order_amount"      => $request->amount,
                "expires"           => 300,
                "attach"            => json_encode([
                    "user_id" => $request->user_id
                ]),
                // "notify_url"        => route('paycloud.notify'),
                // "return_url"        => route('paycloud.return'),
                "term_ip"           => request()->ip(),
                "description"       => "Wallet Payment",
                "latitude"          => "30.5595",
                "longitude"         => "22.9375",
                "format"            => "JSON",
                "charset"           => "UTF-8",
                "sign_type"         => "RSA2",
                "version"           => "1.0",
                "timestamp"         => round(microtime(true) * 1000),
                "method"            => "pay.paycloud.checkout",
                "pay_options" => "Card,ScanToPay",
            ];

            // Build Sign String
            $stringToBeSigned = $this->buildToBeSignedString($parameters);

            // Generate Signature
            $parameters['sign'] = $this->generateSign(
                $stringToBeSigned,
                $appRsaPrivateKey
            );

            // Send API Request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($gatewayUrl, $parameters);

            // Response
            if ($response->successful()) {
            
                $responseData = $response->json();
              
            if (isset($responseData['data'])){
                $payData = json_decode($responseData['data'], true);
          
            
                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $payData['pay_url'] ?? '',
                    'transaction_id' => $trans_id ?? '',
                    'success_url' => route('paycloud-success'),
                    'fail_url' => route('paycloud-fail'), 
                    'redirect_url' => route('paycloud-redirect'),
                ];
            }
            return [
                'status' => 'error',
                'message' => $responseData['msg'] ?? 'Pay URL not found',
            ];
            }

            Log::error('PayCloud API Error', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

    

        } catch (\Exception $e) {

            Log::error('PayCloud Exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reformat PEM Key
     */
    private function reformatPemKey($rawKey, $keyType = "PRIVATE")
    {
        $rawKey = str_replace([
            "-----BEGIN {$keyType} KEY-----",
            "-----END {$keyType} KEY-----",
            "\n",
            "\r"
        ], '', $rawKey);

        $formattedKey = wordwrap($rawKey, 64, "\n", true);

        return "-----BEGIN {$keyType} KEY-----\n"
            . $formattedKey .
            "\n-----END {$keyType} KEY-----";
    }

    /**
     * Build String To Sign
     */
    private function buildToBeSignedString($params)
    {
        unset($params['sign']);

        ksort($params);

        $stringToBeSigned = '';

        foreach ($params as $key => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            $stringToBeSigned .= $key . '=' . $value . '&';
        }

        return rtrim($stringToBeSigned, '&');
    }

    /**
     * Generate RSA2 Signature
     */
    private function generateSign($data, $privateKey)
    {
        openssl_sign(
            $data,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        return base64_encode($signature);
    }

    /**
     * PayCloud Notify URL
     */
    public function notify(Request $request)
    {
        Log::info('PayCloud Notify', $request->all());

        // Verify signature here

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Return URL
     */
    public function return(Request $request)
    {
        return view('payments.success');
    }
}
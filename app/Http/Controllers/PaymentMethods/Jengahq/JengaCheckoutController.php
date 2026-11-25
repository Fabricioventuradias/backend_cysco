<?php

namespace App\Http\Controllers\PaymentMethods\Jengahq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use Exception;
use Illuminate\Support\Facades\Storage;

class JengaCheckoutController extends Controller
{
    use ApiResponseTrait,MerchantTrait;
    
    public function getBaseUrl($env){
        return $env == 1 ? 'https://api.finserve.africa/' : 'https://uat.finserve.africa/';
    }
    
    public function getJengaHqConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'JENGAHQ_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    public function generatePaymentUrl($request, $payment_option_config, $calling_from)
    {
        $status = 3;
        if ($calling_from == "DRIVER") {
            $user        = $request->user('api-driver');
            $id          = $user->id;
            $status      = 2;
            $merchant_id = $user->merchant_id;
            $email       = $user->email;
            $first_name  = $user->first_name;
            $last_name   = $user->last_name;
            $phone       = $user->phoneNumber;
        } else {
            $user        = $request->user('api');
            $id          = $user->id;
            $status      = 1;
            $merchant_id = $user->merchant_id;
            $email       = $user->email;
            $first_name  = $user->first_name;
            $last_name   = $user->last_name;
            $phone       = $user->UserPhone;
        }
    
        $currency       = $request->currency;
        $amount         = (string) $request->amount;
        $orderReference = 'REF_' . time();
    
        DB::table('transactions')->insert([
            'user_id'                => $calling_from == "USER" ? $id : null,
            'driver_id'              => $calling_from == "DRIVER" ? $id : null,
            'status'                 => $status,
            'booking_id'             => $request->booking_id,
            'order_id'               => $request->order_id,
            'handyman_order_id'      => $request->handyman_order_id,
            'merchant_id'            => $merchant_id,
            'payment_transaction_id' => $orderReference,
            'amount'                 => $amount,
            'payment_option_id'      => $payment_option_config->payment_option_id,
            'reference_id'           => '',
            'checkout_id'            => $orderReference,
            'request_status'         => 1,
            'status_message'         => 'PENDING',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
    
        // NO token, NO signature here — that's the fix
        $ref = [
            'merchant_id'        => $merchant_id, // still needed to look up config in paymentUrl()
            'orderReference'     => $orderReference,
            'currency'           => $currency,
            'amount'             => $amount,
            'customerFirstName'  => $first_name,
            'customerLastName'   => $last_name,
            'customerEmail'      => $email,
            'customerPhone'      => $phone,
        ];
    
        $param = urlencode(encrypt($ref));
    
        return [
            'status'         => 'NEED_TO_OPEN_WEBVIEW',
            'url'            => route('jengahq.payment-url', ['ref' => $param]),
            'transaction_id' => $orderReference,
            'success_url'    => route('jengahq-success'),
            'fail_url'       => route('jengahq-fail'),
        ];
    }

    public function paymentUrl(Request $request)
    {
        $ref = urldecode($request->query('ref'));
        try {
            $decryptRef = decrypt($ref);
        } catch (\Exception $e) {
            return $this->failedResponse('Invalid or expired payment link.');
        }
    
        $merchant_id           = $decryptRef['merchant_id'];
        $payment_option_config = $this->getJengaHqConfig($merchant_id);
    
        if (empty($payment_option_config)) {
            return $this->failedResponse('Configuration not found.');
        }
    
        $merchantCode   = $payment_option_config->api_public_key;
        $consumerSecret = $payment_option_config->api_secret_key;
        $apiKey         = $payment_option_config->auth_token;
        $callbackUrl    = route('jengahq-callback');
        $baseUrl = $this->getBaseUrl($payment_option_config->gateway_condition);
    
        // Generated NOW — the exact moment the webview opens, zero gap
        $token = $this->getAccessToken($merchantCode, $consumerSecret, $apiKey,$baseUrl);
        if (!$token) {
            return $this->failedResponse('Could not authenticate with Jenga PGW. Please try again.');
        }
    
        $params = [
            'merchantCode'   => $merchantCode,
            'orderReference' => $decryptRef['orderReference'],
            'currency'       => $decryptRef['currency'],
            'orderAmount'    => $decryptRef['amount'],
            'callbackUrl'    => $callbackUrl,
        ];
    
        try {
            $signature = $this->generateSignature($params);
        } catch (\RuntimeException $e) {
            return $this->failedResponse('Unable to prepare payment. Please contact support.');
        }

        $data = [
            'checkoutUrl'           => 'https://v3-uat.jengapgw.io/processPayment',
            'token'                 => $token,
            'merchantCode'          => $merchantCode,
            'currency'              => $decryptRef['currency'],
            'orderAmount'           => $decryptRef['amount'],
            'orderReference'        => $decryptRef['orderReference'],
            'productType'           => 'Product/Service',
            'productDescription'    => 'Payment',
            'paymentTimeLimit'      => '30',
            'customerFirstName'     => $decryptRef['customerFirstName'],
            'customerLastName'      => $decryptRef['customerLastName'],
            'customerPostalCodeZip' => '00100',
            'customerAddress'       => 'N/A',
            'customerEmail'         => $decryptRef['customerEmail'],
            'customerPhone'         => $decryptRef['customerPhone'],
            'extraData'             => '',
            'callbackUrl'           => $callbackUrl,
            'countryCode'           => 'KE',
            'secondaryReference'    => $decryptRef['orderReference'],
            'signature'             => $signature,
        ];
        \Log::channel('jengahq_api')->emergency(['reponse_url'=>$data]);
        return view('payment.jenga.checkout', $data);
    }
    
    protected function getAccessToken($merchantCode, $consumerSecret, $apiKey,$baseUrl)
    {
        $url = $baseUrl.'/authentication/api/v3/authenticate/merchant';
        $payload = json_encode([
            'merchantCode'   => $merchantCode,
            'consumerSecret' => $consumerSecret,
        ]);
    
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                "Api-Key: {$apiKey}",
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
    
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        if ($curlError || $httpCode !== 200) {
            return null;
        }
    
        return json_decode($response, true)['accessToken'] ?? null;
    }
    
    protected function generateSignature(array $params): string
    {
        $dataToSign = $params['merchantCode']
            . $params['orderReference']
            . $params['currency']
            . $params['orderAmount']
            . $params['callbackUrl'];
    
        $disk = Storage::disk('merchant_keys');
        $privateKeyFile = "mtprivate.pem"; // per-merchant key file
    
        if (!$disk->exists($privateKeyFile)) {
            throw new \RuntimeException("Jenga private key not found: {$privateKeyFile}");
        }
    
        $privateKeyPem = file_get_contents($disk->path($privateKeyFile));
        $privateKey = openssl_pkey_get_private($privateKeyPem);
    
        if (!$privateKey) {
            throw new \RuntimeException('Unable to load Jenga private key. Check the PEM file contents/format.');
        }
    
        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
        return base64_encode($signature);
    }
    
    public function callback(Request $request)
    {
         \Log::channel('jengahq_api')->emergency(['callback_url_response'=>$request->all()]);
        $data = $request->query();


        $responseStatus       = $data['responseStatus'] ?? null;
        $transactionStatus    = $data['transactionStatus'] ?? null;
        $orderReference       = $data['orderReference'] ?? null;
        $transactionReference = $data['transactionReference'] ?? null;
        $message              = $data['message'] ?? null;

        $trans = DB::table('transactions')
            ->where('checkout_id', $orderReference)
            ->first();

        // if (!$trans) {
        //     return response()->json(['message' => 'transaction not found'], 404);
        // }

        if ($transactionStatus === 'SUCCESS' && $responseStatus === 'true') {
            DB::table('transactions')->where('id', $trans->id)->update([
                'request_status'      => 2,
                'reference_id'        => $transactionReference,
                'payment_transaction' => json_encode($data),
                'status_message'      => 'SUCCESS',
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            
            return redirect()->route('jengahq-success');
        } else {
            DB::table('transactions')->where('id', $trans->id)->update([
                'request_status'      => 3,
                'payment_transaction' => json_encode($data),
                'status_message'      => $message ?? 'FAILED',
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
            
            return redirect()->route('jengahq-fail');
        }

        return response()->json(['message' => 'callback processed']);
    }
    
    public function Success()
    {
        return '<h1>Payment Success.</h1>';
    }
    public function Fail()
    {
        return '<h1>Payment Faild.</h1>';
    }

}
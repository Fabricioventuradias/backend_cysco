<?php

namespace App\Http\Controllers\PaymentMethods\KkiaPay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use Exception;

class KkiapayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getKkiapayConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'KKIAPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([
            ['merchant_id', '=', $merchant_id],
            ['payment_option_id', '=', $payment_option->id]
        ])->first();
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

        $ref = [
            'merchant_id'       => $merchant_id,
            'orderReference'    => $orderReference,
            'amount'            => $amount,
            'customerFirstName' => $first_name,
            'customerLastName'  => $last_name,
            'customerEmail'     => $email,
            'customerPhone'     => $phone,
        ];

        $param = urlencode(encrypt($ref));

        return [
            'status'         => 'NEED_TO_OPEN_WEBVIEW',
            'url'            => route('kkiapay.payment-url', ['ref' => $param]),
            'transaction_id' => $orderReference,
            'success_url'    => route('kkiapay-success'),
            'fail_url'       => route('kkiapay-fail'),
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
        $payment_option_config = $this->getKkiapayConfig($merchant_id);
    
        if (empty($payment_option_config)) {
            return $this->failedResponse('Configuration not found.');
        }
    
        $publicKey = $payment_option_config->api_public_key;
        $sandbox   = $payment_option_config->gateway_condition == 1 ? 'false' : 'true';
    
        $data = [
            'public_key'         => $publicKey,
            'amount'             => $decryptRef['amount'],
            'sandbox'            => $sandbox,
            'orderReference'     => $decryptRef['orderReference'],
            'customerFirstName'  => $decryptRef['customerFirstName'],
            'customerLastName'   => $decryptRef['customerLastName'],
            'customerEmail'      => $decryptRef['customerEmail'],
            'customerPhone'      => $decryptRef['customerPhone'],
            'verifyUrl'          => route('kkiapay-callback'),
            'success_url'        => route('kkiapay-success'),
            'fail_url'           => route('kkiapay-fail'),
        ];
    
        \Log::channel('kkiapay_api')->emergency(['payment_url_data' => $data]);
    
        return view('payment.kkiapay.kkiapay', $data);
    }

    public function callback(Request $request)
    {
        \Log::channel('kkiapay_api')->emergency(['callback_request' => $request->all()]);
    
        $orderReference = $request->input('orderReference');
        $transactionId  = $request->input('transaction_id');
    
        $trans = DB::table('transactions')
            ->where('checkout_id', $orderReference)
            ->first();
    
        if (!$trans) {
            return response()->json(['message' => 'transaction not found'], 404);
        }
    
        $merchant_id           = $trans->merchant_id;
        $payment_option_config = $this->getKkiapayConfig($merchant_id);
    
        $publicKey  = $payment_option_config->api_public_key;
        $privateKey = $payment_option_config->auth_token;
        $secretKey  = $payment_option_config->api_secret_key;
        $sandbox    = $payment_option_config->gateway_condition != 1;
    
        try {
            $kkiapay = new \Kkiapay\Kkiapay($publicKey, $privateKey, $secretKey, $sandbox);
            $result  = $kkiapay->verifyTransaction($transactionId);
    
            \Log::channel('kkiapay_api')->emergency(['verify_response' => $result]);
    
            if (($result->status ?? null) === 'SUCCESS') {
                DB::table('transactions')->where('id', $trans->id)->update([
                    'request_status'      => 2,
                    'reference_id'        => $result->transactionId ?? $transactionId,
                    'payment_transaction' => json_encode($result),
                    'status_message'      => 'SUCCESS',
                    'updated_at'          => date('Y-m-d H:i:s'),
                ]);
    
                return response()->json(['success' => true, 'redirect' => route('kkiapay-success')]);
            }
    
            DB::table('transactions')->where('id', $trans->id)->update([
                'request_status'      => 3,
                'payment_transaction' => json_encode($result),
                'status_message'      => $result->status ?? 'FAILED',
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
    
            return response()->json(['success' => false, 'redirect' => route('kkiapay-fail')], 422);
        } catch (\Throwable $e) {
            Log::error('Kkiapay verify failed: ' . $e->getMessage());
    
            DB::table('transactions')->where('id', $trans->id)->update([
                'request_status' => 3,
                'status_message' => 'FAILED',
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
    
            return response()->json(['success' => false, 'redirect' => route('kkiapay-fail')], 422);
        }
    }

    public function Success()
    {
        return '<h1>Payment Success.</h1>';
    }

    public function Fail()
    {
        return '<h1>Payment Failed.</h1>';
    }
}
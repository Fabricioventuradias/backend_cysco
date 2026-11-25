<?php

namespace App\Http\Controllers\PaymentMethods\PayDunya;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayDunyaWebController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getPayDunyaConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'PAYDUNYA_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([
            ['merchant_id', '=', $merchant_id],
            ['payment_option_id', '=', $payment_option->id],
        ])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    // on production
    public function createPaymentUrl($request, $payment_option_config, $calling_from)
    {
        try {
            $masterKey = $payment_option_config->api_public_key;
            $privateKey = $payment_option_config->api_secret_key;
            $token = $payment_option_config->auth_token;
            $isLive = $payment_option_config->gateway_condition == 1;

            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $businessName = $user->Merchant->BusinessName;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $businessName = $user->Merchant->BusinessName;
            }

            $baseUrl = $isLive
                ? 'https://app.paydunya.com/api/v1'
                : 'https://app.paydunya.com/sandbox-api/v1';

            $gatewayUrl = $baseUrl . '/checkout-invoice/create';

            $payment_option = $this->getPayDunyaConfig($merchant_id);
            $reference = 'REFERENCE_MERCHANT_' . $id . '_' . time();
            $amount = $request->amount;

            $data = [
                'invoice' => [
                    'total_amount' => (int) $amount,
                    'description' => 'Payment for order ' . $reference,
                    'customer' => [
                        'name' => $user->name ?? '',
                        'email' => $user->email ?? '',
                        'phone' => $user->phone ?? '',
                    ],
                ],
                'store' => [
                    'name' => $businessName,
                ],
                'custom_data' => [
                    'reference' => $reference,
                ],
                'actions' => [
                    'cancel_url' => route('paydunya-cancel'),
                    'return_url' => route('paydunya-redirect', [$reference]),
                    'callback_url' => route('paydunya-webhook'),
                ],
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $gatewayUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'PAYDUNYA-MASTER-KEY: ' . $masterKey,
                    'PAYDUNYA-PRIVATE-KEY: ' . $privateKey,
                    'PAYDUNYA-TOKEN: ' . $token,
                ),
            ));

            $response = json_decode(curl_exec($curl));
            \Log::channel('paydunya')->emergency(["response"=>$response]);
            curl_close($curl);

            if (isset($response->response_code) && $response->response_code == '00') {
                $invoice_token = $response->token;

                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $invoice_token,
                    'amount' => $amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'request_status' => 1,
                    'reference_id' => $reference,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                throw new \Exception('Payment Url not generated');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        DB::commit();

        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response->response_text ?? '',
            'transaction_id' => $response->token ?? '',
            'success_url' => route('paydunya-success'),
            'fail_url' => route('paydunya-cancel'),
        ];
    }

    // Manual re-check of a transaction's status (mirrors the MpesaController status-check pattern)
    // public function checkPaymentStatus($invoice_token, $payment_option_config)
    // {
    //     $masterKey = $payment_option_config->api_public_key;
    //     $privateKey = $payment_option_config->api_secret_key;
    //     $token = $payment_option_config->auth_token;
    //     $isLive = $payment_option_config->gateway_condition == 1;

    //     $baseUrl = $isLive
    //         ? 'https://app.paydunya.com/api/v1'
    //         : 'https://app.paydunya.com/sandbox-api/v1';

    //     $url = $baseUrl . '/checkout-invoice/confirm/' . $invoice_token;

    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $url,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => array(
    //             'Content-Type: application/json',
    //             'PAYDUNYA-MASTER-KEY: ' . $masterKey,
    //             'PAYDUNYA-PRIVATE-KEY: ' . $privateKey,
    //             'PAYDUNYA-TOKEN: ' . $token,
    //         ),
    //     ));

    //     $response = json_decode(curl_exec($curl));
    //     curl_close($curl);

    //     return $response;
    // }

    public function Redirect(Request $request, $reference = null)
    {
        \Log::channel('paydunya')->emergency($request->all());
        $reference = $reference ?? $request->reference;
        if ($reference) {
            $transaction = DB::table('transactions')->where('reference_id', $reference)->first();
            if ($transaction && $transaction->request_status == 2 && $transaction->status_message == 'SUCCESS') {
                return redirect(route('paydunya-success'));
            }
            return redirect(route('paydunya-cancel'));
        }
        throw new \Exception('Reference not found');
    }

    public function Success(Request $request)
    {
        \Log::channel('paydunya')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Cancel(Request $request)
    {
        \Log::channel('paydunya')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    // Sole DB-writing endpoint — driven by PayDunya's IPN (callback_url), application/x-www-form-urlencoded
    public function Webhook(Request $request)
    {
        \Log::channel('paydunya')->emergency(["webhook_response"=>$request->all()]);

        $data = $request->input('data');
        if (empty($data) || !isset($data['hash'], $data['invoice']['token'], $data['status'])) {
            return response()->json(['response_code' => '01', 'response_text' => 'Invalid payload'], 400);
        }

        // TODO: verify $data['hash'] against SHA-512 hash of your master key
        // if (!hash_equals(hash('sha512', $masterKey), $data['hash'])) { ... reject ... }

        $invoice_token = $data['invoice']['token'];
        $status = $data['status']; // completed | cancelled | failed

        $request_status = $status == 'completed' ? 2 : ($status == 'failed' ? 3 : 4);
        $status_message = $status == 'completed' ? 'SUCCESS' : strtoupper($status);

        DB::table('transactions')
            ->where('payment_transaction_id', $invoice_token)
            ->update([
                'request_status' => $request_status,
                'status_message' => $status_message,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return response()->json(['response_code' => '00', 'response_text' => 'IPN received']);
    }
}
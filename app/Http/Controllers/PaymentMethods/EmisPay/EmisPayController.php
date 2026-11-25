<?php

namespace App\Http\Controllers\PaymentMethods\EmisPay;

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
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\DriverCashout;
use App\Models\BusinessSegment\BusinessSegmentCashout;
use App\Models\Onesignal;

class EmisPayController extends Controller
{
    // Slug for this gateway across payment_options / payment_options_configurations
    const SLUG = 'EMIS_PAY';

    /**
     * Step 1: create a frameToken with GPO and hand back a webview URL to the app,
     * same contract shape as MonnifyPaymentController::initiatePayment().
     */
    public function initiatePayment($request, $paymentConfig, $calling_from)
    {
        try {
            $status = 3;
            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                // $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 2;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $phone = $user->UserPhone;
            } else {
                $user = $request->user('api');
                // $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $phone = $user->phoneNumber;
            }

            $amount = $request->amount;
            $currency = $request->currency;

            // GPO config, per merchant (payment_options_configurations row)
            $merchantToken   = $paymentConfig->api_public_key;   // GPO "token" (Frame Token) from EMIS portal
            $environment     = $paymentConfig->gateway_condition; // 1 = live, 2 = cert/UAT if EMIS ever provisions one

            // reference must be <=15 chars, alphanumeric — do NOT reuse Monnify's long TRANS_ style id here
            $trans_id = "EMIS_" . time();

            $frameTokenBody = [
                "reference"   => $trans_id,
                "amount"      => $amount,          // GPO expects last 2 digits as decimals, confirm formatting with EMIS before going live
                "token"       => $merchantToken,
                "qrCode"      => "DISABLED",
                "mobile"      => "AUTHORIZATION",
                "card"        => "DISABLED",
                "callbackUrl" => route('emis-pay-webhook'),
            ];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frameToken",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($frameTokenBody),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
            ));

            $response = curl_exec($curl);
            $curlErr  = curl_error($curl);
            curl_close($curl);
            // dd("https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frameToken",$frameTokenBody,$response);
            \Log::channel('emis_pay')->emergency(['frameToken_request' => $frameTokenBody, 'frameToken_response' => $response, 'curl_error' => $curlErr]);

            $res = json_decode($response, true);
            if (empty($res['id'])) {
                // matches GPO's Table 2 error shape: { "code": "...", "message": "..." }
                throw new \Exception($res['message'] ?? 'GPO frameToken request failed');
            }

            $frameToken = $res['id'];

            DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'status' => $status,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $trans_id,
                'amount' => $amount,
                'payment_option_id' => $paymentConfig->payment_option_id,
                'reference_id' => $trans_id,
                'request_status' => 1,
                'status_message' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $data = [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('emis-pay-webview', ['token' => $frameToken, 'trans_id' => $trans_id]),
                'transaction_id' => $trans_id,
                'success_url' => route('emis-pay-success'),
                'fail_url' => route('emis-pay-fail'),
            ];

            return $data;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Step 2: render the GPO webframe inside our own page so we can catch its
     * postMessage event (GPO gives no native redirect/returnUrl param) and
     * bounce the WebView to success/fail ourselves.
     */
    public function webView(Request $request)
    {
        $frameToken = $request->token;
        $transId    = $request->trans_id;

        $frameUrl = "https://pagamentonline.emis.co.ao/online-payment-gateway/webframe/frame?token={$frameToken}";

        return view('payment.emispay.emispay', [
            'frame_url' => $frameUrl,
            'trans_id' => $transId,
            'success_url' => route('emis-pay-success'),
            'fail_url' => route('emis-pay-fail'),
        ]);
    }

    /**
     * Server-to-server callback — GPO's callbackUrl (§2.1.3.2.2). This is the
     * source of truth for transaction status, independent of the postMessage/
     * WebView bridge which is UX-only and can be missed if the app is killed.
     */
    public function webhook(Request $request)
    {
        \Log::channel('emis_pay')->emergency(['webhook_url' => $request->all()]);

        $data = $request->all();

        if (isset($data['reference'])) {
            $transaction_id = $data['reference'];
            $accepted = isset($data['status']) && strtoupper($data['status']) === 'ACCEPTED';

            DB::table('transactions')
                ->where(['payment_transaction_id' => $transaction_id])
                ->update([
                    'request_status' => $accepted ? 2 : 3,
                    'payment_transaction' => json_encode($data),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status_message' => $accepted ? 'SUCCESS' : 'FAIL',
                ]);
        }

        return 'webhok_success';
    }

    /**
     * Landing page only — no DB write here. The webhook() callback above is
     * the sole source of truth for transaction status; this route just gives
     * the WebView/browser somewhere to land after postMessage fires, and can
     * optionally read current status for display (e.g. "Payment received").
     */
    public function Success(Request $request)
    {
        \Log::channel('emis_pay')->emergency(['success_url' => $request->all()]);
        echo '<h3>Success</h3>';
    }

    public function Fail(Request $request)
    {
        \Log::channel('emis_pay')->emergency(['fail_url' => $request->all()]);
        echo '<h3>Failed</h3>';
    }
}
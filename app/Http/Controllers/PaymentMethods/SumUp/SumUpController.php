<?php

namespace App\Http\Controllers\PaymentMethods\SumUp;

use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\DriverCard;

class SumUpController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    // SumUp uses the same base URL for sandbox & live —
    // environment is decided by which account the API key belongs to.
    // public function getBaseUrl($env)
    // {
    //     return 'https://api.sumup.com/';
    // }

    // public function getSumUpConfig($merchant_id)
    // {
    //     $payment_option = PaymentOption::where('slug', 'SUMUP')->first();
    //     $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
    //     $string_file = $this->getStringFile($merchant_id);
    //     if (empty($paymentOption)) {
    //         return $this->failedResponse(trans("$string_file.configuration_not_found"));
    //     }
    //     return $paymentOption;
    // }

    // public function makePaymentUsingSumUp($request, $paymentConfig, $calling_from)
    // {
      
    //     $request->validate([
    //         'amount' => 'required|numeric|min:0.01',
    //     ]);

    //     $status = 3;
    //     if ($calling_from == "DRIVER") {
    //         $driver = $request->user('api-driver');
    //         $id = $driver->id;
    //         $merchant_id = $driver->merchant_id;
    //         $status = 2;
    //     } elseif ($calling_from == "USER") {
    //         $user = $request->user('api');
    //         $id = $user->id;
    //         $merchant_id = $user->merchant_id;
    //         $status = 1;
    //     }

    //     $transactionId = "trans" . $id . '' . time();
       

    //     // Config mapping (PaymentOptionsConfiguration columns):
    //     // secret_key    -> sup_sk_... API key
    //     // merchant_key  -> SumUp merchant code e.g. MC3HL7TA
    //     // callback_url  -> server-to-server webhook (return_url)
    //     $data = [
    //         "amount"             => round((float) $request->amount, 2),
    //         "currency"           => $paymentConfig->currency ?? 'CHF',
    //         "checkout_reference" => $transactionId,
    //         "merchant_code"      => $paymentConfig->auth_token,
    //         "description"        => ($calling_from == "USER" ? "User" : "Driver") . " wallet payment #" . $transactionId,
    //         "hosted_checkout"    => ["enabled" => true],
    //         "redirect_url"       => route('sumup-success'),          // browser lands here after paying
    //         "return_url"         => $paymentConfig->callback_url ?? '',    // SumUp POSTs status here (server)
    //     ];

    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => $this->getBaseUrl($paymentConfig->gateway_condition) . 'v0.1/checkouts',
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'POST',
    //         CURLOPT_POSTFIELDS => json_encode($data),
    //         CURLOPT_HTTPHEADER => [
    //             'Content-Type: application/json',
    //             'Authorization: Bearer ' . $paymentConfig->api_secret_key,
    //         ],
    //     ]);
    //     $response = json_decode(curl_exec($curl), true);
    //     $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    //     curl_close($curl);
     

    //     \Log::channel('sumup_pay')->emergency(['create_checkout_response' => $response, 'http_code' => $httpCode]);
    //     //   dd($response);

    // //     if ($httpCode == 201 && !empty($response['hosted_checkout_url'])) {

    // //         $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
    // //         DB::table('transactions')->insert([
    // //             'user_id' => $calling_from == "USER" ? $id : NULL,
    // //             'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
    // //             'status' => $calling_for,
    // //             'merchant_id' => $merchant_id,
    // //             'payment_transaction_id' => $transactionId,
    // //             'reference_id' => $response['id'], // SumUp checkout id — needed for status verification
    // //             'amount' => $request->amount,
    // //             'payment_option_id' => $paymentConfig->payment_option_id,
    // //             'request_status' => 1,
    // //             'status_message' => 'PENDING',
    // //             'created_at' => date('Y-m-d H:i:s'),
    // //             'updated_at' => date('Y-m-d H:i:s')
    // //         ]);

    // //         return [
    // //             'transaction_id' => $transactionId,
    // //             'payment_url'    => $response['hosted_checkout_url'], // app opens this in WebView/browser
    // //             'success_url'    => route('sumup-success'),
    // //             'fail_url'       => route('sumup-fail'),
    // //         ];
    // //     }

    // //     return [
    // //         'result' => 0,
    // //         'response' => $response['message'] ?? $response['detail'] ?? 'transaction not done'
    // //     ];
    // // }

    // // /**
    // //  * Server-to-server callback from SumUp (return_url).
    // //  * Payload contains checkout id + status, but we NEVER trust it blindly —
    // //  * we re-verify with a GET /v0.1/checkouts/{id} before updating.
    // //  */
    // // public function SumUpCallBack(Request $request)
    // // {
    // //     \Log::channel('sumup_pay')->emergency(['callback_url' => $request->all()]);
    // //     $data = $request->all();

    // //     if (!empty($data)) {
    // //         $checkout_id = $data['id'] ?? ($data['checkout_id'] ?? '');
    // //         if (empty($checkout_id)) {
    // //             return "success";
    // //         }

    // //         $trans = DB::table('transactions')->where(['request_status' => 1, 'reference_id' => $checkout_id])->first();

    // //         if (!empty($trans)) {
    // //             $paymentConfig = $this->getSumUpConfig($trans->merchant_id);

    // //             // verify with SumUp directly — source of truth
    // //             $checkout = $this->verifyCheckout($checkout_id, $paymentConfig);

    // //             $is_paid = isset($checkout['status'])
    // //                 && $checkout['status'] == 'PAID'
    // //                 && (float) $checkout['amount'] == (float) $trans->amount;

    // //             if ($is_paid) {
    // //                 DB::table('transactions')
    // //                     ->where(['payment_transaction_id' => $trans->payment_transaction_id])
    // //                     ->update([
    // //                         'request_status' => 2,
    // //                         'payment_transaction' => json_encode($checkout),
    // //                         'updated_at' => date('Y-m-d H:i:s'),
    // //                         'status_message' => 'SUCCESS'
    // //                     ]);
    // //             } elseif (isset($checkout['status']) && in_array($checkout['status'], ['FAILED', 'EXPIRED'])) {
    // //                 DB::table('transactions')
    // //                     ->where(['payment_transaction_id' => $trans->payment_transaction_id])
    // //                     ->update([
    // //                         'request_status' => 3,
    // //                         'payment_transaction' => json_encode($checkout),
    // //                         'updated_at' => date('Y-m-d H:i:s'),
    // //                         'status_message' => 'FAIL'
    // //                     ]);
    // //             }
    // //             // if still PENDING, leave request_status = 1; app keeps polling PaymentStatus
    // //         }
    // //     }
    //     return "success";
    // }

    // protected function verifyCheckout($checkout_id, $paymentConfig)
    // {
    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => $this->getBaseUrl($paymentConfig->gateway_condition) . 'v0.1/checkouts/' . urlencode($checkout_id),
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => [
    //             'Authorization: Bearer ' . $paymentConfig->secret_key,
    //         ],
    //     ]);
    //     $response = json_decode(curl_exec($curl), true);
    //     curl_close($curl);

    //     \Log::channel('sumup_pay')->emergency(['verify_checkout' => $response]);
    //     return $response ?? [];
    // }

    // public function sumupPaySuccess(Request $request)
    // {
    //     \Log::channel('sumup_pay')->emergency(['success_url' => $request->all()]);
    //     echo '<h3>Success</h3>';
    // }

    // public function sumupPayFail(Request $request)
    // {
    //     \Log::channel('sumup_pay')->emergency(['fail_url' => $request->all()]);
    //     echo '<h3>Failed</h3>';
    // }

    // public function PaymentStatus(Request $request)
    // {
    //     $transactionId = $request->transaction_id;
    //     $transaction_table = DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();

    //     // Fallback: if callback hasn't arrived yet, verify directly with SumUp
    //     if (!empty($transaction_table) && $transaction_table->request_status == 1 && !empty($transaction_table->reference_id)) {
    //         $paymentConfig = $this->getSumUpConfig($transaction_table->merchant_id);
    //         $checkout = $this->verifyCheckout($transaction_table->reference_id, $paymentConfig);

    //         if (isset($checkout['status']) && $checkout['status'] == 'PAID' && (float) $checkout['amount'] == (float) $transaction_table->amount) {
    //             DB::table('transactions')->where('payment_transaction_id', $transactionId)->update([
    //                 'request_status' => 2,
    //                 'payment_transaction' => json_encode($checkout),
    //                 'status_message' => 'SUCCESS',
    //                 'updated_at' => date('Y-m-d H:i:s'),
    //             ]);
    //             $transaction_table->request_status = 2;
    //         } elseif (isset($checkout['status']) && in_array($checkout['status'], ['FAILED', 'EXPIRED'])) {
    //             DB::table('transactions')->where('payment_transaction_id', $transactionId)->update([
    //                 'request_status' => 3,
    //                 'payment_transaction' => json_encode($checkout),
    //                 'status_message' => 'FAIL',
    //                 'updated_at' => date('Y-m-d H:i:s'),
    //             ]);
    //             $transaction_table->request_status = 3;
    //         }
    //     }

    //     $payment_status = $transaction_table->request_status == 2 ? true : false;
    //     if ($transaction_table->request_status == 1) {
    //         $request_status_text = "processing";
    //         $transaction_status = 1;
    //     } else if ($transaction_table->request_status == 2) {
    //         $request_status_text = "success";
    //         $transaction_status = 2;
    //     } else {
    //         $request_status_text = "failed";
    //         $transaction_status = 3;
    //     }
    //     return ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
    // }

    //new code
    protected string $baseUrl = 'https://api.sumup.com/v0.1';

    public function getSumUpConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'SUMUP')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    /* ==================================================================
     | CURL HELPER
     |================================================================== */
    public function sumupRequest(string $method, string $path, ?array $body = null,$apiKey): array
    {
        $url = $this->baseUrl . $path;
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Log::error('SumUp curl error', ['url' => $url, 'error' => $curlError]);
            return ['status' => 0, 'body' => ['message' => $curlError]];
        }

        $decoded = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            Log::error('SumUp API error', ['url' => $url, 'status' => $httpCode, 'body' => $decoded]);
        }

        return ['status' => $httpCode, 'body' => $decoded];
    }


    /* ==================================================================
     | STEP 1 — Create (or reuse) a SumUp customer for the user
     |================================================================== */
    // public function createCustomer(Request $request)
    // {
    //     $user = $request->user('api');

    //     if ($user->pin_payment_customer_token) {
    //         return response()->json(['customer_id' => $user->pin_payment_customer_token]);
    //     }

    //     $sumupCustomerId = 'USER-' . $user->id;

    //     $result = $this->sumupRequest('POST', '/customers', [
    //         'customer_id' => $sumupCustomerId,
    //         'personal_details' => [
    //             'first_name' => $user->first_name,
    //             'last_name'  => $user->last_name,
    //             'email'      => $user->email,
    //             'phone'      => $user->phone,
    //         ],
    //     ]);

    //     if ($result['status'] >= 400) {
    //         return response()->json(['message' => 'Could not create SumUp customer', 'error' => $result['body']], 422);
    //     }

    //     $user->update(['pin_payment_customer_token' => $sumupCustomerId]);

    //     return response()->json(['customer_id' => $sumupCustomerId]);
    // }

    /* ==================================================================
     | STEP 2 — Create the tokenization checkout + log a transactions row
     |================================================================== */
    public function createSaveCardCheckout(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'calling_from' => 'required',
            'amount' => 'required',
            'currency'=>"required"
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        $calling_from = $request->calling_from;
        
        $status = 3;
        if ($calling_from == "DRIVER") {
            $user = $request->user('api-driver');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
            $status = 2;
        } elseif ($calling_from == "USER") {
            $user = $request->user('api');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
            $status = 1;
        }

        
        $currency = $request->currency;

        if (! $user->pin_payment_customer_token) {
            return response()->json(['message' => 'Customer not created yet'], 422);
        }

        $paymentOption = $this->getSumUpConfig($merchant_id);
        $apiKey = $paymentOption->api_secret_key;
        $domainData = request()->getHost();
        $parts = explode('.', $domainData);
        $domain = count($parts) >= 2 ? $parts[count($parts) - 2] : $domainData;
        $transactionId = 'SUMUP-' . strtoupper(Str::random(12));

        $result = $this->sumupRequest('POST', '/checkouts', [
            'checkout_reference' => $transactionId,
            'amount'             => 1,
            'currency'           => $currency,
            'merchant_code'      => $paymentOption->auth_token,
            'description'        => 'Save card for future payments',
            'customer_id'        => $user->pin_payment_customer_token,
            'purpose'            => 'SETUP_RECURRING_PAYMENT',
             'redirect_url'       => route('sumup.result', ['reference' => $transactionId]),
        ],$apiKey);

        if ($result['status'] >= 400) {
            return response()->json(['message' => 'Could not create checkout', 'error' => $result['body']], 422);
        }

        DB::table('transactions')->insert([
            'user_id'                => $calling_from === 'USER' ? $id : null,
            'driver_id'              => $calling_from === 'DRIVER' ? $id : null,
            'status'                 => $status,
            'merchant_id'            => $merchant_id,
            'payment_mode'           => 'SAVE_CARD',
            'checkout_id'            => $result['body']['id'],   // SumUp's checkout id
            'reference_id'           => $transactionId,          // our own generated reference
            'payment_transaction_id' => $transactionId,
            'amount'                 => 1,
            'payment_option_id'      => $paymentOption->payment_option_id,
            'request_status'         => 1,
            'status_message'         => 'PENDING',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        $data = [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('sumup.pay',['checkoutId' => $result['body']['id']]),
                'transaction_id' => $transactionId,
                'success_url' => route('sumup-success'),
                'fail_url' => route('sumup-fail'),
                'deeplink_success' => $domain . '://sumup-payment-result?status=success',
                'deeplink_fail'    => $domain . '://sumup-payment-result?status=fail',
            ];
            
            return $this->successResponse('data fetched',$data);
    }

    /* ==================================================================
     | STEP 3 — Hosted pay page the WebView loads
     |================================================================== */
    
    public function payView(Request $request, string $checkoutId)
    {
        $transaction = DB::table('transactions')->where('checkout_id', $checkoutId)->first();
        $isSaveCard = $transaction && $transaction->payment_mode === 'SAVE_CARD';
    
        return view('payment.sumup.checkout', [
            'checkoutId'    => $checkoutId,
            'enableWallets' => ! $isSaveCard,
        ]);
    }

    protected function storeCardFromCheckout(int $userId, array $checkout, array $displayFields = []): void
    {
        $mandateStatus = ($checkout['mandate']['status'] ?? null) === 'active' ? 1 : 0;

        UserCard::updateOrCreate(
            ['user_id' => $userId, 'token' => $checkout['payment_instrument']['token']],
            [
                'card_id'     => $checkout['id'],                       // SumUp checkout id
                'user_token'  => $checkout['customer_id'] ?? null,      // SumUp customer id
                'card_holder' => $displayFields['card_holder'] ?? null,
                'card_number' => $displayFields['card_number'] ?? null, // last 4 digits only
                'card_type'   => $displayFields['card_type'] ?? null,
                'exp_month'   => $displayFields['exp_month'] ?? null,
                'exp_year'    => $displayFields['exp_year'] ?? null,
                'status'      => $mandateStatus,
                'card_delete' => NULL,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]
        );
    }

    /* ==================================================================
     | STEP 5 — Charge a SAVED card for a real order
     |================================================================== */
    public function chargeSavedCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'card_id'      => 'required',
            'calling_from' => 'required',
            'currency'     => 'required',
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
    
        $callingFrom = $request->calling_from;
    
        if ($callingFrom === 'DRIVER') {
            $driver = $request->user('api-driver');
            $id = $driver->id;
            $merchantId = $driver->merchant_id;
        } elseif ($callingFrom === 'USER') {
            $user = $request->user('api');
            $id = $user->id;
            $merchantId = $user->merchant_id;
        } else {
            return $this->failedResponse('Invalid calling_from');
        }
    
        $result = $this->chargeCard([
            'amount'       => $request->amount,
            'card_id'      => $request->card_id,
            'currency'     => $request->currency,
            'merchant_id'  => $merchantId,
            'calling_from' => $callingFrom,
            'id'           => $id,
        ]);
    
        return $result['success']
            ? $this->successResponse('data fetched', $result)
            : $this->failedResponse($result['message'] ?? $result);
    }
    
    public function chargeCard(array $params)
    {
        $card = $params['calling_from'] === 'DRIVER'
            ? DriverCard::where('id', $params['card_id'])->whereNull('card_delete')->first()
            : UserCard::where('id', $params['card_id'])->whereNull('card_delete')->first();
    
        $customerId = $params['calling_from'] === 'DRIVER'
            ? ($card->driver_token ?? null)
            : ($card->user_token ?? null);
    
        if (! $card || ! $card->token) {
            return ['success' => false, 'message' => 'No saved card found for this user'];
        }
    
        $paymentOption = $this->getSumUpConfig($params['merchant_id']);
        $apiKey = $paymentOption->api_secret_key;
        $transactionId = 'SUMUP-' . strtoupper(Str::random(12));
    
        $checkoutResult = $this->sumupRequest('POST', '/checkouts', [
            'checkout_reference' => $transactionId,
            'amount'             => $params['amount'],
            'currency'           => $params['currency'],
            'merchant_code'      => $paymentOption->auth_token,
            'description'        => 'Order payment',
            'customer_id'        => $customerId,
            'redirect_url'       => route('sumup.result', ['reference' => $transactionId]),
        ], $apiKey);
    
        if ($checkoutResult['status'] >= 400) {
            return ['success' => false, 'message' => 'Could not create order checkout', 'error' => $checkoutResult['body']];
        }
    
        $checkoutId = $checkoutResult['body']['id'];
    
        DB::table('transactions')->insert([
            'user_id'                => $params['calling_from'] === 'USER' ? $params['id'] : null,
            'driver_id'              => $params['calling_from'] === 'DRIVER' ? $params['id'] : null,
            'status'                 => 1,
            'merchant_id'            => $params['merchant_id'],
            'payment_mode'           => 'ORDER_PAYMENT',
            'checkout_id'            => $checkoutId,
            'reference_id'           => $transactionId,
            'payment_transaction_id' => $transactionId,
            'card_id'                => $card->id,
            'amount'                 => $params['amount'],
            'payment_option_id'      => $paymentOption->payment_option_id,
            'request_status'         => 1,
            'status_message'         => 'PENDING',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
    
        $processResult = $this->sumupRequest('PUT', "/checkouts/{$checkoutId}", [
            'payment_type' => 'card',
            'token'        => $card->token,
            'customer_id'  => $customerId,
            'mandate'      => ['type' => 'recurrent'],
        ], $apiKey);
    
        $result = $processResult['body'];
    
        if (isset($result['next_step']['url'])) {
            return [
                'success'     => true,
                'status'      => 'CHALLENGE_REQUIRED',
                'checkout_id' => $checkoutId,
                'reference'   => $transactionId,
                'next_step'   => $result['next_step'],
            ];
        }
    
        $finalStatus = $result['status'] ?? null;
    
        DB::table('transactions')
            ->where('checkout_id', $checkoutId)
            ->update([
                'request_status' => $finalStatus === 'PAID' ? 2 : 3,
                'status_message' => $finalStatus ?? 'FAILED',
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
    
        if ($processResult['status'] >= 400 || $finalStatus === 'FAILED') {
            return ['success' => false, 'status' => $finalStatus, 'message' => $result];
        }
    
        return [
            'success'     => true,
            'status'      => $finalStatus,
            'checkout_id' => $checkoutId,
            'reference'   => $transactionId,
            'transaction' => $result['transactions'][0] ?? null,
        ];
    }

    
    
    public function paymentResult(Request $request, string $reference)
    {
        \Log::channel('sumup_pay')->emergency(['sumup_callback_url' => $request->all(),'ref_id'=>$reference]);
        $transaction = DB::table('transactions')->where('reference_id', $reference)->first();
        
        if (! $transaction) {
            Log::warning('SumUp result: no matching transaction', ['reference' => $reference]);
            return view('payment.sumup.result', ['status' => 'unknown']);
        }
        $paymentOption = $this->getSumUpConfig($transaction->merchant_id);
        $apiKey = $paymentOption->api_secret_key;
 
        // SumUp appends ?checkout_id=... to redirect_url automatically —
        // prefer that; fall back to what we stored at creation time.
        $checkoutId = $request->query('checkout_id') ?: $transaction->checkout_id;
 
        $result   = $this->sumupRequest('GET', "/checkouts/{$checkoutId}",NULL,$apiKey);
        $checkout = $result['body'] ?? [];
        $status   = $checkout['status'] ?? $transaction->status_message;
 
        $userId = $transaction->user_id ?? $transaction->driver_id;
        $this->resolveCheckout($checkoutId, $checkout, $userId, $transaction);
 
        if ($status === 'PAID') {
            return redirect()->route('sumup-success', [
                'reference'   => $reference,
                'checkout_id' => $checkoutId,
            ]);
        }
    
        return redirect()->route('sumup-fail', [
            'reference'   => $reference,
            'checkout_id' => $checkoutId,
            'status'      => $status,
        ]);
    }
    
    // public function sumupPaysuccess(Request $request)
    // {
    //     echo '<h3>Success</h3>';
    // }

    // public function sumupPayFail(Request $request)
    // {
    //     echo '<h3>Fail</h3>';
    // }
    public function sumupPaysuccess(Request $request)
    {
        \Log::channel('sumup_pay')->emergency(['success_url' => $request->all()]);
    
        $scheme = 'adminkloud';
        $deeplink = $scheme . '://sumup-payment-result?status=success&reference=' . urlencode($request->query('reference', ''));
    
        return view('payment.sumup.deeplink-redirect', ['deeplink' => $deeplink, 'status' => 'success']);
    }
    
    public function sumupPayFail(Request $request)
    {
        \Log::channel('sumup_pay')->emergency(['fail_url' => $request->all()]);
    
        $scheme = 'adminkloud';
        $deeplink = $scheme . '://sumup-payment-result?status=fail&reference=' . urlencode($request->query('reference', ''));
    
        return view('payment.sumup.deeplink-redirect', ['deeplink' => $deeplink, 'status' => 'fail']);
    }
    
    protected function resolveCheckout(string $checkoutId, array $checkout, ?int $userId, $transaction = null): void
    {
        $status = $checkout['status'] ?? null;
        $transaction = $transaction ?: DB::table('transactions')->where('checkout_id', $checkoutId)->first();
    
        if (! $transaction) {
            return;
        }
        
        $paymentOption = $this->getSumUpConfig($transaction->merchant_id);
        $apiKey = $paymentOption->api_secret_key;
    
        $requestStatus = match ($status) {
            'PAID'              => 2,
            'FAILED', 'EXPIRED' => 3,
            default             => 1,
        };
    
        DB::table('transactions')->where('checkout_id', $checkoutId)->update([
            'request_status' => $requestStatus,
            'status_message' => $status ?? 'PENDING',
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    
        $purpose = $checkout['purpose'] ?? ($transaction->payment_mode === 'SAVE_CARD' ? 'SETUP_RECURRING_PAYMENT' : null);
    
        if ($purpose === 'SETUP_RECURRING_PAYMENT' && $status === 'PAID' && isset($checkout['payment_instrument']['token']) && $userId) {
            $customerId = $checkout['customer_id'] ?? null;
            $token      = $checkout['payment_instrument']['token'];
    
            $displayFields = $this->fetchCardDisplayDetails($customerId, $token,$apiKey);
            
            // $existing = UserCard::where('user_id', $userId)
            // ->where('card_delete', 0)
            // ->where('card_number', $displayFields['last_4_digits'] ?? null)
            // ->where('card_type', $displayFields['type'] ?? null)
            // ->where('token', '!=', $token) // different token, same physical card
            // ->first();
            
            // if ($existing) {
            //     // Same card already saved under a different token.
            //     // Deactivate the newly created duplicate on SumUp's side to
            //     // avoid piling up dead tokens, and don't touch user_cards at all.
            //     $this->sumupRequest('DELETE', "/customers/{$customerId}/payment-instruments/{$token}");
        
            //     DB::table('transactions')->where('checkout_id', $checkoutId)->update([
            //         'status_message' => 'DUPLICATE_CARD',
            //         'card_id'        => $existing->id,
            //     ]);
        
            //     return;
            // }
            //check the transaction is for driver or user
            if(!empty($transaction->driver_id)){
                $card = DriverCard::updateOrCreate(
                    ['driver_id' => $userId, 'token' => $token],
                    [
                        'driver_token'        => $customerId,
                        'card_holder'       => $displayFields['name'] ?? null,
                        'card_number'       => $displayFields['last_4_digits'] ?? null,
                        'card_type'         => $displayFields['type'] ?? null,
                        'card_delete'       => NULL,
                        'payment_option_id' => $transaction->payment_option_id,
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ]
                );
            }else{
                $card = UserCard::updateOrCreate(
                    ['user_id' => $userId, 'token' => $token],
                    [
                        'user_token'        => $customerId,
                        'card_holder'       => $displayFields['name'] ?? null,
                        'card_number'       => $displayFields['last_4_digits'] ?? null,
                        'card_type'         => $displayFields['type'] ?? null,
                        'status'            => ($checkout['mandate']['status'] ?? null) === 'active' ? 1 : 0,
                        'card_delete'       => NULL,
                        'payment_option_id' => $transaction->payment_option_id,
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ]
                );
            }
            
    
            DB::table('transactions')->where('checkout_id', $checkoutId)->update(['card_id' => $card->id]);
        }
    }

    protected function fetchCardDisplayDetails(?string $customerId, string $token,$apiKey): array
    {
        if (! $customerId) {
            return [];
        }
    
        $result = $this->sumupRequest('GET', "/customers/{$customerId}/payment-instruments",NULL,$apiKey);
    
        if ($result['status'] >= 400) {
            return [];
        }
    
        foreach ($result['body'] as $instrument) {
            if (($instrument['token'] ?? null) === $token) {
                return $instrument['card'] ?? [];
            }
        }
    
        return [];
    }
}
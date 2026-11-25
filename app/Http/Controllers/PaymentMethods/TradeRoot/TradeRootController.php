<?php

namespace App\Http\Controllers\PaymentMethods\TradeRoot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;

class TradeRootController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    private function getWalletId(int $userId): string
    {
        return $this->uuidv5(
            '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'minzolor-wallet-' . $userId
        );
    }

    private function generateAssuranceData(
        string $aesKey,
        string $walletId,
        string $sessionId,
        ?string $signature = null
    ): string {
        $data = [
            'timestamp' => (int) round(microtime(true) * 1000),
            'digitalWalletId' => $walletId,
        ];

        if ($signature !== null) {
            $data['signature'] = $signature;   // required for the Immediate Payment flow
        }

        $data['sessionId'] = $sessionId;       // kept last so order matches the spec

        $payload = json_encode($data);

        $iv = random_bytes(16);

        $ciphertext = openssl_encrypt($payload, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \Exception('openssl_encrypt failed: ' . openssl_error_string());
        }

        return base64_encode($iv . $ciphertext);   // IV first
    }


    public function makePaymentUsingTradeRoot($request, $paymentConfig, $calling_from)
    {
        try {
            if ($request->calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $merchant_id = $driver->merchant_id;
            } elseif ($request->calling_from == "USER") {
                $user = $request->user('api');
                $merchant_id = $user->merchant_id;
            }
            $string_file = $this->getStringFile($merchant_id);

            // e-Commerce "Immediate Payment" flow (NOT the old /initiateissuetoken)
            $url = 'https://securepay.travelbuy.co.za/traderootecommercenedbankapi/initiateimmediatepayment';

            $tokenRequesterKey = $paymentConfig->api_secret_key;   // dashed UUID — used for the SIGNATURE
            $merchantId = $paymentConfig->api_public_key;   // e.g. PF7750002000008

            $aesKey = str_replace('-', '', $tokenRequesterKey);    // 32 chars — used for AES only
            if (strlen($aesKey) !== 32) {
                throw new \Exception('AES key must be 32 bytes. Current length: ' . strlen($aesKey));
            }

            $datetime = now()->utc()->format('Y-m-d\TH:i:sO');
            $sessionId = (string) Str::uuid();
            $currencyCode = $request->currencyCode ?? '710';
            $transactionAmount = (int) round($request->amount * 100);   // cents

            // RRN — mandatory for this gateway; reused verbatim in the signature
            $rrn = substr((string) time(), -6) . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $rrnExt = 'QS' . $rrn;

            $walletId = $this->getWalletId(auth()->id());

            // Signature: DASHED key, fields in this exact order, whole string lowercased, SHA-512, then uppercased
            $signature = strtoupper(hash('sha512', strtolower(
                $merchantId . $transactionAmount . $currencyCode . $rrn . $rrnExt . $sessionId . $tokenRequesterKey
            )));

            $assuranceData = $this->generateAssuranceData(
                $aesKey,
                $walletId,
                $sessionId,
                $signature
            );

            $payload = [
                'transactionAmount' => $transactionAmount,
                'transmissionDateTime' => $datetime,
                'merchantId' => $merchantId,
                'currencyCode' => $currencyCode,
                'sessionId' => $sessionId,
                'retrievalReferenceNumber' => $rrn,
                'retrievalReferenceNumberExtended' => $rrnExt,
                'assuranceData' => $assuranceData,
                'transactionInfo' => [
                    'transactionCategory' => '95',   // 3-D Secure
                ],
                'peripheryData' => [
                    'callbackUrl' => route('traderoot-callback'),
                    'notificationUrl' => route('traderoot-callback'), // TODO: dedicated S2S URL — trust THIS for settlement
                ],
            ];

            Log::channel('traderoot_pay')->info(['immediate_payment_request' => $payload]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $res = curl_exec($ch);

            if (curl_errno($ch)) {
                return ['success' => false, 'message' => curl_error($ch)];
            }
            curl_close($ch);

            $response = json_decode($res, true);
            if (!is_array($response)) {
                throw new \Exception('Invalid response from Traderoot');
            }

            if (isset($response['responseCode']) && $response['responseCode'] == '00') {
                DB::beginTransaction();

                $id = auth()->id();
                $merchant_id = $paymentConfig->merchant_id ?? null;

                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : null,
                    'driver_id' => $calling_from == "DRIVER" ? $id : null,
                    'status' => 'PENDING',
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $sessionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $response['peripheryData']['initiationUrl'] ?? '',
                    'transaction_id' => $sessionId ?? '',
                    'success_url' => route('traderoot-success'),
                    'fail_url' => route('traderoot-fail'),
                    'redirect_url' => route('traderoot-redirect'),
                ];
            }

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TradeRoot Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function TradeRootCallBack(Request $request)
    {
        try {

            $data = $request->all();

            $decoded = json_decode(
                base64_decode($data['data']),
                true
            );

          Log::channel('traderoot_pay')->info(['callback_data' => $decoded]);

            if (($decoded['responseCode'] ?? null) === '00') {
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $decoded['sessionId']])
                    ->update([
                        'request_status' => 2,

                        'payment_transaction' => json_encode($request->all()),
                        'reference_id' => $decoded['echoData'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'SUCCESS'
                    ]);
                return redirect()->route('traderoot-success');

            } else {
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $decoded['sessionId']])
                    ->update([
                        'request_status' => 3,
                        'card_is_active' => 2,
                        'payment_transaction' => json_encode($request->all()),
                        'reference_id' => $decoded['echoData'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'FAIL'
                    ]);
                return redirect()->route('traderoot-fail');
                // return $decoded['responseMessage'] ?? 'Payment Not Completed, Try Again';
            }

        } catch (\Exception $e) {



            return response()->json([

                'success' => false,

                'message' => $e->getMessage()
            ]);
        }
    }

    public function TradeRootPaysuccess(Request $request)
    {
        \Log::channel('traderoot_pay')->emergency(['success_url' => $request->all()]);
        echo '<h3>Success</h3>';
    }

    public function TradeRootPayFail(Request $request)
    {
        \Log::channel('traderoot_pay')->emergency(['fail_url' => $request->all()]);
        echo '<h3>Failed</h3>';
    }

    public function TradeRootPayRedirect(Request $request)
    {
        \Log::channel('traderoot_pay')->emergency(['redirect_url' => $request->all()]);
        echo '<h3>Redirecting...</h3>';
    }

    private function uuidv5(
        string $namespace,
        string $name
    ): string {

        $nhex = str_replace(
            ['-', '{', '}'],
            '',
            $namespace
        );

        $nstr = '';

        for ($i = 0; $i < strlen($nhex); $i += 2) {

            $nstr .= chr(
                hexdec(
                    $nhex[$i] . $nhex[$i + 1]
                )
            );
        }

        $hash = sha1($nstr . $name);

        return sprintf(

            '%08s-%04s-%04x-%04x-%12s',

            substr($hash, 0, 8),

            substr($hash, 8, 4),

            (hexdec(substr($hash, 12, 4)) & 0x0fff)
            | 0x5000,

            (hexdec(substr($hash, 16, 4)) & 0x3fff)
            | 0x8000,

            substr($hash, 20, 12)
        );
    }


}
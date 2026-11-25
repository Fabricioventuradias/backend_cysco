<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\PaymentOptionsConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantWalletController extends Controller
{
    protected $baseUrl = "https://malipo-wallet-api.ubx.co.tz/api/v1/wallet";
    protected $iss = "https://msprojects.apporioproducts.com/multi-service-v3/public/api";
    protected $ubxFeeRate = 0.01; // 1%

    public function index()
    {
        $merchant = Auth::guard('merchant')->user();

        $result = $this->getWalletBalance($merchant->id);

        $balance = $result['status'] == 'success' ? $result['data'] : null;
        $error = $result['status'] == 'error' ? $result['message'] : null;

        return view('merchant.wallet.settlement', compact('balance', 'error'));
    }

public function settle(Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    $merchant = Auth::guard('merchant')->user();
    $amount = (float) $request->input('amount');

    // Check against current wallet balance before calling settlement API
    $balanceResult = $this->getWalletBalance($merchant->id);

    if ($balanceResult['status'] != 'success') {
        return redirect()->route('merchant.wallet.index')
            ->with('error', 'Unable to verify wallet balance. Please try again.');
    }

    $currentBalance = (float) $balanceResult['data']['wallet_balance'];

    if ($amount > $currentBalance) {
        return redirect()->route('merchant.wallet.index')
            ->with('error', 'Withdrawal amount cannot exceed your available wallet balance.');
    }

    $result = $this->settleWalletPayment($merchant->id, $amount);

    if ($result['status'] == 'success') {
        return redirect()->route('merchant.wallet.index')->with(
            'success',
            'Settlement submitted successfully. Processing ID: ' . $result['data']['settlement_processing_id']
        );
    }

    return redirect()->route('merchant.wallet.index')
        ->with('error', $result['message']);
}

    public function walletSettlementUpdate(Request $request)
    {
        Log::channel('xrpay')->emergency(['callback_url_settlement' => $request->all()]);

        $data = $request->all();
        $payment_request_id = $data['payment_request_id'] ?? null;

        if (!$payment_request_id) {
            return response("Invalid request", 400);
        }

        $transaction = DB::table('transactions')
            ->where('payment_transaction_id', $payment_request_id)
            ->first();

        if (!$transaction) {
            return response("Transaction not found", 404);
        }

        if ($transaction->request_status == 2) {
            return response("Already processed", 200);
        }

        DB::table('transactions')
            ->where('payment_transaction_id', $payment_request_id)
            ->update([
                'request_status' => 2,
                'payment_transaction' => json_encode($data),
                'updated_at' => now(),
                'status_message' => 'SUCCESS'
            ]);

        return response()->json(['result' => 1, 'message' => 'callback received']);
    }

    /* ---------------------------------------------------------
     |  Internal helpers (private to this controller)
     * ---------------------------------------------------------*/

    private function getWalletBalance($merchant_id)
    {
        $paymentConfig = PaymentOptionsConfiguration::where('merchant_id', $merchant_id)
            ->where('payment_gateway_provider', 'XRPAY')
            ->first();

        if (!$paymentConfig || empty($paymentConfig->api_public_key)) {
            return [
                'status' => 'error',
                'message' => 'XRPAY wallet not configured for this merchant'
            ];
        }

        $wallet_number = $paymentConfig->api_public_key;
        $token = generate_self_signed_jwt($this->iss);

        $url = $this->baseUrl . "/get-balance";
        $walletRequestId = (string) time();

        $data = json_encode([
            "wallet_number" => $wallet_number,
            "wallet_request_id" => $walletRequestId
        ]);

        Log::channel('xrpay')->emergency(['get_balance_request' => $data]);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json",
                "Content-Length: " . strlen($data)
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            Log::channel('xrpay')->emergency(['get_balance_curl_error' => $error]);
            return ['status' => 'error', 'message' => $error];
        }

        curl_close($ch);

        $result = json_decode($response, true);
        Log::channel('xrpay')->emergency(['get_balance_response' => $result]);

        if (isset($result['success']) && $result['success'] == true) {
            return [
                'status' => 'success',
                'data' => $result['data']
            ];
        }

        return [
            'status' => 'error',
            'message' => $result['message'] ?? 'Unable to fetch wallet balance'
        ];
    }

private function settleWalletPayment($merchant_id, $amount)
{
    $paymentConfig = PaymentOptionsConfiguration::where('merchant_id', $merchant_id)
        ->where('payment_gateway_provider', 'XRPAY')
        ->first();

    if (!$paymentConfig || empty($paymentConfig->api_public_key)) {
        return [
            'status' => 'error',
            'message' => 'XRPAY wallet not configured for this merchant'
        ];
    }

    $wallet_number = $paymentConfig->api_public_key;
    $token = generate_self_signed_jwt($this->iss);

    $url = $this->baseUrl . "/payment";

    $paymentRequestId = (string) time();



  $ubxFee = round($amount * $this->ubxFeeRate, 2);
        $totalCollected = $amount + $ubxFee;

        $data = json_encode([
            "wallet_number" => $wallet_number,
            "payment_request_id" => $paymentRequestId,
            "payment_updates_url" => route('merchant.wallet.callback'),
            "total_collected_amount" => (string) $totalCollected,
            "total_settlement_amount" => (string) $amount,
            
            
    // UBX fee = 1% of withdrawal amount, deducted from it
    // $ubxFee = round($amount * 0.01, 2);
    // $beneficiaryAmount = $amount - $ubxFee;

    // $data = json_encode([
    //     "wallet_number" => $wallet_number,
    //     "payment_request_id" => $paymentRequestId,
    //     "payment_updates_url" => route('merchant.wallet.callback'),
    //     "total_collected_amount" => (string) $amount,
    //     "total_settlement_amount" => (string) $beneficiaryAmount,
        "beneficiaries" => [
            [
                "beneficiary_id" => "BN02",
                "beneficiary_role" => "operator",
                "beneficiary_fi_id" => "FI046",
                "beneficiary_fi_name" => "Tanzania Commercial Bank",
                "beneficiary_name" => "Hanshi",
                "beneficiary_account_number" => "300400000043",
                "beneficiary_account_name" => "Hanshi company ltd",
                "beneficiary_tin" => "193327311",
                "amount" => (string) $amount,
            ],
            [
                "beneficiary_id" => "UBX",
                "beneficiary_role" => "UBX",
                "beneficiary_fi_id" => "UBX",
                "beneficiary_fi_name" => "UBX",
                "beneficiary_name" => "UBX",
                "beneficiary_account_number" => "UBX",
                "beneficiary_account_name" => "UBX",
                "beneficiary_tin" => "UBX",
                "amount" => (string) $ubxFee,
            ],
        ],
    ]);

    Log::channel('xrpay')->emergency(['settlement_request' => $data]);
  

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "Content-Length: " . strlen($data)
        ],
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        Log::channel('xrpay')->emergency(['settlement_curl_error' => $error]);
        return ['status' => 'error', 'message' => $error];
    }

    curl_close($ch);

    $result = json_decode($response, true);
    Log::channel('xrpay')->emergency(['settlement_response' => $result]);

    if (isset($result['success']) && $result['success'] == true) {
        DB::table('transactions')->insert([
            'merchant_id' => $merchant_id,
            'payment_transaction_id' => $paymentRequestId,
            'amount' => $totalCollected,
            'payment_option_id' => $paymentConfig->payment_option_id,
            'request_status' => 1,
            'status_message' => 'PENDING',
            'payment_transaction' => json_encode($result['data']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'status' => 'success',
            'data' => $result['data'],
            'ubx_fee' => $ubxFee,
            'beneficiary_amount' => $amount,
        ];
    }

    return [
        'status' => 'error',
        'message' => $result['message'] ?? 'Settlement failed'
    ];
}
}
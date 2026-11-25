<?php

namespace App\Http\Controllers\Helper;

use App\Models\BookingTransaction;
use App\Models\ReferralFranchiseDiscount;
use App\Traits\MerchantTrait;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FranchiseReferralController extends Controller
{
    use MerchantTrait;
    const SENDER_TYPE_USER   = 'USER';
    const SENDER_TYPE_DRIVER = 'DRIVER';

    public function saveReferral($request = [])
    {
        $sender     = $request['sender_details'];
        $senderType = $request['type'] ?? self::SENDER_TYPE_USER; // default USER
        $booking_transaction=BookingTransaction::where('booking_id', $request['booking_id'])->first();
         
        // Example: commission based on type
        $percentage = $sender->type_based_commission;

        // Calculate commission amount
        $commissionAmount = ($request['amount'] * $percentage) / 100;
        $company_earning  = $request['amount'] - $commissionAmount;

        $refe = ReferralFranchiseDiscount::create([
            'sender_id'   => $sender->id,
            'type' => $senderType,
            'merchant_id' => $request['merchant_id'],
            'receiver_id' => $request['receiver_id'],
            'booking_id'  => $request['booking_id'],
            'amount'      => $commissionAmount,
        ]);
        if ($senderType === self::SENDER_TYPE_DRIVER) {
            $update_data['franchise_driver_earning'] = $commissionAmount;
            $company_earning= $company_earning-$booking_transaction->franchise_earning;
        }else{
            $update_data['franchise_earning'] = $commissionAmount;
        }
        
        $update_data['company_earning']   = $company_earning;
        $booking_transaction->update($update_data);

        $paramArray = array(
            'user_id'        => $sender->id,
            'booking_id'     => $request['booking_id'],
            'amount'         => $commissionAmount,
            'narration'      => 16,
            'platform'       => 2,
            'payment_method' => 3,
            'transaction_id' => NULL,
            'receiver_id'    => $request['receiver_id'],
        );
        $paramArrayDriver = array(
            'merchant_id'   => $request['merchant_id'],
            'driver_id'      => $request['receiver_id'],
            'booking_id'     => $request['booking_id'],
            'amount'         => $commissionAmount,
            'narration'      => 16,
            'platform'       => 2,
            'payment_method' => 3,
        );
        
        // throw new \Exception(json_encode($request['merchant_id']));
        // Route to the correct wallet credit method based on sender type.
        // Verify "DriverWalletCredit" actually exists on your WalletTransaction class.
        if ($senderType === self::SENDER_TYPE_DRIVER) {
            WalletTransaction::WalletCredit($paramArrayDriver);
        } else {
            WalletTransaction::UserWalletCredit($paramArray);
        }
        
    }
   
}
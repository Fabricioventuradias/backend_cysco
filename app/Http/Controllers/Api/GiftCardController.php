<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;

class GiftCardController extends Controller
{

    use ApiResponseTrait, MerchantTrait;
    /**
     * Purchase Gift Card
     */
    public function purchaseGiftCard(Request $request)
    {

        $user = $request->user();


        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'receiver_id' => 'required|exists:users,id',
            'send_at' => 'nullable|date|after:now',
            'sender_email' => 'nullable|email',
            'sender_message' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $paramArray = array(
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'narration' => 30,
                'platform' => 2,
                'payment_method' => 3,
                'notification_type' => 'WALLET_SEND_GIFTCARD',
                'sender_email' =>   !empty($validated['sender_email']),
                'sender_message' =>  !empty($validated['sender_message'])
            );

            WalletTransaction::UserWalletDebit($paramArray);

            // 2. Create gift card
            $giftCard = GiftCard::create([
                'merchant_id' => $user->merchant_id,
                'code' => strtoupper(Str::random(12)),
                'amount' => $validated['amount'],
                'remaining_amount' => $validated['amount'],
                'sender_id' => $user->id,
                'receiver_id' => $validated['receiver_id'],
                'status' => 'pending',
                'is_scheduled' => !empty($validated['send_at']),
                'send_at' => $validated['send_at'] ?? null,
                'sender_email' => !empty($validated['sender_email']),
                'sender_message' => !empty($validated['sender_message'])
            ]);


            if (!$giftCard->is_scheduled) {
                $this->processGiftCard($giftCard);
                DB::commit();
                return $this->successResponse('Gift card created and sent successfully', $giftCard);
            }

            DB::commit();
            return $this->successResponse('Gift card schuduled successfully', $giftCard);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->failedResponse("Failed to create gift card");
        }
    }
    public function processGiftCard(GiftCard $giftCard)
    {
        try {
            if ($giftCard->status !== 'pending') {
                return;
            }

            // Credit receiver wallet
            $paramArray = array(
                'sender_id' => $giftCard->sender_id,
                'user_id' => $giftCard->receiver_id,
                'amount' => $giftCard->remaining_amount,
                'narration' => 31,
                'platform' => 2,
                'payment_method' => 3,
                'receipt' => 'test',
                'notification_type' => 'WALLET_RECEIVE_GIFTCARD',
                'sender_email' => $giftCard->sender_email,
                'sender_message' => $giftCard->sender_message
            );

            WalletTransaction::UserWalletCredit($paramArray);

            // Update gift card
            $giftCard->update([
                'status' => 'sent',
                'remaining_amount' => 0,
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::channel('onesignal')->emergency($e->getMessage());
        }
    }
    public function giftCardHistory(Request $request)
    {
        try {

            $user = $request->user();

            $giftCards = GiftCard::with([
                'sender:id,first_name,email',
                'receiver:id,first_name,email'
            ])
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('receiver_id', $user->id);
                })
                ->latest()
                ->paginate(10);

            $giftCards->getCollection()->transform(function ($giftCard) use ($user) {

            $giftCard->order_status = ($giftCard->receiver_id == $user->id)
                ? 'received'
                : 'send';

            return $giftCard;
        });

            return $this->successResponse(
                'Gift card history fetched successfully',
                [
                    'data' => $giftCards->items(),

                    'pagination' => [
                        'current_page' => $giftCards->currentPage(),
                        'next_page' => $giftCards->nextPageUrl(),
                        'previous_page' => $giftCards->previousPageUrl(),
                        'per_page' => $giftCards->perPage(),
                        'total' => $giftCards->total(),
                    ]
                ]
            );

        } catch (\Throwable $e) {


            return $this->failedResponse('Failed to fetch gift card history');
        }
    }
}
<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\EphemeralKey;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Webhook;
use Exception;
use App\Traits\MerchantTrait;
use App\Traits\ApiResponseTrait;
use App\Models\PaymentOptionsConfiguration;

class StripeSdkController extends Controller
{
    use MerchantTrait,ApiResponseTrait;
    
    private function setStripeKey($merchantId,$paymentOptionId)
    {
        $config = PaymentOptionsConfiguration::where('merchant_id', $merchantId)->where('payment_option_id',$paymentOptionId)->first();
    
        if (!$config) {
            throw new \Exception("Stripe config not found");
        }
    
        \Stripe\Stripe::setApiKey($config->api_secret_key);
    }
    
    public function createCustomer(Request $request){
        $request->validate([
            'email' => 'required|email',
            'name'  => 'required|string',
            'platform'=> 'required',
            'payment_option_id'=>'required'
        ]);

        try {
            $user = $request->user('api'); 
            $paymentOptionId = $request->payment_option_id;
            $this->setStripeKey($user->merchant_id,$paymentOptionId);
            $platform = $request->platform;
            // Create Stripe customer
            $customer = Customer::create([
                'email'    => $request->email,
                'name'     => $request->name,
                'phone'    => NULL,
                'metadata' => [
                    'user_id'    => $user->id,
                    'app_source' => $platform == 1 ? 'ANDROID' : "IOS",
                ],
            ]);
            
            // Create ephemeral key for this customer
            $ephemeralKey = EphemeralKey::create(
                ['customer' => $customer->id],
                ['stripe_version' => '2023-10-16']
            );


            // Save stripe customer ID to your users table
            if ($user) {
                $user->update(['pin_payment_customer_token' => $customer->id]);
            }

            return $this->successResponse('Customer created successfully',["customer_id"=>$customer->id,'ephemeralKey' => $ephemeralKey->secret]);

        } catch (Exception $e) {
            Log::error('Stripe createCustomer error: ' . $e->getMessage());
            return response()->json([
                'result'  => '0',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate([
            'amount'          => 'required|integer|min:1',
            'currency'        => 'required|string|in:inr,usd,gbp,eur,aed,sgd',
            'customer_id'      => 'required|string',
            'booking_id'       => 'nullable|string',
            'description'     => 'nullable|string',
            'platform'=> 'required',
            'payment_option_id'=>'required'
        ]);

        try {
            $user = $request->user('api');
            $platform = $request->platform;
            $paymentOptionId = $request->payment_option_id;
            $this->setStripeKey($user->merchant_id,$paymentOptionId);
            // Create Payment Intent
            $paymentIntentData = [
                'amount'                      => $request->amount,
                'currency'                    => $request->currency,
                'customer'                    => $request->customer_id,
                'capture_method'              => 'manual',
                // 'setup_future_usage'          => 'off_session',
                'automatic_payment_methods'   => ['enabled' => true], // shows all methods from dashboard
                'description'                 => $request->description ?? 'App Payment',
                'metadata'                    => [
                    'booking_id'   => $request->booking_id ?? '',
                    'capture_mode' => 'manual',
                    'app_source'   => $platform == 1 ? 'ANDROID' : "IOS",
                ],
            ];

            $paymentIntent = PaymentIntent::create($paymentIntentData);
            
            $ephemeralKey = EphemeralKey::create(
                ['customer' => $request->customer_id],
                ['stripe_version' => '2023-10-16']
            );

            if(!empty($request->booking_id)){
                $booking = Booking::where('id',$request->booking_id)->update(['payment_intent_id' => $paymentIntent->id]);
            }
            $data = [
                'paymentIntentId' => $paymentIntent->id,
                'customerId'      => $request->customer_id,
                'clientSecret'    => $paymentIntent->client_secret,
                'ephemeralKey'    => $ephemeralKey->secret,
            ];
            return $this->successResponse('Payment intent created',$data);

        } catch (Exception $e) {
            Log::error('Stripe createPaymentIntent error: ' . $e->getMessage());
            return response()->json([
                'result'  => '0',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function createSetupIntent(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|string',
            'platform'=>'required',
            'payment_option_id'=>'required'
        ]);

        try {
            $user = $request->user('api');
            $platform = $request->platform;
            $paymentOptionId = $request->payment_option_id;
            $this->setStripeKey($user->merchant_id,$paymentOptionId);
            $setupIntent = SetupIntent::create([
                'customer'             => $request->customer_id,
                // 'usage'                => 'off_session',
                'metadata'             => [
                    'app_source' => $platform == 1 ? 'ANDROID' : "IOS",
                ],
                'automatic_payment_methods' => [
                    'enabled'           => true,
                    'allow_redirects'   => 'never',
                ],
            ]);

            $ephemeralKey = EphemeralKey::create(
                ['customer' => $request->customer_id],
                ['stripe_version' => '2023-10-16']
            );
            
            $data = [
                'customerId'   => $request->customer_id,
                'ephemeralKey'    => $ephemeralKey->secret,
                'clientSecret'    => $setupIntent->client_secret,
            ];
            return $this->successResponse('Setup intent created',$data);

        } catch (Exception $e) {
            Log::error('Stripe createSetupIntent error: ' . $e->getMessage());
            return response()->json([
                'result'  => '0',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function capturePayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'amount'          => 'nullable|integer|min:1', // for partial capture
            'payment_option_id'=>'required'
        ]);

        try {
            $user = $request->user('api');
            $paymentOptionId = $request->payment_option_id;
            $this->setStripeKey($user->merchant_id,$paymentOptionId);
            
            $captureData = [];

            // Partial capture (optional — capture less than authorized amount)
            if ($request->has('amount')) {
                $captureData['amount_to_capture'] = $request->amount;
            }

            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            // Verify it's in the right state
            if ($paymentIntent->status !== 'requires_capture') {
                return response()->json([
                    'result'  => '0',
                    'message' => 'Payment intent is not in requires_capture state. Current status: ' . $paymentIntent->status,
                ], 400);
            }

            $captured = $paymentIntent->capture($captureData);

            // Update your database
            // StripePayment::where('payment_intent_id', $request->paymentIntentId)
            //     ->update(['status' => $captured->status]);

            if($captured->status === 'succeeded'){
                return $this->successResponse('Payment captured successfully',[
                    'status'          => $captured->status,
                    'paymentIntentId' => $captured->id,
                    'amountCaptured'  => $captured->amount_received,
                ]);
            }
            return $this->failedResponse('Capture failed');

        } catch (Exception $e) {
            Log::error('Stripe capturePayment error: ' . $e->getMessage());
            return response()->json([
                'result'  => '0',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    public function getPaymentMethod(Request $request)
    {
        $user            = $request->user('api');
        $paymentOptionId = $request->payment_option_id;
        $this->setStripeKey($user->merchant_id, $paymentOptionId);
        $customerId      = $request->customer_id;
    
        try {
            $setupIntents = \Stripe\SetupIntent::all([
                'customer' => $customerId,
                'limit'    => 20,
            ]);
    
            $allMethods  = [];
            $seenPmIds   = []; // dedupe by pm ID
            $seenTypes   = []; // ✅ dedupe by type — prevents duplicate Link/Revolut
    
            foreach ($setupIntents->data as $si) {
                if ($si->status === 'succeeded' && $si->payment_method) {
    
                    if (in_array($si->payment_method, $seenPmIds)) {
                        continue; // skip same pm_xxx
                    }
    
                    try {
                        $pm = \Stripe\PaymentMethod::retrieve($si->payment_method);
    
                        // ✅ Skip if we already have this TYPE
                        // e.g. only show ONE Link entry even if 5 Link pm_xxx exist
                        if (in_array($pm->type, $seenTypes)) {
                            \Log::info('Skipping duplicate type: ' . $pm->type);
                            continue;
                        }
    
                        $seenPmIds[] = $si->payment_method;
                        $seenTypes[] = $pm->type;
                        $allMethods[] = $this->formatPaymentMethod($pm);
    
                    } catch (\Exception $e) {
                        \Log::warning('Could not retrieve PM: ' . $e->getMessage());
                    }
                }
            }
    
            if (!empty($allMethods)) {
                return $this->successResponse('Payment Method Fetched Successfully', $allMethods);
            }
    
            return response()->json([
                'result'  => '0',
                'message' => 'No payment method found'
            ]);
    
        } catch (\Exception $e) {
            \Log::error('getPaymentMethod error: ' . $e->getMessage());
            return response()->json([
                'result'  => '0',
                'message' => $e->getMessage()
            ]);
        }
    }
 
    private function formatPaymentMethod(\Stripe\PaymentMethod $pm): array
    {
        $data = [
            'payment_method_id' => $pm->id,
            'type'              => $pm->type,
            'brand'             => '',
            'last4'             => '',
        ];
     
        switch ($pm->type) {
            case 'card':
                $data['brand'] = $pm->card->brand ?? '';
                $data['last4'] = $pm->card->last4 ?? '';
                break;
            case 'link':
                $data['brand'] = 'Link';
                $data['last4'] = $pm->link->email ?? '';
                break;
            case 'revolut_pay':
                $data['brand'] = 'Revolut Pay';
                break;
            case 'amazon_pay':
                $data['brand'] = 'Amazon Pay';
                break;
            case 'apple_pay':
                $data['brand'] = 'Apple Pay';
                break;
            default:
                $data['brand'] = ucfirst(str_replace('_', ' ', $pm->type));
                break;
        }
     
        return $data;
    }
    
    //booking accept reject 
    public function createPaymentIntentDuringRide($booking){
        try{
            $this->setStripeKey($booking->merchant_id,$booking->payment_option_id);
            $customerId = $booking->User->pin_payment_customer_token;
            $res = PaymentIntent::create([
                'amount'               => (int)($booking->estimate_bill * 100),
                'currency'             => $booking->CountryArea->Country->isoCode,
                'customer'             => $customerId,
                'payment_method'       => $booking->BookingDetail->stripe_payment_method_id,
                'capture_method'       => 'manual',
                'confirm'              => true,        
                // 'off_session'          => true,
                'automatic_payment_methods' => [
                    'enabled'           => true,
                    'allow_redirects'   => 'never',
                ],
            ]);
            
            return [
                'status' => true,
                'pi' => $res->id
            ];
        } catch (\Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    //during booking end payment via stripe sdk (online payment)
    public function charge($merchant_id = NULL,$payment_option_config = NULL,$payment_intent_id = NULL,$amount=NULL){
        try{
            $this->setStripeKey($merchant_id,$payment_option_config->payment_option_id);
            if ($payment_intent_id != null) {
                $charge = PaymentIntent::retrieve($payment_intent_id);
                $totalAmount = (int) ($amount * 100);
                if ($charge->status === 'requires_confirmation') {
                    $charge = $charge->confirm();
                    
                    // Re-retrieve to get updated status after confirm
                    $charge = PaymentIntent::retrieve($payment_intent_id);
                }
                $chargableAmount = $charge->amount_capturable;
                
                if ($charge->status === 'requires_capture') {
                    $capturedIntent = $charge->capture([
                        'amount_to_capture' => $chargableAmount,
                    ]);
                } else {
                    return [
                        'status'  => false,
                        'message' => 'PaymentIntent not ready for capture. Status: ' . $charge->status
                    ];
                }
        
                if($totalAmount != $chargableAmount && $totalAmount >= $chargableAmount){
                   $remainingAmount = $totalAmount-$chargableAmount;
                   if ($remainingAmount > 0) {
                        $capturedIntent = PaymentIntent::create([
                            'amount' => $remainingAmount,
                            'currency' => $charge->currency,
                            'customer' => $charge->customer,
                            'payment_method' => $charge->payment_method,
                            'confirm' => true,              // immediate charge
                            // 'off_session' => true,           // no user interaction
                            'capture_method' => 'automatic', //direct charge
                            'description' => 'Extra ride charge (distance/time adjustment)',
                            'metadata' => [
                                'parent_payment_intent' => $charge->id,
                            ],
                        ]);
                    }
                }
            }
            return [
                    'status'  => true,
                    'message'=> 'Success'
            ];
        } catch (\Exception $e) {
                return [
                    'status'  => false,
                    'message' => $e->getMessage()
                ];
        }
    }
    
    //cancel intent and stripe release the amount
     public function cancelIntent($merchant_id = NULL,$payment_option_id = NULL,$booking = NULL,$cancel_charges = NULL){
        try{
            $this->setStripeKey($merchant_id,$payment_option_id);
            $paymentIntentId = $booking->payment_intent_id;
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                
            $cancellableStatuses = [
                'requires_payment_method',
                'requires_capture',
                'requires_confirmation',
                'requires_action',
                'processing',
            ];
                
            if (!in_array($paymentIntent->status, $cancellableStatuses)) {
                // return $this->failedResponse('Cannot cancel. Status is: ' . $paymentIntent->status);
                return [
                    'status'  => false,
                    'message' => 'Cannot cancel. Status is: ' . $paymentIntent->status
                ];
            }
         
            $cancelled = $paymentIntent->cancel();  //stripe release the amount
            if(!empty($cancel_charges)){
                $sc_account_id         = optional($booking->Driver)->stripe_account_id;
                $isConnectStore        = !empty($booking->Merchant->Configuration) && $booking->Merchant->Configuration->stripe_connect_store_enable == 1;
                
                $params = [
                    'amount'         => (int) ($cancel_charges * 100),
                    'currency'       => $paymentIntent->currency,
                    'customer'       => $paymentIntent->customer,
                    'payment_method' => $paymentIntent->payment_method,
                    'confirm'        => true,
                    // 'off_session'    => true,
                    'capture_method' => 'automatic',
                    'description'    => 'Cancellation charge (replaces auth ' . $paymentIntent->id . ')',
                    'metadata'       => [
                        'parent_payment_intent' => $paymentIntent->id,
                        'order_id'              => $booking->id,
                        'flow'                  => 'cancel_fee_exceeds_auth',
                    ],
                ];
         
                if ($isConnectStore && $sc_account_id) {
                    $params['transfer_data']          = ['destination' => $sc_account_id];
                    $params['application_fee_amount'] = $cancel_charges;
                    $params['transfer_group']         = 'ORDER_' . $booking->id;
                }
         
                $newIntent = PaymentIntent::create($params);
            }
            
            return [
                'status'  => true,
                'message'=> 'Success'
            ];
        } catch (\Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
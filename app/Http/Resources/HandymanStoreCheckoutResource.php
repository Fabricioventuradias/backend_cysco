<?php

namespace App\Http\Resources;

use App\Traits\MerchantTrait;
use App\Traits\HandymanStoreTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class HandymanStoreCheckoutResource extends JsonResource
{
    use MerchantTrait, HandymanStoreTrait;

    public function toArray($data)
    {
        $string_file = $this->getStringFile(NULL, $this->User->Merchant);
        $currency    = $this->User->Country->isoCode;
        $merchant_id = $this->User->merchant_id;

        /* ── 1. Subscription discount — single call, reused per service ── */
        $subscriptionData = hasActiveSubscription($this->User);

        /* ── 2. service_details ── */
        $checkout_details = $this->HandymanStoreCheckoutDetails;
        $services         = [];
        $ordered_services = [];
        $cart_amount      = 0;
        $subscription_discount_total = 0;

        foreach ($checkout_details as $service) {
            $base_price = (float) $service->price;

            $discount  = $this->calculateSubscriptionDiscount($subscriptionData, $base_price);
            $disc_amt  = $discount['discount_amount'];
            $final_amt = $discount['final_amount'];

            $service_name = $service->handymanStoreService->serviceName ?? 'NA';
            $serviceImage = $service->handymanStoreService->HandymanStoreService(
                $service->handymanStoreService->service_type_id,
                $service->handymanStoreService->handyman_store_id
            );

            $services[] = [
                'id'                           => $service->id,
                'handyman_store_service_id'    => $service->handyman_store_service_id,
                'name'                         => $service_name,
                'quantity'                     => $service->quantity,
                'price_type'                   => $service->price_type              ?? null,
                'segment_price_card_detail_id' => $service->segment_price_card_detail_id ?? null,
                'amount'                       => $base_price,
                'amount_string'                => $currency . ' ' . sprintf("%.2f", $base_price),
                'discount_amount'              => $disc_amt,
                'discount_amount_string'       => $currency . ' ' . sprintf("%.2f", $disc_amt),
                'final_amount'                 => $final_amt,
                'final_amount_string'          => $currency . ' ' . sprintf("%.2f", $final_amt),
                'image'                        => !empty($serviceImage)
                                                    ? get_image($serviceImage, 'service', $merchant_id)
                                                    : "",
                'is_favourite'                 => false,
                'rating'                       => 0,
                'reviewCount'                  => 0,
            ];

            $ordered_services[] = [
                'service_type_id'              => (string) $service->service_type_id,
                'handyman_store_service_id'    => (string) $service->handyman_store_service_id,
                'quantity'                     => (string) $service->quantity,
                'price'                        => $base_price,
                'segment_price_card_detail_id' => (string) ($service->segment_price_card_detail_id ?? ''),
                'service_price'                => $final_amt,
                'service_name'                 => $service_name,
                'discount_amount'              => $disc_amt,
                'discount_amount_string'       => $currency . ' ' . sprintf("%.2f", $disc_amt),
            ];

            $cart_amount                 += $base_price * $service->quantity;
            $subscription_discount_total += $disc_amt   * $service->quantity;
        }

        /* ── 3. Amounts — READ from checkout model (already calculated in getCartDataApp) ── */
        $tax_amount      = (float) ($this->tax            ?? 0);
        $tax_per         = (string) ($this->tax_per       ?? 0);

        // ── promo discount — from saved checkout ──
        $promo_discount       = (float) ($this->promo_discount        ?? 0);
        $subscription_discount = (float) ($this->subscription_discount ?? $subscription_discount_total);

        // ── combined discount — from saved checkout ──
        $discount_amount = (float) ($this->discount_amount ?? 0);

        // ── final amount — from saved checkout ──
        $final_total = (float) ($this->estimate_amount ?? 0);
        if ($final_total == 0) {
            $final_total = max(0, $cart_amount - $discount_amount + $tax_amount);
        }

        /* ── 4. Bill details ── */
        $bill_details = [
            [
                'name'  => trans("$string_file.cart_amount"),
                'value' => (string) round($cart_amount, 2),
                'bold'  => false,
            ],
            [
                'name'  => trans("$string_file.tax_amount"),
                'value' => (string) round($tax_amount, 2),
                'bold'  => false,
            ],
        ];

        // ── show subscription discount separately if present ──
        if ($subscription_discount > 0) {
            $bill_details[] = [
                'name'  => trans("$string_file.subscription_discount"),
                'value' => (string) round($subscription_discount, 2),
                'bold'  => false,
            ];
        }

        // ── show promo discount separately if present ──
        // if ($promo_discount > 0) {
        //     $bill_details[] = [
        //         'name'  => trans("$string_file.promo_discount"),
        //         'value' => (string) round($promo_discount, 2),
        //         'bold'  => false,
        //     ];
        // }

        // ── show combined only if both present ──
        if ($discount_amount > 0) {
            $bill_details[] = [
                'name'  => trans("$string_file.discount_amount"),
                'value' => (string) round($discount_amount, 2),
                'bold'  => false,
            ];
        }

        $bill_details[] = [
            'name'  => trans("$string_file.final_amount_paid"),
            'value' => (string) round($final_total, 2),
            'bold'  => true,
        ];

        /* ── 5. Schedule ── */
        $slot     = $this->ServiceTimeSlotDetail ?? null;
        $schedule = [
            'booking_date'        => !empty($this->booking_date)
                                        ? date("Y-m-d", strtotime($this->booking_date))
                                        : date("Y-m-d"),
            'time_slot_detail_id' => $slot->id             ?? null,
            'slot_label'          => $slot->slot_time_text ?? "",
        ];

        /* ── 6. Service address ── */
        $service_address = [
            'address'   => $this->drop_location ?? "",
            'latitude'  => (string) ($this->latitude  ?? ""),
            'longitude' => (string) ($this->longitude ?? ""),
        ];

        /* ── 7. Driver details ── */
        $driver_details = (object) [];
        if (!empty($this->Driver)) {
            $driver         = $this->Driver;
            $driver_details = [
                'id'         => $driver->id,
                'image'      => !empty($driver->profile_picture)
                                    ? get_image($driver->profile_picture, 'driver', $merchant_id)
                                    : "",
                'first_name' => $driver->first_name ?? "",
                'last_name'  => $driver->last_name  ?? "",
            ];
        }

        /* ── 8. Return ── */
        return [
            'cart_id'                     => $this->id,
            'total_quantity'              => $checkout_details->sum('quantity'),
            'total_amount'                => round($cart_amount, 4),

            // ── discount breakdown ──
            'subscription_discount'       => round($subscription_discount, 2),
            'promo_discount'              => round($promo_discount, 2),
            'discount_amount'             => round($discount_amount, 4),

            'tax'                         => round($tax_amount, 4),
            'tax_per'                     => $tax_per,
            'final_amount'                => (string) round($final_total, 2),

            'ordered_services'            => $ordered_services,
            'service_time_slot_detail_id' => $slot->id              ?? null,
            'booking_date'                => $schedule['booking_date'],
            'booking_time'                => $slot->slot_time_text  ?? "",
            'segment_price_card_id'       => $this->segment_price_card_id ?? null,
            'latitude'                    => (string) ($this->latitude    ?? ""),
            'longitude'                   => (string) ($this->longitude   ?? ""),
            'drop_location'               => $this->drop_location         ?? "",
            'service_address'             => $service_address,
            'schedule'                    => $schedule,
            'service_details'             => $services,
            'bill_details'                => $bill_details,

            // ── promo code applied ──
            'applied_promo_code'          => $this->PromoCode->promoCode ?? "",
            'promo_code_description'      => $this->PromoCode->promo_code_description ?? "",

            'store_address'               => $this->HandymanStore->address ?? "",
        ];
    }
}
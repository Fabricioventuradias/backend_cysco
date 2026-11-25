<?php
namespace App\Services;

use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\MapBoxController;
use App\Http\Controllers\Helper\Merchant as MerchantHelper;
use App\Models\BusinessSegment\Option;
use App\Models\BusinessSegment\Order;
use App\Models\BusinessSegment\OrderDetail;
use App\Models\BusinessSegment\Product;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\CountryArea;
use App\Models\Merchant;
use App\Models\POS\PosPayment;
use App\Models\POS\PosSession;
use App\Models\POS\PosStaffProfile;
use App\Models\PriceCard;
use App\Models\ProductCart;
use App\Traits\MerchantTrait;
use App\Traits\OrderTrait;
use App\Traits\POS\PosOrderTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\User;
use App\Traits\ProductTrait;
class PosOrderService
{
    use MerchantTrait, OrderTrait, PosOrderTrait,ProductTrait;

    public function saveCart(PosStaffProfile $staffProfile, array $data): object
    {
        $merchant_id         = $staffProfile->merchant_id;
        $business_segment_id = $staffProfile->business_segment_id;
        $string_file         = $this->getStringFile($merchant_id);

        $session = PosSession::where('id', $data['pos_session_id'])
            ->where('business_segment_id', $business_segment_id)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            throw new \Exception('No active POS session found.');
        }

        DB::beginTransaction();
        try {
            if (!empty($data['product_update']) && $data['product_update'] === 'YES' && !empty($data['cart_id'])) {
                $product_cart = ProductCart::where('id', $data['cart_id'])
                    ->where('is_pos', 1)->first();

                if (!$product_cart) {
                    throw new \Exception(trans("$string_file.cart_not_found"));
                }

                $product_details  = json_decode($product_cart->product_details, true);
                $updated_products = [];
                $product_options  = $data['options'] ?? [];

                foreach ($product_details as $product) {
                    if (!empty($product_options)) {
                        $quantity = (
                            $product['product_variant_id'] == $data['product_variant_id'] &&
                            $product['options'] == $product_options
                        ) ? $data['quantity'] : $product['quantity'];
                    } else {
                        $quantity = $product['product_variant_id'] == $data['product_variant_id']
                            ? $data['quantity']
                            : $product['quantity'];
                    }

                    if ($quantity > 0) {
                        $updated_products[] = [
                            'product_variant_id' => $product['product_variant_id'],
                            'quantity'           => $quantity,
                            'options'            => $product['options'] ?? [],
                        ];
                    }
                }

                $product_cart->product_details = json_encode($updated_products);

            } else {
                $price_card = PriceCard::where([
                    ['status',          '=', 1],
                    ['merchant_id',     '=', $merchant_id],
                    ['service_type_id', '=', $data['service_type_id']],
                    ['segment_id',      '=', $data['segment_id']],
                    ['price_card_for',  '=', 2],
                ])->first();

                if (!$price_card) {
                    throw new \Exception(trans("$string_file.no_price_card_for_area"));
                }

                $product_cart = ProductCart::where('is_pos', 1)
                    ->where('pos_staff_id', $staffProfile->id)
                    ->where('pos_session_id', $data['pos_session_id'])
                    ->where('segment_id', $data['segment_id'])
                    ->first();

                if (!$product_cart) {
                    $product_cart               = new ProductCart();
                    $product_cart->user_id      = $staffProfile->user_id;
                    $product_cart->merchant_id  = $merchant_id;
                    $product_cart->segment_id   = $data['segment_id'];
                    $product_cart->is_pos       = 1;
                    $product_cart->pos_staff_id   = $staffProfile->id;
                    $product_cart->pos_session_id = $data['pos_session_id'];
                }

                $product_cart->service_type_id     = $data['service_type_id'];
                $product_cart->product_details     = is_string($data['product_details'])
                    ? $data['product_details']
                    : json_encode($data['product_details']);
                $product_cart->price_card_id       = $price_card->id;
                $product_cart->business_segment_id = $business_segment_id;
                $product_cart->pos_terminal_id     = $data['pos_terminal_id'] ?? null;
                $product_cart->pos_order_type      = $data['pos_order_type'];
                $product_cart->pos_table_number    = $data['pos_table_number'] ?? null;
                $product_cart->pos_customer_name   = $data['pos_customer_name'] ?? null;
                $product_cart->pos_customer_phone  = $data['pos_customer_phone'] ?? null;
            }

            $product_cart->save();

            $promoCode   = $data['promo_code_object'] ?? null;
            $fakeRequest = new Request($data);
            $cartData    = $this->getPosCartData($product_cart, $promoCode, 'save_cart', $fakeRequest, $string_file);

            DB::commit();
            return $cartData;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function placeOrder(PosStaffProfile $staffProfile, array $data): array
    {
        $merchant_id = $staffProfile->merchant_id;
        $string_file = $this->getStringFile($merchant_id);

        DB::beginTransaction();
        try {
            $product_cart = ProductCart::where('id', $data['cart_id'])
                ->where('is_pos', 1)
                ->where('pos_staff_id', $staffProfile->id)
                ->first();

            if (!$product_cart) {
                throw new \Exception(trans("$string_file.cart_not_found"));
            }

            $bs       = $product_cart->BusinessSegment;
            $merchant = Merchant::find($merchant_id);
            $pos_kitchen_display = $merchant->BookingConfiguration->pos_kitchen_display ?? 1;
            
            $promoCode    = $data['promo_code_object'] ?? null;
            $fakeRequest  = new Request($data);
            $return_cart  = $this->getPosCartData($product_cart, $promoCode, 'place_order', $fakeRequest, $string_file);
            $admin_receipt = $product_cart->pos_receipt;
            if (empty($admin_receipt)) {
                throw new \Exception(trans("$string_file.cart_not_found"));
            }

            foreach ($return_cart->product_details as $product_variant) {
                if ($product_variant['variant_status'] == 2) {
                    throw new \Exception(trans("$string_file.cart_out_of_stock"));
                }
            }
            
            $order                      = new Order();
            $order->merchant_id         = $merchant_id;
            $order->user_id = $staffProfile->user_id;
            if (!empty($data['pos_customer_name'])) {
                $name_parts = explode(' ', trim($data['pos_customer_name']), 2);
                $first_name = $name_parts[0];
                $last_name  = $name_parts[1] ?? ''; // empty if no space
            } else {
                $first_name = 'POS Customer';
                $last_name  = '';
            }
            if (!empty($data['pos_customer_phone'])) {
                $customer = User::where('UserPhone', $data['pos_customer_phone'])
                    ->where('merchant_id', $merchant_id)
                    ->first();
                if (!$customer) {
                    $customer               = new User();
                    $customer->merchant_id  = $merchant_id;
                    $customer->UserPhone        = $data['pos_customer_phone'];
                    $customer->first_name   = $first_name;
                    $customer->last_name    = $last_name;
                    $customer->pos_type     = 1; // pos customer
                    $customer->country_id = $bs->CountryArea->Country->id;
                    $customer->country_area_id = $bs->CountryArea->id;
                    $customer->password     = \Hash::make(12345678);  //fixed for pos user we change it from users
                    $customer->save();
                }else{
                    $customer->pos_type     =  $customer->pos_type ?? 1;
                    $customer->save();
                }
                $order->user_id = $customer->id;
            }
            date_default_timezone_set($bs->CountryArea['timezone']);
            $order->pos_staff_user_id   = $staffProfile->id;
            $order->segment_id          = $product_cart->segment_id;
            $order->business_segment_id = $product_cart->business_segment_id;
            $order->country_area_id     = $bs->country_area_id;
            $order->service_type_id     = $product_cart->service_type_id;
            $order->price_card_id       = $product_cart->price_card_id;
            $order->payment_method_id   = $data['payment_method_id'];
            $order->payment_status      = 1;
            $order->cart_amount         = $admin_receipt['total_amount_formatted'];
            $order->discount_amount     = $admin_receipt['discount_amount'] ?? 0;
            $order->tax                 = $admin_receipt['tax_amount_formatted'];
            $order->delivery_amount     = $admin_receipt['delivery_charge_formatted'];
            $order->final_amount_paid   = $admin_receipt['final_amount'];
            $order->quantity            = $admin_receipt['quantity'];
            $order->order_timestamp     = time();
            $order->order_date          = Carbon::now()->setTimeZone($bs->CountryArea->timezone)->format('Y-m-d');
            $order->is_pos              = 1;
            $order->pos_session_id      = $data['pos_session_id'];
            $order->pos_order_type      = $product_cart->pos_order_type;
            $order->pos_table_number    = $product_cart->pos_table_number;
            $order->promo_code_id       = !empty($promoCode) ? $promoCode->id : NULL;
            $order->additional_notes    = $data['additional_notes'] ?? null;
            $order->pos_receipt         = json_encode($admin_receipt);
            $order->bill_details        = json_encode([
                'user'   => ['slab_amount' => $admin_receipt['final_amount']],
                'driver' => [],
            ]);
            $order->order_status = $pos_kitchen_display == 1 ? 1 : 11;
            $order->save();

            $pos_payment                  = new PosPayment();
            $pos_payment->order_id        = $order->id;
            $pos_payment->pos_session_id  = $data['pos_session_id'];
            $pos_payment->payment_method  = $data['payment_method_id'];
            $pos_payment->amount          = $order->final_amount_paid;
            $pos_payment->transaction_ref = 'POSTXN' . $order->merchant_order_id . '-' . time();
            $pos_payment->status          = PosPayment::STATUS_COMPLETED;
            if ($data['payment_method_id'] == 1) {
                $pos_payment->cash_received   = $data['cash_received'] ?? null;
                $pos_payment->change_returned = $data['change_returned'] ?? null;
            }
            $pos_payment->save();

            $fakeRequest->merge(['order_id' => $order->id]);
            $this->saveOrderStatusHistory($fakeRequest, $order);

            foreach ($return_cart->product_details as $product) {
                $detail                     = new OrderDetail();
                $detail->order_id           = $order->id;
                $detail->product_id         = $product['product_id'];
                $detail->product_variant_id = $product['product_variant_id'];
                $detail->weight_unit_id     = $product['weight_unit_id'];
                $detail->quantity           = $product['quantity'];
                $detail->price              = $product['product_price'];
                $detail->discount           = 0;
                $detail->total_amount       = $product['total_price'];
                $detail->options            = !empty($product['arr_option'])
                    ? json_encode($product['arr_option']) : null;
                $detail->save();

                if ($product['manage_inventory'] == 1) {
                    $fakeRequest->merge([
                        'order_id'     => $detail->order_id,
                        'product_id'   => $detail->product_id,
                        'id'           => $detail->product_variant_id,
                        'new_stock'    => $detail->quantity,
                        'stock_type'   => 2,
                        'stock_out_id' => $detail->id,
                    ]);
                    $this->manageProductVariantInventory($fakeRequest);
                }
            }

            if ($pos_kitchen_display == 2) {
                $this->orderTransaction($fakeRequest, $order, 2);
            }

            $product_cart->delete();
            DB::commit();

            return [
                'order_id'            => $order->id,
                'order_number'        => $order->merchant_order_id,
                'order_status'        => $order->order_status,
                'pos_kitchen_display' => $pos_kitchen_display,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCurrentCart(PosStaffProfile $staffProfile, int $sessionId, int $segmentId, string $stringFile): ?object
    {
        $cart = ProductCart::where('is_pos', 1)
            ->where('pos_staff_id', $staffProfile->id)
            ->where('pos_session_id', $sessionId)
            ->where('segment_id', $segmentId)
            ->first();

        if (!$cart) return null;

        $fakeRequest = new Request([
            'pos_session_id' => $sessionId,
            'segment_id'     => $segmentId,
        ]);

        return $this->getPosCartData($cart, null, 'get_cart', $fakeRequest, $stringFile);
    }

    public function clearCart(PosStaffProfile $staffProfile, int $sessionId, int $segmentId): void
    {
        ProductCart::where('is_pos', 1)
            ->where('pos_staff_id', $staffProfile->id)
            ->where('pos_session_id', $sessionId)
            ->where('segment_id', $segmentId)
            ->delete();
    }
    
    public function applyRemovePromo($staffProfile, $sessionId, $segmentId, $promoCode = NULL,$stringFile){
        $product_cart = ProductCart::where('is_pos', 1)
            ->where('pos_staff_id', $staffProfile->id)
            ->where('pos_session_id', $sessionId)
            ->where('segment_id', $segmentId)
            ->first();
        if (!$product_cart) {
            throw new \Exception(trans("$stringFile.cart_not_found"));
        }
    
        $promocode = null;
    
        if (!empty($promoCode)) {
            $fakeRequest = new Request([
                'promo_code'          => $promoCode,
                'order_amount'        => $product_cart->cart_amount,
                'segment_id'          => $segmentId,
                'merchant_id'         => $staffProfile->merchant_id,
                'business_segment_id' => $staffProfile->business_segment_id,
                'area'                => $product_cart->BusinessSegment->country_area_id,
                'promo_source'        => 2, // POS
                'user_id'             => $staffProfile->user_id, // for admin panel
            ]);
    
            $commonController = new CommonController();
            $checkPromo       = $commonController->checkPromoCode($fakeRequest);
    
            if (!isset($checkPromo['status']) || !$checkPromo['status']) {
                throw new \Exception(
                    isset($checkPromo['message'])
                        ? $checkPromo['message']
                        : trans("$stringFile.invalid_promo_code")
                );
            }
    
            $promocode = $checkPromo['promo_code'];
        }
    
        $fakeRequest = new Request([
            'pos_session_id' => $sessionId,
            'segment_id'     => $segmentId,
        ]);
    
        return $this->getPosCartData($product_cart,$promocode,'applyRemovePromoCode',$fakeRequest,$stringFile);
    }

    // Exact copy of getPosCartData from API controller
    // Only difference: it's now in the service, accessible to both API and admin
    private function getPosCartData(ProductCart $product_cart, $promo_code, string $calling_from, Request $request, string $string_file)
    {
        $merchant_helper         = new MerchantHelper();
        $merchant_id             = $product_cart->merchant_id;
        $trip_calculation_method = $product_cart->Merchant->Configuration->trip_calculation_method;
        $format_price            = $product_cart->Merchant->Configuration->format_price;
        $time_format             = $product_cart->Merchant->Configuration->time_format;
    
        $arr_cart_product = json_decode($product_cart->product_details, true);
    
        $res  = [];
        $res2 = [];
        foreach ($arr_cart_product as $key => $product) {
            $res[$product['product_variant_id'] . '_' . $key] = $product;
            $res2[] = $product['product_variant_id'];
        }
        $arr_variant    = $res;
        $arr_product_id = $res2;
    
        $arr_product_list = ProductVariant::select('id', 'product_id', 'product_price', 'weight', 'weight_unit_id', 'is_title_show', 'discount', 'status')
            ->whereIn('id', $arr_product_id)
            ->where('delete', NULL)
            ->with(['Product' => function ($q) {
                $q->select('id', 'category_id', 'merchant_id', 'manage_inventory', 'tax', 'product_cover_image', 'food_type', 'business_segment_id');
                $q->with(['Option']);
            }])
            ->with(['ProductInventory' => function ($q) {
                $q->select('id', 'product_variant_id', 'current_stock');
            }])
            ->get();
    
        $arrProdList = array_column($arr_product_list->toArray(), NULL, 'id');
    
        $total_cart_quantity               = 0;
        $total_cart_amount                 = 0;
        $total_tax_amount                  = 0;
        $total_product_discount            = 0;
        $business_segment_id               = NULL;
        $cart_out_of_stock                 = false;
        $price_availability_time_slab_logs = [];
        $arr_cart_product_result           = [];
    
        $country_area = CountryArea::find(
            optional($product_cart->BusinessSegment)->country_area_id
        );
        $currency = isset($country_area->Country) ? $country_area->Country->isoCode : "";
    
        foreach ($arr_variant as $key => $variant) {
            $product     = $arrProdList[$variant['product_variant_id']];
            $prodVariant = ProductVariant::find($variant['product_variant_id']);
            $prodId      = $product['product']['id'];
            $productData = Product::find($prodId);
            $business_segment_id = $product['product']['business_segment_id'];
    
            $arr_return_option = [];
            $arr_option_amount = [];
            $arr_options = $variant['options'] ?? [];
            if (!empty($arr_options)) {
                foreach ($product['product']['option'] as $option) {
                    if (in_array($option['id'], $arr_options)) {
                        $optionData = Option::find($option['id']);
                        $arr_return_option[] = [
                            'id'          => $option['id'],
                            'option_name' => $optionData->Name($business_segment_id),
                            'amount'      => $option['pivot']['option_amount'],
                        ];
                        $arr_option_amount[] = $option['pivot']['option_amount'];
                    }
                }
            }
            $product_option_amount = array_sum($arr_option_amount);
    
            $product_discount = (!empty($product['discount']) && $product['discount'] > 0)
                ? $product['discount'] : NULL;
            $quantity      = (int) $variant['quantity'];
            $product_price = $product['product_price'];
    
            $manage_inventory  = $product['product']['manage_inventory'];
            $item_out_of_stock = false;
            $current_stock     = 0;
            if ($manage_inventory == 1 && !empty($product['product_inventory']['id'])) {
                $current_stock     = $product['product_inventory']['current_stock'];
                $item_out_of_stock = $current_stock < $quantity;
                if ($item_out_of_stock) $cart_out_of_stock = true;
            }
    
            $product_image = count($productData->ProductImage) > 0
                ? get_image($productData->ProductImage[0]['product_image'], 'product_image', $merchant_id)
                : "";
    
            $product_total_price    = (($product_price - ($product_discount ?? 0)) + $product_option_amount) * $quantity;
            $total_product_discount += ($product_discount ?? 0);
    
            $unit = '';
            if (isset($product['weight_unit_id'])) {
                $weightUnitData = \App\Models\WeightUnit::find($product['weight_unit_id']);
                $unit = $weightUnitData->WeightUnitName ?? '';
            }
    
            $discounted_price = !empty($product_discount) ? ($product_price - $product_discount) : "";
    
            $arr_cart_product_result[] = [
                'product_id'            => $product['product_id'],
                'weight_unit_id'        => $product['weight_unit_id'],
                'product_variant_id'    => $product['id'],
                'food_type'             => $product['product']['food_type'],
                'quantity'              => $quantity,
                'current_stock'         => $current_stock,
                'manage_inventory'      => $manage_inventory,
                'currency'              => $currency,
                'item_out_of_stock'     => $item_out_of_stock,
                'product_price'         => $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method),
                'discount'              => !empty($product_discount) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "",
                'discounted_price'      => !empty($discounted_price) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "",
                'total_price'           => $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method),
                'weight_unit'           => $product['weight'] . ' ' . $unit,
                'product_name'          => $productData->Name($merchant_id),
                'variant_title_heading' => $product['is_title_show'] == 1 ? trans("$string_file.size") : "",
                'variant_title'         => $product['is_title_show'] == 1 ? $prodVariant->Name($merchant_id) : "",
                'variant_status'        => $product['status'],
                'product_cover_image'   => !empty($product['product']['product_cover_image'])
                    ? get_image($product['product']['product_cover_image'], 'product_cover_image', $merchant_id, true)
                    : $product_image,
                'product_image'         => $product_image,
                'arr_option'            => $arr_return_option,
            ];
    
            $total_cart_quantity += $quantity;
            $total_cart_amount   += $product_total_price;
        }
    
        $product_cart->business_segment_id = $business_segment_id;
        $product_cart->cart_amount         = $total_cart_amount;
        $product_cart->save();
    
        // ── Delivery charge ──────────────────────────────────────────────────────
        $delivery_charge = 0;
        if ($product_cart->pos_order_type === 'delivery' && $product_cart->ServiceType->type == 1) {
            $delivery_charge = (string) $product_cart->delivery_charge;
    
            if (!empty($request->latitude) && !empty($request->longitude)) {
                $from = $product_cart->BusinessSegment->latitude . ',' . $product_cart->BusinessSegment->longitude;
                $to   = $request->latitude . ',' . $request->longitude;
    
                $google_key = $product_cart->Merchant->BookingConfiguration->google_key;
                $units = isset($product_cart->BusinessSegment->CountryArea->Country) &&
                    ($product_cart->BusinessSegment->CountryArea->Country['distance_unit'] == 1)
                    ? 'metric' : 'imperial';
    
                $selected_map  = getSelectedMap($product_cart->Merchant, "FOOD_CART");
                $google_result = match ($selected_map) {
                    'MAP_BOX' => MapBoxController::MapBoxDistanceAndTime($from, $to, $google_key, $units, false, 'foodCart', $string_file),
                    default   => GoogleController::GoogleDistanceAndTime($from, $to, $google_key, $units, false, 'foodCart', $string_file),
                };
    
                $user_distance = $google_result['distance_in_meter'] ?? null;
                $product_cart->estimate_distance = $google_result['distance'] ?? null;
    
                if (!empty($user_distance)) {
                    $delivery_charge_slabs = $product_cart->PriceCard->PriceCardDetail->where('status', 1)->toArray();
                    $request->merge(['for' => 2, 'distance' => $user_distance, 'cart_amount' => $total_cart_amount]);
                    $slab = $this->getDistanceSlab($request, $delivery_charge_slabs);
                    if (isset($slab['slab_amount'])) {
                        $delivery_charge = $slab['slab_amount'];
                    }
                }
                $product_cart->delivery_charge = $delivery_charge;
                $product_cart->save();
            }
        }
    
        // ── Promo discount ───────────────────────────────────────────────────────
        $discount_amount = 0;
        $promocode       = "";
        if (!empty($promo_code->id)) {
            if ($promo_code->promoCode === "FIRSTDELFREE") {
                $discount_amount = $delivery_charge;
            } else {
                $discount_amount = $promo_code->promo_code_value;
                if ($promo_code->promo_code_value_type == 2) {
                    $promoMaxAmount  = $promo_code->promo_percentage_maximum_discount;
                    $discount_amount = ($total_cart_amount * $discount_amount) / 100;
                    $discount_amount = !empty($promoMaxAmount) && ($discount_amount > $promoMaxAmount)
                        ? $promoMaxAmount : $discount_amount;
                }
            }
            $promocode = $promo_code->promoCode;
        }
    
        // ── Tax ──────────────────────────────────────────────────────────────────
        $tax_per = $product_cart->PriceCard->tax ?? 0;
        if ($tax_per > 0) {
            $total_tax_amount = ($total_cart_amount * $tax_per / 100);
        }
    
        $final = ($total_cart_amount - $discount_amount) + $total_tax_amount + (float) $delivery_charge;
    
        // ── Format amounts ───────────────────────────────────────────────────────
        $total_amount    = $merchant_helper->TripCalculation($total_cart_amount, $merchant_id, $trip_calculation_method);
        $discount_amount = $merchant_helper->TripCalculation($discount_amount, $merchant_id, $trip_calculation_method);
        $tax_amount      = $merchant_helper->TripCalculation($total_tax_amount, $merchant_id, $trip_calculation_method);
        $delivery_charge = $merchant_helper->TripCalculation($delivery_charge, $merchant_id, $trip_calculation_method);
        $final_amount    = $merchant_helper->TripCalculation($final, $merchant_id, $trip_calculation_method);
    
        $c_amount = number_format($total_amount, 2);
        if (
            isset($product_cart->Merchant->ApplicationConfiguration->business_segment_tax_inclusive) &&
            $product_cart->Merchant->ApplicationConfiguration->business_segment_tax_inclusive == 1
        ) {
            $c_amount = number_format(($total_amount - $tax_amount), 2);
        }
        $c_amount    = str_replace(',', '', $c_amount);
        $cart_amount = $merchant_helper->TripCalculation($c_amount, $merchant_id, $trip_calculation_method);
    
        $result = $this->buildPosBillDetails(
            $merchant_id, $cart_amount, $discount_amount, $tax_amount,
            $total_amount, $delivery_charge, $final_amount, $currency,
            $total_cart_quantity, $product_cart->pos_order_type,
            $format_price, $trip_calculation_method, $string_file
        );
    
        $product_receipt           = $result['receipt'];
        $product_receipt_for_admin = $result['admin_receipt'];
    
        // ── Payment methods ──────────────────────────────────────────────────────
        $paymentMethods = $product_cart->PriceCard->CountryArea->PaymentMethod;
        $bookingData    = new BookingDataController();
        $options        = $bookingData->PaymentOption(
            $paymentMethods, $product_cart->user_id, null,
            $product_cart->PriceCard->minimum_wallet_amount, NULL, false,
            $product_cart->segment_id, 2
        );
    
        $business_segment = BusinessSegment::select(
            'commission_method', 'commission', 'full_name', 'address', 'delivery_time'
        )->find($business_segment_id);
    
        // ── Assemble response ────────────────────────────────────────────────────
        $product_cart->cart_out_of_stock  = $cart_out_of_stock;
        $product_cart->promoCode          = $promocode;
        $product_cart->receipt            = $product_receipt;
        $product_cart->pos_receipt        = $product_receipt_for_admin;
        $product_cart->product_details    = $arr_cart_product_result;
        $product_cart->payment_method     = $options;
        $product_cart->store_name         = $business_segment->full_name;
        $product_cart->store_address      = $business_segment->address;
        $product_cart->delivery_time      = $business_segment->delivery_time . ' ' . trans("$string_file.minute");
        $product_cart->pos_order_type     = $product_cart->pos_order_type;
        $product_cart->pos_table_number   = $product_cart->pos_table_number;
        $product_cart->pos_customer_name  = $product_cart->pos_customer_name;
        $product_cart->pos_customer_phone = $product_cart->pos_customer_phone;
        $product_cart->active_promo_code  = !empty($promocode);
        $product_cart->promo_code_text    = !empty($request->promo_code)
            ? trans("$string_file.apply_promo_applied")
            : trans("$string_file.apply_promo_code");
    
        // ── Clean up relations — prevents bloated response ───────────────────────
        unset(
            $product_cart->PriceCard,
            $product_cart->User,
            $product_cart->BusinessSegment,
            $product_cart->Merchant,
            $product_cart->created_at,
            $product_cart->updated_at,
            $product_cart->Segment,
            $product_cart->ServiceType
        );
        $product_cart->setRelations([]); // ← this is the key fix — clears ALL eager loaded relations
    
        return $product_cart;
    }
}
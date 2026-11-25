<?php

namespace App\Traits\POS;

use App\Http\Controllers\Helper\Merchant as MerchantHelper;
use DB;
use App\Models\POS\PosPayment;

trait PosOrderTrait
{
    public function buildPosBillDetails($merchant_id,$cart_amount,$discount_amount,$tax_amount,$total_amount,$delivery_charge,$final_amount,$currency,$quantity,$pos_order_type,$format_price,$trip_calculation_method,$string_file) {
        $merchant_helper = new MerchantHelper();

        // ── For saving to order / admin use ─────────────────────────────────
        $product_receipt_for_admin = [
            'cart_amount'               => $merchant_helper->PriceFormat($cart_amount, $merchant_id, $format_price, $trip_calculation_method),
            'currency'                  => $currency,
            'quantity'                  => $quantity,
            'total_amount'              => $merchant_helper->PriceFormat($total_amount, $merchant_id, $format_price, $trip_calculation_method),
            'total_amount_formatted'    => $total_amount,
            'discount_amount'           => $merchant_helper->PriceFormat($discount_amount, $merchant_id, $format_price, $trip_calculation_method),
            'tax_amount'                => $merchant_helper->PriceFormat($tax_amount, $merchant_id, $format_price, $trip_calculation_method),
            'tax_amount_formatted'      => $tax_amount,
            'delivery_charge'           => $merchant_helper->PriceFormat($delivery_charge, $merchant_id, $format_price, $trip_calculation_method),
            'delivery_charge_formatted' => $delivery_charge,
            'final_amount'              => $final_amount,
            'final_amount_formatted'    => $merchant_helper->PriceFormat($final_amount, $merchant_id, $format_price, $trip_calculation_method)
        ];
    
        // ── For API response — formatted bill details ────────────────────────
        $bill_details = [];
    
        if ($cart_amount > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.cart_amount"),
                'value'           => $product_receipt_for_admin['cart_amount'],
                'value_formatted' => $cart_amount,
                'bold'            => false,
            ];
        }
        if ($discount_amount > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.discount_amount"),
                'value'           => '- ' . $product_receipt_for_admin['discount_amount'],
                'value_formatted' => $discount_amount,
                'bold'            => false,
            ];
        }
        if ($tax_amount > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.tax"),
                'value'           => $product_receipt_for_admin['tax_amount'],
                'value_formatted' => $tax_amount,
                'bold'            => false,
            ];
        }
        if ($total_amount > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.total_amount"),
                'value'           => $product_receipt_for_admin['total_amount'],
                'value_formatted' => $total_amount,
                'bold'            => false,
            ];
        }
        if ($pos_order_type === 'delivery' && $delivery_charge > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.delivery_charge"),
                'value'           => $product_receipt_for_admin['delivery_charge'],
                'value_formatted' => $delivery_charge,
                'bold'            => false,
            ];
        }
        
        if ($final_amount > 0) {
            $bill_details[] = [
                'name'            => trans("$string_file.grand_total"),
                'value'           => $product_receipt_for_admin['final_amount_formatted'],
                'value_formatted' => $final_amount,
                'bold'            => true,
            ];
        }
    
        $product_receipt = [
            'currency'     => $currency,
            'quantity'     => $quantity,
            'bill_details' => $bill_details,
        ];
    
        // Return both
        return [
            'receipt'       => $product_receipt,           // for API response
            'admin_receipt' => $product_receipt_for_admin, // for saving to order
        ];
    }
    
    public function getStatusLabel($string_file){
        $status_labels = [
                1 => trans("$string_file.pos_new_order"),
                2 => trans("$string_file.pos_cancel_order"),
                6  => trans("$string_file.pos_order_process"),
                7  => trans("$string_file.pos_order_ready"),
                11 => trans("$string_file.pos_order_completed"),
                12 => trans("$string_file.auto_expired"),
            ];
            
            return $status_labels;
    }
    
    public function getStatusColor(): array
    {
        return [
            1  => 'primary',
            2  => 'danger',
            6  => 'info',
            7  => 'warning',
            11 => 'success',
            12 => 'secondary',
        ];
    }
    
    public function getTerminalStatuses(): array
    {
        return [2, 11, 12]; // cancelled, completed, expired
    }
    
    public function isTerminalStatus(int $status): bool
    {
        return in_array($status, $this->getTerminalStatuses());
    }
    
    public function getNextStatus(int $status, bool $kitchenDisplay = true): ?int
    {
        $transitions = $kitchenDisplay
            ? [1 => 6, 6 => 7, 7 => 11]  // full flow: new → process → ready → completed
            : [1 => 11];                   // no kitchen: new → completed directly
    
        return $transitions[$status] ?? null;
    }
    
    // public function buildSessionSummary(int $sessionId): array
    // {
    //     $orders = DB::table('orders')
    //         ->where('pos_session_id', $sessionId)
    //         ->where('payment_status', 1)
    //         ->whereIn('order_status', [4, 9, 10, 11])
    //         ->select([
    //             DB::raw('COUNT(*) as total_orders'),
    //             DB::raw('SUM(final_amount_paid) as total_revenue'),
    //         ])
    //         ->first();

    //     $byPaymentMethod = PosPayment::where('pos_session_id', $sessionId)
    //         ->where('status', 1)
    //         ->select(
    //             'payment_method',
    //             DB::raw('SUM(amount) as total'),
    //             DB::raw('COUNT(*) as count')
    //         )
    //         ->groupBy('payment_method')
    //         ->get();

    //     $totalCash = $byPaymentMethod
    //         ->where('payment_method', PosPayment::METHOD_CASH)
    //         ->first()?->total ?? 0;

    //     return [
    //         'total_orders'      => (int) ($orders->total_orders ?? 0),
    //         'total_revenue'     => (float) ($orders->total_revenue ?? 0),
    //         'total_cash_sales'  => (float) $totalCash,
    //         'by_payment_method' => $byPaymentMethod,
    //     ];
    // }
    
    public function buildSessionSummary(int $sessionId): array
    {
        $orders = DB::table('orders')
            ->where('pos_session_id', $sessionId)
            ->where('payment_status', 1)
            ->where('order_status', 11)
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(final_amount_paid) as total_revenue'),
            ])
            ->first();
    
        $byPaymentMethod = PosPayment::where('pos_session_id', $sessionId)
            ->where('status', PosPayment::STATUS_COMPLETED) // now matches 'completed'
            ->select(
                'payment_method',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('payment_method')
            ->get();
    
        $totalCash = $byPaymentMethod
            ->where('payment_method', PosPayment::METHOD_CASH) // now matches 'cash'
            ->first()?->total ?? 0;
    
        return [
            'total_orders'      => (int) ($orders->total_orders ?? 0),
            'total_revenue'     => (float) ($orders->total_revenue ?? 0),
            'total_cash_sales'  => (float) $totalCash,
            'by_payment_method' => $byPaymentMethod,
        ];
    }

    public function getTodayOrderCount(int $storeId): int
    {
        return DB::table('orders')
            ->where('business_segment_id', $storeId)
            ->whereDate('created_at', today())
            ->where('payment_status', 1)
            ->count();
    }

    public function getSessionRevenue(int $sessionId): float
    {
        return (float) DB::table('orders')
            ->where('pos_session_id', $sessionId)
            ->where('payment_status', 1)
            ->whereIn('order_status', [4, 9, 10, 11])
            ->sum('final_amount_paid');
    }
    
    private function getPosOrderStatusDetails($order,$string_file): array
    {
        $completed_icon = "completed"; // or your icon path
        $current_icon   = "current";
        $pending_icon   = "pending";
    
        // POS status flow
        $pos_kitchen_display = $order->Merchant->BookingConfiguration->pos_kitchen_display ?? 0;
    
        if ($pos_kitchen_display == 1) {
            $order_status_action = [1, 6, 7, 11]; // full kitchen flow
        } else {
            $order_status_action = [1, 11]; // direct complete
        }
    
        $status_labels = $this->getStatusLabel($string_file);
    
        // Parse order status history
        $status_completed    = [];
        $arr_completed_order = [];
        if (!empty($order->order_status_history)) {
            $status_completed    = json_decode($order->order_status_history, true);
            $status_completed    = array_column($status_completed, NULL, 'order_status');
            $arr_completed_order = array_keys($status_completed);
        }
    
        $order_status_holders = [];
    
        foreach ($order_status_action as $order_status) {
            $completed_time = "";
            $icon           = $pending_icon;
            $message        = "";
    
            if (in_array($order_status, $arr_completed_order)) {
                // Already completed
                $icon           = $completed_icon;
                $completed_time = $status_completed[$order_status]['order_timestamp'] ?? "";
            } elseif ($order->order_status == $order_status) {
                // Current status
                $icon = $current_icon;
            }
    
            // Status specific messages
            switch ($order_status) {
                case 1:
                    $message = trans("$string_file.pos_new_order");
                    break;
                case 6:
                    $message = trans("$string_file.pos_order_process");
                    break;
                case 7:
                    $message = trans("$string_file.pos_order_ready");
                    break;
                case 11:
                    $message = trans("$string_file.pos_order_completed");
                    break;
            }
    
            $order_status_holders[] = [
                'status'      => $order_status,
                'status_text' => $status_labels[$order_status] ?? "",
                'message'     => $message,
                'status_time' => $completed_time,
                // 'tick_icon'   => $icon,
                'is_current'  => $order->order_status == $order_status,
                'is_completed'=> in_array($order_status, $arr_completed_order),
            ];
        }
    
        return $order_status_holders;
    }
    
    private function staffHasPermission($staff, string $permission): bool
    {
        if (!$staff->role) return false;
    
        $permissions = $staff->role->permissions ?? [];
    
        // permissions stored as JSON array of keys
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }
    
        return in_array($permission, $permissions);
    }
    
    private function checkSessionActive($order): bool
    {
        if (empty($order->pos_session_id)) return true;
        
        $session = \App\Models\POS\PosSession::find($order->pos_session_id);
        return $session && $session->status === 'active';
    }
}
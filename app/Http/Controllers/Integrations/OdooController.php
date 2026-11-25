<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Traits\DriverTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DriverDocument;
use App\Models\HandymanOrder;


class OdooController extends Controller
{
    
    use DriverTrait;

    public function submit_service($request)
    {
        $apiKey=$request['config']['api_key'];
        $secretKey=$request['config']['api_secret'];
        $order_id="WORK".$request['order']->id;
        $customer_name=$request['order']['User']->first_name." ".$request['order']['User']->last_name;
        $customer_email=$request['order']['User']->email;
        $merchant_id=$request['order']->merchant_id;
        $orders=$request['order']['HandymanOrderDetail'];
        $timezone=$request['order']['CountryArea']['timezone'];
        $tax=$request['order']['tax_per'];
        $businessName = strtoupper(str_replace(' ', '', $request['order']['Merchant']->BusinessName));
        
        $lines = [];
        $expectedTotal = 0;
        date_default_timezone_set($timezone);
        $timestamp = time();
        
        foreach ($orders as $orderd) {
            $lines[] = [
                "service_code" => $businessName."_".$orderd->ServiceType->id, // Make dynamic if you have a field
                "description"  => $orderd->ServiceType->serviceName ?? '',
                "quantity"     => (int) $orderd->quantity,
                "unit_price"   => (float) $orderd->price,
                "tax_rate"     => $tax,
            ];
        
            $expectedTotal += (float) $orderd->total_amount;
        }

        $bodyData = [
            "order_ref" => $businessName.$request['order']->id,
            "customer" => [
                "name" => $customer_name,
                "tax_id" => "000213794CA012",
                "email" => $customer_email,
                "is_final_consumer" => true
            ],
            "provider_ref" => "ug8956y57",
            "lines" => $lines,
            "expected_total" => $expectedTotal,
            "service_completed_at" => $timestamp
        ];

        $result = $this->sendInvoiceRequest($apiKey, $secretKey, $bodyData,$timezone);
        
        $order=HandymanOrder::where('id', $request['order']->id)
            ->update(['odoo_response' => $result]);
        return $result;
        

    }
    function sendInvoiceRequest($apiKey, $secretKey, $bodyData,$timezone)
    {
        $url = "https://anuarios-academicos.compllexus.com/api/anuarius/invoice";
        
        
        // 1. Serialize the body to JSON
        $body = json_encode($bodyData);
        
        // 2. Get current timestamp (seconds)
        date_default_timezone_set($timezone);
        $timestamp = time();
        
        // 3. Create signature: HMAC-SHA256("timestamp.body")
        $signatureData = "{$timestamp}.{$body}";
        $signature = hash_hmac('sha256', $signatureData, $secretKey, false);
        
        // 4. Prepare headers
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'X-Timestamp: ' . $timestamp,
            'X-Signature: ' . $signature
        ];
        
        // 5. Send cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        curl_close($ch);
        return $response;
        
        // return [
        //     'success' => $httpCode === 200 || $httpCode === 201,
        //     'http_code' => $httpCode,
        //     'response' => json_decode($response, true),
        //     'raw_response' => $response
        // ];
    }


}

<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Traits\DriverTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use App\Models\DriverDocument;


class LatraController extends Controller
{
    //
    use DriverTrait;

    protected $payload;

    public function __construct($data)
    {
        // dd($data);


        $this->payload = $data;
        $calling_for = $this->payload['calling_for'];
        $config = $this->payload['config'];
        $token = $this->generate_self_signed_jwt($config['auth_token']);

        $headers = [
            'Content-Type: application/json',
            'client-id: ' . $config->api_key,
            'app-id: ' . $config->auth_token,
            'app-key: ' . $config->api_secret
        ];


        switch ($calling_for) {
            case 'DRIVER_VEHICLE_REGISTRATION':
                $this->vehicle_registration($token, $data);
                break;
            case "DRIVER_REGISTRATION":

                $this->driver_registration($token, $data);
                break;
            case "TRIP_SUBMIT":
                $this->trip_submit($token, $data);
                break;
        }

    }


    public function generate_self_signed_jwt($iss)
    {
        $privateKey = file_get_contents(storage_path('oauth-private.key'));

        $payload = [
            "iss" => $iss, // client id provided by API provider
            "sub" => $iss,
            "iat" => time(),               // issued at
            "nbf" => time(),               // not before
            "exp" => time() + 3600,        // expiry (1 hour)
            "jti" => uniqid()              // unique token id
        ];


        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        return $jwt;
    }







    public function vehicle_registration($token, $data)
    {
        $url = "http://41.59.57.164:8080/api/v1/ticket-engine/rideVehicle/submitVehicle";

        $payload = [
            "vehicleRegistrationNumber" => $data['request']['vehicle_number'],
            "vehicleModel" => $data['request']['vehicle_model_id'],
            "vehicleChasisNumber" => $data['request']['body_number'],
            "vehicleTypeEnum" => $data['request']['vehicle_type'],
            "vehicleRegistrationDate" => $data['request']['vehicle_register_date']
        ];
        
           \Log::channel('latra')->info('payload_vehicle_registration', ['data' => $payload]);


        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: */*",
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);



        curl_close($ch);
        $res = json_decode($response, true);
        \Log::channel('latra')->info('response_vehicle', ['data' => $res]);
        if (!isset($res['statusCode']) || $res['statusCode'] != 5000) {
            $message = $res['message'] ?? 'Vehicle registration failed....';
            throw new \Exception($message);
        }
        return $res;

    }


    public function driver_registration($token, $data)
    {

        $url = "http://41.59.57.164:8080/api/v1/ticket-engine/rideDriver/submitDriver";

        $payload = json_encode([
            "driverName" => $data['driver']['first_name'] ?? 'test',
            "driverEmail" => $data['merchant']['email'] ?? 'test@gmail.com',
            "driverPhone" => $data['driver']['phoneNumber'] ?? '9898989898',
            "driverLicenseNumber" => $data['request']['document_number'] ?? '123456789',
            "licenceCategory" => $data['request']['licenceCategory'],
        ]);
           \Log::channel('latra')->info('payload_driver_registration', ['data' => $payload]);
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: */*',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);

        $res = json_decode($response, true);

        curl_close($ch);
        \Log::channel('latra')->info('response_driver', ['data' => $res]);
        // if (!isset($res['data']['statusCode']) || $res['data']['statusCode'] != 5000) {
        //     $message = $res['data']['message'] ?? 'Driver registration failed..';
        //     throw new \Exception($message);
        // }
        $statusCode = $res['statusCode'] ?? null;

        if ($statusCode != 5000) {

            $message = $res['message'] ?? 'Driver registration failed....';

            // if (isset($res['data']) && is_array($res['data'])) {
            //     $message = implode(', ', $res['data']);
            // }

            throw new \Exception($message);
        }
        return $res;

    }
    public function trip_submit($token, $tripData)
    {
        // \Log::channel('latra')->info('trip_submit_raw', ['tripData' => $tripData]);

        $booking = $tripData['booking'] ?? [];
        $driver = $booking['Driver'] ?? [];
        $bookingDetail = $booking['BookingDetail'] ?? [];
        $driverVehicle = $booking['DriverVehicle'] ?? [];
        $user = $booking['user'] ?? [];

        $rawDocs = $driver['DriverDocument'] ?? collect();

        $firstDocumentId = $rawDocs
            ->filter(function ($doc) {
                return !empty($doc->Document)
                    && ($doc->Document->required_for_third_party_integration ?? 0) == 1;
            })
            ->pluck('document_id')
            ->first();


        $licenceNumber = null;
        if ($firstDocumentId && !empty($driver['id'])) {
            $licenceNumber = DriverDocument::where('document_id', $firstDocumentId)
                ->where('driver_id', $driver['id'])
                ->value('document_number');
        }

        $originCoords = ($bookingDetail['start_latitude'] ?? '') . ',' . ($bookingDetail['start_longitude'] ?? '');
        $endCoords = ($bookingDetail['end_latitude'] ?? '') . ',' . ($bookingDetail['end_longitude'] ?? '');
        $startTime = !empty($bookingDetail['start_timestamp']) ? date("Y-m-d H:i:s", $bookingDetail['start_timestamp']) : null;
        $endTime = !empty($bookingDetail['end_timestamp']) ? date("Y-m-d H:i:s", $bookingDetail['end_timestamp']) : null;

        $payload = [
            "tripId" => (string) ($tripData['request']['booking_id'] ?? ''),
            "originCoordinates" => $originCoords,
            "endCoordinates" => $endCoords,
            "startTime" => $startTime,
            "endTime" => $endTime,
            "totalFareAmount" => (float) 22,
            "tripDistance" => (int) (floatval($booking['travel_distance'] ?? 0) * 1000),
            "rating" => (int) ($driver['rating'] ?? 0),
            "driverEarning" => (float) ($driver['total_earnings'] ?? 0),
            "driverLicenseNumber" => $licenceNumber ?? 9999999999,
            "vehicleRegistrationNumber" => $driverVehicle['vehicle_number'] ?? '',
            "passengerName" => $user['first_name'] ?? '',
            "passengerPhone" => $user['UserPhone'] ?? '',
            "passengerNida" => null,
            "pickupPoint" => $booking['pickup_location'] ?? '',
            "dropOffPoint" => $booking['drop_location'] ?? '',
        ];

        \Log::channel('latra')->info('trip_submit_payload', ['payload' => $payload]);

        $ch = curl_init("http://41.59.57.164:8080/api/v1/ticket-engine/rideHailing/submitTrip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: */*",
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("cURL error during Latra trip submit");
        }

        $res = json_decode($response, true);
        \Log::channel('latra')->info('response_trip', ['data' => $res]);

        if (($res['statusCode'] ?? null) != 5000) {
            throw new \Exception($res['message'] ?? 'Latra end trip details not sent');
        }

        return $res;
    }


}

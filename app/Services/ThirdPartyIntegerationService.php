<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ThirdPartyIntegerationService
{
    public function processEndBookingIntegrations($request, $booking, $driver = null, $merchant = null, $calling_for = null)
    {
        $integration = new \App\Http\Controllers\Integrations\IntegrationController();
        $config = $booking->Merchant->IntegrationConfiguration ?? null;
        $results = [];

        if (!$config) {
            return $results;
        }

        if ($config->wasl_integration == 1) {
            $results['WASL'] = $this->safeCall($integration, 'WASL', [
                'request'     => $request,
                'booking'     => $booking,
                'calling_for' => $calling_for ?? 'DISPATCHING_TRIPS',
            ]);
        }

        if ($config->sapo_api_enable == 1 && $booking->segment_id == 2) {
            $results['SAPO_API'] = $this->safeCall($integration, 'SAPO_API', [
                'request'     => $request,
                'booking'     => $booking,
                'calling_for' => $calling_for ?? 'FINAL_TRACKING',
            ]);
        }

        if ($config->latra_api_enable == 1) {
            $results['LATRA'] = $this->safeCall($integration, 'LATRA', [
                'request'     => $request instanceof \Illuminate\Http\Request ? $request->all() : $request,
                'driver'      => $driver,
                'merchant'    => $merchant,
                'booking'     => $booking,
                'calling_for' => $calling_for ?? 'TRIP_SUBMIT',
            ]);
        }

        return $results;
    }

    private function safeCall($integration, string $slug, array $payload)
    {
        try {
            return $integration->proceedThirdPartyIntegrations($slug, $payload);
        } catch (\Exception $e) {
            \Log::error("Integration {$slug} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Flight Radar Api Start
     */
    public function flightradar24(array $payload): array
    {
        $originalRequest = $payload['request'] ?? null;

        $requestData = $originalRequest instanceof \Illuminate\Http\Request
            ? $originalRequest->all()
            : ($originalRequest ?? $payload);

        if (empty($requestData['flight_iata'])) {
            throw new \InvalidArgumentException('flight_iata is required');
        }

        $driver = null;
        if (($requestData['type'] ?? null) == 2 && $originalRequest instanceof \Illuminate\Http\Request) {
            $driver = $originalRequest->user('api-driver');
        }

        $flightIata = $requestData['flight_iata'];
        $apiKey = $payload['config']->api_key ?? env('FR24_API_KEY');

        if (empty($apiKey)) {
            throw new \Exception('Flightradar24 API key not configured');
        }

        // Resolve timezone: driver's country timezone if available, else app default
        $timezone = $driver->CountryArea->timezone ?? config('app.timezone', 'UTC');

        $summary = $this->getFlightSummary($apiKey, $flightIata, $timezone);

        if (!$summary) {
            throw new \Exception('No flight data found for today');
        }

        $result = [
            'flight'          => $summary['flight'] ?? "",
            'callsign'        => $summary['callsign'] ?? "",
            'aircraft_type'   => $summary['type'] ?? "",
            'registration'    => $summary['reg'] ?? "",
            'flight_status'   => $this->mapFr24Status($summary),
            'category'        => $summary['category'] ?? "",
            'departure'       => [
                'iata'         => $summary['orig_iata'] ?? "",
                'icao'         => $summary['orig_icao'] ?? "",
                'actual'       => $summary['datetime_takeoff'] ?? "",
                'actual_local' => $this->toTimezone($summary['datetime_takeoff'] ?? "", $timezone),
                'runway'       => $summary['runway_takeoff'] ?? "",
            ],
            'arrival'         => [
                'iata'         => $summary['dest_iata'] ?? "",
                'icao'         => $summary['dest_icao'] ?? "",
                'actual'       => $summary['datetime_landed'] ?? "",
                'actual_local' => $this->toTimezone($summary['datetime_landed'] ?? "", $timezone),
                'runway'       => $summary['runway_landed'] ?? "",
                'eta'          => "",
                'eta_local'    => "",
            ],
            'distance' => [
                'actual' => $summary['actual_distance'] ?? "",
                'direct' => $summary['circle_distance'] ?? "",
            ],
            'live' => [
                'latitude'       => "",
                'longitude'      => "",
                'altitude'       => "",
                'ground_speed'   => "",
                'vertical_speed' => "",
                'heading'        => "",
                'squawk'         => "",
                'is_airborne'    => !($summary['flight_ended'] ?? true),
            ],
            'airline_name'  => $this->getAirlineName($apiKey, $summary['operating_as'] ?? '') ?? $summary['operating_as'],
        ];

        if ($result['live']['is_airborne'] && !empty($summary['hex'])) {
            $liveData = $this->getLivePosition($apiKey, $summary['hex'], $flightIata);
            if ($liveData) {
                $result['live'] = array_merge($result['live'], [
                    'latitude'       => $liveData['lat'] ?? "",
                    'longitude'      => $liveData['lon'] ?? "",
                    'altitude'       => $liveData['alt'] ?? "",
                    'ground_speed'   => $liveData['gspeed'] ?? "",
                    'vertical_speed' => $liveData['vspeed'] ?? "",
                    'heading'        => $liveData['track'] ?? "",
                    'squawk'         => $liveData['squawk'] ?? "",
                ]);

                $result['arrival']['eta'] = $liveData['eta'] ?? "";
                $result['arrival']['eta_local'] = $this->toTimezone($liveData['eta'] ?? "", $timezone);
            }
        }

        return $result;
    }

    private function getFlightSummary(string $apiKey, string $flightIata, string $timezone): ?array
    {
        $startLocal = Carbon::now($timezone)->startOfDay();
        $endLocal   = Carbon::now($timezone)->endOfDay();

        $startUtc = $startLocal->clone()->timezone('UTC')->format('Y-m-d\TH:i:s');
        $endUtc   = $endLocal->clone()->timezone('UTC')->format('Y-m-d\TH:i:s');

        $response = Http::withHeaders([
            'Accept'         => 'application/json',
            'Accept-Version' => 'v1',
            'Authorization'  => 'Bearer ' . $apiKey,
        ])->get('https://fr24api.flightradar24.com/api/flight-summary/full', [
            'flights'              => $flightIata,
            'flight_datetime_from' => $startUtc,
            'flight_datetime_to'   => $endUtc,
        ]);

        if (!$response->successful()) {
            \Log::warning('FR24 flight-summary failed: ' . $response->body());
            return null;
        }

        $flights = $response->json('data') ?? [];

        return $flights[0] ?? null;
    }

    private function getLivePosition(string $apiKey, string $hex, string $flightIata): ?array
    {
        $response = Http::withHeaders([
            'Accept'         => 'application/json',
            'Accept-Version' => 'v1',
            'Authorization'  => 'Bearer ' . $apiKey,
        ])->get('https://fr24api.flightradar24.com/api/live/flight-positions/full', [
            'hex'     => $hex,
            'flights' => $flightIata,
        ]);

        if (!$response->successful()) {
            \Log::warning('FR24 live-position failed: ' . $response->body());
            return null;
        }

        $positions = $response->json('data') ?? [];

        return $positions[0] ?? null;
    }

    private function toTimezone(?string $utcDatetime, string $timezone): ?string
    {
        if (empty($utcDatetime)) {
            return "";
        }

        try {
            return Carbon::parse($utcDatetime, 'UTC')
                ->timezone($timezone)
                ->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return "";
        }
    }

    private function mapFr24Status(array $flight): string
    {
        $isEnded = ($flight['flight_ended'] ?? false) === true
            || ($flight['flight_ended'] ?? null) === 'true';

        if ($isEnded && !empty($flight['datetime_landed'])) {
            return 'landed';
        }

        if (!empty($flight['datetime_takeoff']) && empty($flight['datetime_landed'])) {
            return 'active';
        }

        return 'scheduled';
    }

    private function getAirlineName(string $apiKey, string $airlineIcao): ?string
    {
        $response = Http::withHeaders([
            'Accept'         => 'application/json',
            'Accept-Version' => 'v1',
            'Authorization'  => 'Bearer ' . $apiKey,
        ])->get('https://fr24api.flightradar24.com/api/static/airlines/' . $airlineIcao . '/light');

        if (!$response->successful()) {
            return null;
        }

        return $response->json('name') ?? null;
    }

    /**
     * Flight Radar Api End
     */
}
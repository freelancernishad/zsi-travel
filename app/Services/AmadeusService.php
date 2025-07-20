<?php
namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\AmadeusTokenService;

class AmadeusService
{
    /**
     * Call Amadeus API with token auto-fetch
     *
     * @param string $method GET | POST
     * @param string $endpoint
     * @param array|null $body
     * @param array $queryParams
     * @return array
     */
    public static function call(string $method, string $endpoint, array $body = [], array $queryParams = []): array
    {
        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');

        // ✅ Token from service
        $token = app(AmadeusTokenService::class)->getAccessToken();



        Log::info('[AmadeusService] Using access token (partial): ' . Str::limit($token, 40));
        Log::info('[AmadeusService] Endpoint: ' . $endpoint);


        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $url = $baseUrl . $endpoint;

        // ✅ Execute based on method
        $response = match (strtoupper($method)) {
            'POST' => $http->post($url . '?' . http_build_query($queryParams), $body),
            'GET'  => $http->get($url, $queryParams),
            default => throw new \Exception("Unsupported HTTP method: {$method}"),
        };

        if (!$response->successful()) {
            $message = $response->json()['errors'][0]['detail'] ?? 'Unknown Amadeus API error';


            Log::error('[AmadeusService] Failed request', [
                'status' => $response->status(),
                'body' => $response->json(),
                'sent_token' => Str::limit($token, 40),
            ]);


            throw new \Exception("Amadeus API error: {$message}", $response->status());
        }

        return $response->json();
    }

    public static function getAirportDetailsByIata(string $iataCode): ?array
    {
        try {
            $response = self::call(
                'GET',
                '/v1/reference-data/locations',
                [],
                [
                    'subType' => 'AIRPORT',
                    'keyword' => $iataCode
                ]
            );

            return $response['data'][0] ?? null;
        } catch (\Exception $e) {
            // Optional: Log or fallback
            return null;
        }
    }


    public static function getAirlineNameByCode(string $iataCode): ?string
    {
        try {
            $response = self::call(
                'GET',
                '/v1/reference-data/airlines',
                [],
                [
                    'airlineCodes' => $iataCode
                ]
            );

            return $response['data'][0]['commonName'] ?? $iataCode;
        } catch (\Exception $e) {
            // Optional: Log or fallback
            return $iataCode;
        }
    }



}

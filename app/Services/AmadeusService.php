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
        $token = app(AmadeusTokenService::class)->getAccessToken();
        $url = $baseUrl . $endpoint;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        Log::info("[AmadeusService] Calling {$method} {$url}");
        Log::info("[AmadeusService] Request Body", $body);
        Log::info("[AmadeusService] Bearer Token", ['token' => $token]);

        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $response = match (strtoupper($method)) {
            'POST' => $http->post($url, $body),
            'GET'  => $http->get($url),
            default => throw new \Exception("Unsupported HTTP method: {$method}"),
        };

        if (!$response->successful()) {
            $errorBody = $response->json();
            Log::error('[AmadeusService] Request Failed', [
                'status' => $response->status(),
                'error_body' => $errorBody,
                'sent_token' => $token,
                'request_url' => $url,
            ]);

            $message = $errorBody['errors'][0]['detail'] ?? 'Unknown Amadeus API error';
            throw new \Exception("Amadeus API error: {$message}", $response->status());
        }

        Log::info("[AmadeusService] Success Response", $response->json());

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

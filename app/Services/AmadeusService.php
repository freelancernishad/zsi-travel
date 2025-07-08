<?php
namespace App\Services;

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
        $baseUrl = config('AMADEUS_BASE_API', 'https://test.api.amadeus.com');

        // ✅ Token from service
        $token = app(AmadeusTokenService::class)->getAccessToken();

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
            throw new \Exception("Amadeus API error: {$message}", $response->status());
        }

        return $response->json();
    }
}

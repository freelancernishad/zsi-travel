<?php

namespace App\Services;

use Carbon\Carbon;

use App\Models\AmadeusToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AmadeusTokenService
{
    public function getAccessToken()
    {
        $token = AmadeusToken::latest()->first();

        if ($token && $token->expires_at->gt(now()->addMinute())) {
            return $token->access_token;
        }

        $baseUrl = config('AMADEUS_BASE_API', 'https://test.api.amadeus.com');

        $response = Http::asForm()->post("$baseUrl/v1/security/oauth2/token", [
            'client_id' => env('AMADEUS_CLIENT_ID'),
            'client_secret' => env('AMADEUS_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);
        Log::info("$baseUrl/security/oauth2/token");

        if ($response->successful()) {
            $data = $response->json();

            AmadeusToken::create([
                'type' => $data['type'] ?? null,
                'username' => $data['username'] ?? null,
                'application_name' => $data['application_name'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'token_type' => $data['token_type'] ?? null,
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'state' => $data['state'] ?? null,
                'scope' => $data['scope'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return $data['access_token'];
        }

        throw new \Exception('Failed to retrieve Amadeus token');
    }
}

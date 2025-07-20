<?php

namespace App\Services;

use Carbon\Carbon;

use App\Models\AmadeusToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class AmadeusTokenService
{
    public function getAccessToken()
    {
        $token = AmadeusToken::latest()->first();

        if ($token && $token->expires_at->gt(now()->addMinute())) {
            return $token->access_token;
        }

        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');

        $response = Http::asForm()->post("$baseUrl/v1/security/oauth2/token", [
            'client_id' => config('AMADEUS_CLIENT_ID'),
            'client_secret' => config('AMADEUS_CLIENT_SECRET'),
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



    public function refreshAccessToken()
    {
        // Delete all stored tokens to force refresh
        AmadeusToken::truncate();

        // Generate new token immediately and save
        return $this->generateNewToken();
    }

     protected function generateNewToken()
    {
        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');

        $response = Http::asForm()->post("$baseUrl/v1/security/oauth2/token", [
            'client_id' => env('AMADEUS_CLIENT_ID'),
            'client_secret' => env('AMADEUS_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);

        Log::info("Requesting new token from $baseUrl/v1/security/oauth2/token");

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

            Log::info('New Amadeus access token generated.');

            return $data['access_token'];
        }

        throw new Exception('Failed to retrieve Amadeus token');
    }

}

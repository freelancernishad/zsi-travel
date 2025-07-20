<?php

namespace App\Services;

use App\Models\AmadeusToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AmadeusTokenService
{
    public function getAccessToken()
    {
        $token = AmadeusToken::latest()->first();

        if ($token && $token->expires_at && $token->expires_at->gt(now()->addMinute())) {
            Log::info("🔐 Using existing Amadeus token: {$token->access_token}");
            return $token->access_token;
        }

        return $this->generateNewToken();
    }

    public function generateNewToken()
    {
        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');

        Log::info("🔐 Requesting new token from $baseUrl/v1/security/oauth2/token");

        $response = Http::asForm()->post("$baseUrl/v1/security/oauth2/token", [
            'client_id' => config('AMADEUS_CLIENT_ID'),
            'client_secret' => config('AMADEUS_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            AmadeusToken::truncate(); // Clear old tokens

            AmadeusToken::create([
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'expires_at' => now()->addSeconds($data['expires_in']),
                'token_type' => $data['token_type'] ?? null,
                // add other fields if needed
            ]);

            Log::info('✅ New Amadeus token stored.');

            return $data['access_token'];
        }

        Log::error('❌ Failed to retrieve Amadeus token: ' . $response->body());
        throw new Exception('Failed to retrieve Amadeus token');
    }
}

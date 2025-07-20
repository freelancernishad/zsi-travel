<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\FlightBooking;
use App\Models\AmadeusToken;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Exception $e) {
            Log::error("❌ Stripe Webhook signature error: " . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info("✅ Stripe Webhook received: " . $event->type);

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $booking = FlightBooking::where('transaction_id', $session->id)->first();

            if (!$booking) {
                Log::warning("⚠️ Booking not found for session ID: " . $session->id);
                return response()->json(['error' => 'Booking not found'], 404);
            }

            // Step 1: Get token
            $accessToken = $this->getValidAmadeusToken();

            // Step 2: Prepare payload
            $payload = [
                'data' => [
                    'type' => 'flight-order',
                    'flightOffers' => [$booking->flight_offer],
                    'travelers' => $booking->travelers,
                    'contacts' => $booking->contacts,
                    'ticketingAgreement' => [
                        'option' => 'DELAY_TO_CANCEL',
                        'delay' => '6D',
                    ],
                ]
            ];

            $url = config('AMADEUS_BASE_API') . '/v1/booking/flight-orders';
            Log::info("📤 Sending payload to Amadeus:", $payload);

            // Step 3: Attempt booking with token
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            // Step 4: If token invalid, refresh and retry once
            if ($response->unauthorized()) {
                Log::warning("🔁 Token unauthorized, refreshing and retrying...");
                $accessToken = $this->generateAndStoreAmadeusToken(); // force refresh

                $response = Http::withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);
            }

            // Step 5: Final response check
            if ($response->successful()) {
                $amadeusData = $response->json();
                $booking->update([
                    'booking_id' => $amadeusData['data']['id'] ?? null,
                    'amadeus_response' => $amadeusData,
                    'payment_status' => 'success',
                    'transaction_id' => $session->payment_intent,
                ]);

                Log::info("✅ Amadeus booking success", $amadeusData);
            } else {
                Log::error("❌ Amadeus booking failed: " . json_encode($response->json()));
                $booking->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }

    protected function getValidAmadeusToken()
    {
        $token = AmadeusToken::latest()->first();

        if ($token && $token->expires_at->gt(now()->addMinute())) {
            Log::info("🔐 Using existing Amadeus token: {$token->access_token}");
            return $token->access_token;
        }

        return $this->generateAndStoreAmadeusToken();
    }

    protected function generateAndStoreAmadeusToken()
    {
        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');

        $response = Http::asForm()->post("$baseUrl/v1/security/oauth2/token", [
            'client_id' => config('AMADEUS_CLIENT_ID'),
            'client_secret' => config('AMADEUS_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);

        Log::info("🔐 Requesting new token from $baseUrl");

        if (!$response->successful()) {
            Log::error("❌ Failed to fetch new Amadeus token: " . json_encode($response->json()));
            throw new \Exception('Could not generate new Amadeus token.');
        }

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

        Log::info("✅ New Amadeus token stored.");

        return $data['access_token'];
    }
}

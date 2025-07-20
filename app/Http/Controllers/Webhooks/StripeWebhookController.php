<?php

namespace App\Http\Controllers\Webhooks;

use Stripe\Webhook;
use Illuminate\Http\Request;
use App\Models\FlightBooking;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\AmadeusTokenService;
use Illuminate\Support\Facades\Http;

class StripeWebhookController extends Controller
{
    protected AmadeusTokenService $amadeusTokenService;

    public function __construct(AmadeusTokenService $amadeusTokenService)
    {
        $this->amadeusTokenService = $amadeusTokenService;
    }

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

            try {
                $orderPayload = [
                    'data' => [
                        'type' => 'flight-order',
                        'flightOffers' => [$booking->flight_offer],
                        'travelers' => $booking->travelers,
                        'contacts' => $booking->contacts,
                        'ticketingAgreement' => [
                            'option' => 'DELAY_TO_CANCEL',
                            'delay' => '6D',
                        ],
                    ],
                ];

                // $response = $this->sendAmadeusBooking($orderPayload);

                $booking->update([
                    // 'booking_id' => $response['data']['id'] ?? null,
                    // 'amadeus_response' => $response,
                    'payment_status' => 'success',
                    'transaction_id' => $session->payment_intent,
                ]);
            } catch (\Exception $ex) {
                Log::error("❌ Amadeus booking failed: " . $ex->getMessage());
                $booking->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }

    protected function sendAmadeusBooking(array $payload, bool $retry = true)
    {
        $baseUrl = config('AMADEUS_BASE_API', 'https://api.amadeus.com');
        $url = $baseUrl . '/v1/booking/flight-orders';

        // Get token (existing or new)
        $token = $this->amadeusTokenService->getAccessToken();

        Log::info("[AmadeusWebhook] Using Token: $token");
        Log::info("[AmadeusWebhook] Sending POST $url");
        Log::info('[AmadeusWebhook] Request body: ' . json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        Log::info('[AmadeusWebhook] Response status: ' . $response->status());
        Log::info('[AmadeusWebhook] Response body: ' . $response->body());

        if ($response->status() === 401 && $retry) {
            // Token invalid, refresh and retry once
            Log::warning('🔁 Token unauthorized, refreshing and retrying...');
            $this->amadeusTokenService->generateNewToken();

            return $this->sendAmadeusBooking($payload, false);
        }

        if (!$response->successful()) {
            throw new \Exception('Amadeus API error: ' . $response->body());
        }

        return $response->json();
    }
}

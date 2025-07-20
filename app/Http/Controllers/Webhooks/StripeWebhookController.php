<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\FlightBooking;
use App\Http\Controllers\Controller;
use App\Services\AmadeusTokenService;
use Illuminate\Support\Facades\Http;
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

            try {
                $tokenService = new AmadeusTokenService();
                $accessToken = $tokenService->getAccessToken();

                Log::info("[AmadeusWebhook] Using Token: {$accessToken}");

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

                Log::info("📤 Sending payload to Amadeus: ", $payload);

                $url = config('AMADEUS_BASE_API') . '/v1/booking/flight-orders';

                $response = Http::withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                Log::info("[AmadeusWebhook] POST $url");
                Log::info("[AmadeusWebhook] Response status: {$response->status()}");
                Log::info("[AmadeusWebhook] Response body: ", $response->json());

                if ($response->unauthorized()) {
                    throw new \Exception("Amadeus API error: Unauthorized - Invalid token");
                }

                if (!$response->successful()) {
                    throw new \Exception("Amadeus API error: " . json_encode($response->json()));
                }

                $responseData = $response->json();

                $booking->update([
                    'booking_id' => $responseData['data']['id'] ?? null,
                    'amadeus_response' => $responseData,
                    'payment_status' => 'success',
                    'transaction_id' => $session->payment_intent,
                ]);
            } catch (\Exception $e) {
                Log::error("❌ Amadeus Booking failed: " . $e->getMessage());
                $booking->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }
}

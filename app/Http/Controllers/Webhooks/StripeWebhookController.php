<?php

namespace App\Http\Controllers\Webhooks;

use Stripe\Webhook;
use Illuminate\Http\Request;
use App\Models\FlightBooking;
use App\Services\AmadeusService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\AmadeusTokenService;

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
                $pricingData = $booking->flight_offer;
                $travelers = $booking->travelers;
                $contacts = $booking->contacts;

                $orderPayload = [
                    'data' => [
                        'type' => 'flight-order',
                        'flightOffers' => [$pricingData],
                        'travelers' => $travelers,
                        'contacts' => $contacts,
                        'ticketingAgreement' => [
                            'option' => 'DELAY_TO_CANCEL',
                            'delay' => '6D',
                        ],
                    ]
                ];

                Log::info("📤 Sending payload to Amadeus: ", $orderPayload);

                // ✅ Use proper service instance if needed (not static)
                $amadeusService = new AmadeusService();
                $amadeusResponse = $amadeusService->call(
                    'POST',
                    '/v1/booking/flight-orders',
                    $orderPayload
                );

                Log::info("✅ Amadeus booking response: ", $amadeusResponse);

                $booking->update([
                    'booking_id' => $amadeusResponse['data']['id'] ?? null,
                    'amadeus_response' => $amadeusResponse,
                    'payment_status' => 'success',
                    'transaction_id' => $session->payment_intent,
                ]);
            } catch (\Exception $ex) {
                Log::error("❌ Amadeus Booking failed: " . $ex->getMessage());
                $booking->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }

}

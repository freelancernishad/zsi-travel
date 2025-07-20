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
        Log::error("Stripe Webhook error: " . $e->getMessage());
        return response()->json(['error' => 'Invalid signature'], 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;

        // Find existing booking by Stripe session ID
        $booking = FlightBooking::where('transaction_id', $session->id)->first();

        if (!$booking) {
            Log::warning('Booking not found for session ID: ' . $session->id);
            return response()->json(['error' => 'Booking not found'], 404);
        }

        try {

            // Decode stored JSON data if needed
            $pricingData = is_string($booking->flight_offer) ? json_decode($booking->flight_offer, true) : $booking->flight_offer;
            $travelers = is_string($booking->travelers) ? json_decode($booking->travelers, true) : $booking->travelers;
            $contacts = is_string($booking->contacts) ? json_decode($booking->contacts, true) : $booking->contacts;

            Log::info('Preparing Amadeus booking with payload', [
                'flight_offer' => $pricingData,
                'travelers' => $travelers,
                'contacts' => $contacts,
            ]);

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

            // Call Amadeus API - try once, then retry if invalid token
            try {
                $amadeusResponse = AmadeusService::call(
                    'POST',
                    '/v1/booking/flight-orders',
                    $orderPayload
                );
            } catch (\Exception $ex) {
                Log::error('Amadeus Booking failed on first try: ' . $ex->getMessage());

                if (str_contains($ex->getMessage(), 'Invalid access token')) {
                    Log::info('Invalid token detected, refreshing token and retrying once.');

                    // Clear cached token or force refresh here, example:
                    app(AmadeusTokenService::class)->refreshAccessToken();

                    $amadeusResponse = AmadeusService::call(
                        'POST',
                        '/v1/booking/flight-orders',
                        $orderPayload
                    );
                } else {
                    throw $ex; // rethrow other exceptions
                }
            }

            // Update booking with Amadeus response
            $booking->update([
                'booking_id' => $amadeusResponse['data']['id'] ?? null,
                'amadeus_response' => $amadeusResponse,
                'payment_status' => 'success',
                'transaction_id' => $session->payment_intent, // update transaction id from payment_intent
            ]);

            Log::info('Booking updated successfully with Amadeus response', ['booking_id' => $booking->booking_id]);
        } catch (\Exception $ex) {
            Log::error('Amadeus Booking failed: ' . $ex->getMessage());
            $booking->update(['payment_status' => 'failed']);
        }
    }

    return response()->json(['received' => true]);
}

}

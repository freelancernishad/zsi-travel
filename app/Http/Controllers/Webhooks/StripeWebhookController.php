<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\FlightBooking;
use App\Services\AmadeusService;
use App\Http\Controllers\Controller;
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
            Log::error("Stripe Webhook error: " . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // ✅ Find existing booking by session ID
            $booking = FlightBooking::where('transaction_id', $session->id)->first();

            if (!$booking) {
                Log::warning('Booking not found for session ID: ' . $session->id);
                return response()->json(['error' => 'Booking not found'], 404);
            }

            try {
                // Decode stored data from metadata
                $pricingData = $booking->flight_offer;
                $travelers = $booking->travelers;
                $contacts = $booking->contacts;

                // ✅ Prepare Amadeus booking payload
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

                // ✅ Call Amadeus API
                $amadeusResponse = AmadeusService::call(
                    'POST',
                    '/v1/booking/flight-orders',
                    $orderPayload
                );

                // ✅ Update booking
                $booking->update([
                    'booking_id' => $amadeusResponse['data']['id'] ?? null,
                    'amadeus_response' => $amadeusResponse,
                    'payment_status' => 'success',
                    'transaction_id' => $session->payment_intent, // update from intent
                ]);

            } catch (\Exception $ex) {
                Log::error('Amadeus Booking failed: ' . $ex->getMessage());
                $booking->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['received' => true]);
    }
}

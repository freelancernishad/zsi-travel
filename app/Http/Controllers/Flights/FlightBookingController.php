<?php

namespace App\Http\Controllers\Flights;

use Stripe\Stripe;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\FlightBooking;
use App\Models\FlightPricing;
use App\Services\AmadeusService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FlightBookingController extends Controller
{
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_key' => 'required|string|exists:flight_pricings,unique_key',
            'travelers' => 'required|array',
            'contacts' => 'required|array',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            's_url' => 'required|url',
            'f_url' => 'nullable|url',
            'c_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Step 1: Retrieve flight pricing using unique_key
        $flightPricing = FlightPricing::where('unique_key', $validated['unique_key'])->firstOrFail();

        // Step 2: Create booking entry
        $booking = FlightBooking::create([
            'unique_key' => $validated['unique_key'],
            'flight_offer' => $flightPricing->flight_offer_json,
            'travelers' => $validated['travelers'],
            'contacts' => $validated['contacts'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_status' => 'pending',
        ]);

        // Step 3: Create Stripe checkout session
        Stripe::setApiKey(config('STRIPE_SECRET'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $validated['currency'],
                    'product_data' => [
                        'name' => 'Flight Ticket Booking',
                    ],
                    'unit_amount' => $validated['amount'] * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $validated['s_url'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $validated['c_url'],
            'metadata' => [
                'booking_id' => $booking->id,
            ]
        ]);

        // Optional: Store session ID
        $booking->update(['transaction_id' => $session->id,'session_id' => $session->id]);

        return response()->json([
            'url' => $session->url,
        ]);
    }

    public function getBookingByTransactionId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        $booking = FlightBooking::where('session_id', $request->sessionid)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($booking->payment_status !== 'success') {
            return response()->json(['message' => 'Payment not successful'], 400);
        }

        return response()->json([
            'id' => $booking->id,
            'unique_key' => $booking->unique_key,
            'amount' => $booking->amount,
            'currency' => $booking->currency,
            'payment_status' => $booking->payment_status,
            'travelers' => $booking->travelers,
            'contacts' => $booking->contacts,
            'flight_offer' => \App\Http\Resources\FlightOfferPricingResource::collection([$booking->flight_offer]),
            'created_at' => $booking->created_at,
        ]);
    }


}

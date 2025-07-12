<?php

namespace App\Http\Controllers\Flights;

use Stripe\Stripe;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\FlightBooking;
use App\Services\AmadeusService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FlightBookingController extends Controller
{
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pricing_data' => 'required|array',
            'travelers' => 'required|array',
            'contacts' => 'required|array',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Step 1: Store booking info in DB
        $booking = FlightBooking::create([
            'flight_offer' => $validated['pricing_data'],
            'travelers' => $validated['travelers'],
            'contacts' => $validated['contacts'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_status' => 'pending',
        ]);

        // Step 2: Create Stripe Checkout session
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
            'success_url' => url('/booking-success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/booking-cancel'),
            'metadata' => [
                'booking_id' => $booking->id,
            ]
        ]);

        // Optional: store session_id for webhook handling
        $booking->update(['transaction_id' => $session->id]);

        return response()->json([
            'url' => $session->url,
        ]);
    }
}

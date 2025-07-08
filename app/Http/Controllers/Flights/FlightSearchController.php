<?php

namespace App\Http\Controllers\Flights;

use Illuminate\Http\Request;
use App\Services\AmadeusService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlightOfferResource;
use Symfony\Component\HttpFoundation\Response;

class FlightSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $token = $request->header('Amadeus-Token'); // Or use your token logic
            $currency = $request->input('currencyCode', 'USD');
            $max = $request->input('max', 10);
            $tripType = $request->input('trip_type', 'round-trip');

            // ✅ Required
            $tripsString = $request->input('trips');
            $adult = $request->input('adult');

            if (!$tripsString || !$adult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required search parameters',
                ], 400);
            }

            // ✅ Parse trips
            $parsedTrips = collect(explode(';', $tripsString))
                ->map(function ($trip) {
                    return explode(',', $trip);
                })
                ->filter(function ($parts) {
                    return count($parts) === 3 && $parts[0] && $parts[1] && $parts[2];
                })
                ->map(function ($parts) {
                    return [
                        'from' => $parts[0],
                        'to' => $parts[1],
                        'date' => $parts[2],
                    ];
                })
                ->values();

            if ($parsedTrips->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid trips provided',
                ], 400);
            }

            // ✅ Normalize travel class
            $normalizedClass = $this->normalizeTravelClass($request->input('travelClass'));

            // ✅ Fetch from Amadeus
            $queryParams = [
                'originLocationCode'      => optional($parsedTrips[0])['from'],
                'destinationLocationCode' => optional($parsedTrips[0])['to'],
                'departureDate'           => optional($parsedTrips[0])['date'],
                'returnDate'              => $request->input('returnDate'),
                'adults'                  => $adult,
                'children'                => $request->input('children'),
                'infants'                 => $request->input('infants'),
                'travelClass'             => $normalizedClass,
                'currencyCode'            => $currency,
                'max'                     => $max,
            ];

            $queryParams = array_filter($queryParams, fn($v) => !is_null($v) && $v !== '');

            $response = AmadeusService::call(
                'GET',
                '/v2/shopping/flight-offers',
                [],
                $queryParams
            );

            $offers = $response['data'] ?? [];

            // ✅ Format using Resource
            $formatted = FlightOfferResource::collection($offers);

            // ✅ Filter fields
            $fields = $request->input('fields');
            $selectedFields = null;

            if ($fields) {
                if (is_array($fields)) {
                    $selectedFields = collect($fields)->flatMap(fn($f) => explode(',', $f))->map('trim')->toArray();
                } else {
                    $selectedFields = explode(',', $fields);
                }

                $formatted = $formatted->map(function ($flight) use ($selectedFields) {
                    return collect($flight)->only($selectedFields);
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Flight offers fetched successfully',
                'payload' => [
                    'total' => count($formatted),
                    'formatted' => $formatted,
                ],
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Flight Search Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Flight search failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function normalizeTravelClass($class)
    {
        return match (strtolower($class)) {
            'economy', 'eco' => 'ECONOMY',
            'business', 'biz' => 'BUSINESS',
            'first' => 'FIRST',
            'premium' => 'PREMIUM_ECONOMY',
            default => null,
        };
    }










   public function pricing(Request $request)
{
    try {
        $rawInput = $request->input('full_offer_encoded');

        if (!$rawInput) {
            return response()->json([
                'success' => false,
                'message' => 'Missing encoded flight offer data.',
            ], 400);
        }

        // Check if it's base64 or raw JSON
        if (is_string($rawInput)) {
            $decoded = base64_decode($rawInput, true);
            $flightOffer = json_decode($decoded, true);
        } elseif (is_array($rawInput)) {
            $flightOffer = $rawInput; // Already JSON-decoded array
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid flight offer data format.',
            ], 422);
        }




        if (json_last_error() !== JSON_ERROR_NONE || !is_array($flightOffer)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid flight offer data.',
            ], 422);
        }

        $payload = [
            'data' => [
                'type' => 'flight-offers-pricing',
                'flightOffers' => [$flightOffer],
            ]
        ];

        // Call Amadeus API
        $response = AmadeusService::call(
            'POST',
            '/v1/shopping/flight-offers/pricing',
            $payload
        );

        return response()->json([
            'success' => true,
            'message' => 'Pricing successful',
            'payload' => $response,
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Flight pricing failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Flight pricing failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}




}

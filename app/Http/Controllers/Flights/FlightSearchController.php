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
        $token = $request->header('Amadeus-Token'); // Optional: if your service needs it
        $currency = $request->input('currencyCode', 'USD');
        $max = $request->input('max', 10);
        $tripType = $request->input('trip_type', 'oneWay');
        $tripsString = $request->input('trips');
        $adult = $request->input('adult');

        if (!$tripsString || !$adult) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required search parameters',
            ], 400);
        }

        // Parse trips
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

        // Normalize travel class
        $normalizedClass = $this->normalizeTravelClass($request->input('travelClass'));

        // Detect round-trip
        $isRoundTrip = $parsedTrips->count() === 2 &&
                       $parsedTrips[0]['from'] === $parsedTrips[1]['to'] &&
                       $parsedTrips[0]['to'] === $parsedTrips[1]['from'];

        // One-way or round-trip (GET)
        if ($parsedTrips->count() === 1 || $isRoundTrip) {
            $queryParams = [
                'originLocationCode'      => $parsedTrips[0]['from'],
                'destinationLocationCode' => $parsedTrips[0]['to'],
                'departureDate'           => $parsedTrips[0]['date'],
                'adults'                  => $adult,
                'currencyCode'            => $currency,
                'max'                     => $max,
            ];

            if ($isRoundTrip) {
                $queryParams['returnDate'] = $parsedTrips[1]['date'];
            }

            if ($request->filled('children')) {
                $queryParams['children'] = $request->input('children');
            }
            if ($request->filled('infants')) {
                $queryParams['infants'] = $request->input('infants');
            }
            if ($normalizedClass) {
                $queryParams['travelClass'] = $normalizedClass;
            }

            $response = AmadeusService::call(
                'GET',
                '/v2/shopping/flight-offers',
                [],
                array_filter($queryParams)
            );
        }
        // Multi-city (POST)
        else {
            $travelers = [];

            for ($i = 1; $i <= (int)$adult; $i++) {
                $travelers[] = ['id' => (string)$i, 'travelerType' => 'ADULT'];
            }

            $start = count($travelers) + 1;

            for ($i = 0; $i < (int)$request->input('children', 0); $i++) {
                $travelers[] = ['id' => (string)($start + $i), 'travelerType' => 'CHILD'];
            }

            $start = count($travelers) + 1;

            for ($i = 0; $i < (int)$request->input('infants', 0); $i++) {
                $travelers[] = ['id' => (string)($start + $i), 'travelerType' => 'HELD_INFANT'];
            }

            if ($normalizedClass) {
                foreach ($travelers as &$traveler) {
                    $traveler['cabinRestrictions'] = [[
                        'cabin' => $normalizedClass,
                        'coverage' => 'MOST_SEGMENTS',
                        'originDestinationIds' => $parsedTrips->keys()->map(fn($k) => (string)($k + 1))->toArray(),
                    ]];
                }
            }

            $body = [
                'currencyCode' => $currency,
                'originDestinations' => $parsedTrips->map(function ($trip, $i) {
                    return [
                        'id' => (string)($i + 1),
                        'originLocationCode' => $trip['from'],
                        'destinationLocationCode' => $trip['to'],
                        'departureDateTimeRange' => ['date' => $trip['date']],
                    ];
                })->toArray(),
                'travelers' => $travelers,
                'sources' => ['GDS'],
                'max' => $max,
            ];

            $response = AmadeusService::call(
                'POST',
                '/v2/shopping/flight-offers',
                [],
                [],
                $body
            );
        }

        $offers = $response['data'] ?? [];
        $formatted = FlightOfferResource::collection($offers);

        // Optional: filter fields
        $fields = $request->input('fields');
        if ($fields) {
            $selectedFields = is_array($fields)
                ? collect($fields)->flatMap(fn($f) => explode(',', $f))->map('trim')->toArray()
                : explode(',', $fields);

            $formatted = $formatted->map(fn($flight) => collect($flight)->only($selectedFields));
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

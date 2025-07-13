<?php

namespace App\Http\Controllers\Flights;

use Illuminate\Http\Request;
use App\Models\FlightPricing;
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
        $token = $request->header('Amadeus-Token'); // Optional
        $currency = $request->input('currencyCode', 'USD');
        $max = $request->input('max', 10);
        $adult = $request->input('adult');
        $children = $request->input('children');
        $infants = $request->input('infants');
        $travelClass = $request->input('travelClass');
        $returnDate = $request->input('returnDate');
        $tripTypeInput = $request->input('trip_type');
        $tripsString = $request->input('trips');

        if (!$tripsString || !$adult) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required search parameters',
            ], 400);
        }

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

        $tripType = $tripTypeInput;
        $tripCount = $parsedTrips->count();
        if (!$tripType || !in_array($tripType, ['one-way', 'round-trip', 'multi-destination'])) {
            $tripType = match (true) {
                $tripCount === 1 => 'one-way',
                $tripCount === 2 => 'round-trip',
                $tripCount > 2 => 'multi-destination',
                default => 'unknown',
            };
        }

        $normalizedClass = $this->normalizeTravelClass($travelClass);

        // ✅ Prepare common traveler data
        $travelers = [
            [
                'id' => '1',
                'travelerType' => 'ADULT',
                'cabinRestrictions' => [
                    [
                        'cabin' => strtoupper($normalizedClass),
                        'coverage' => 'MOST_SEGMENTS',
                        'originDestinationIds' => $parsedTrips->pluck('id')->all(),
                    ],
                ],
            ],
        ];

        // Add children and infants if present
        $id = 2;
        if ($children) {
            for ($i = 0; $i < $children; $i++) {
                $travelers[] = ['id' => (string)$id++, 'travelerType' => 'CHILD'];
            }
        }
        if ($infants) {
            for ($i = 0; $i < $infants; $i++) {
                $travelers[] = ['id' => (string)$id++, 'travelerType' => 'HELD_INFANT'];
            }
        }

        // ✅ Handle trip types differently
        if ($tripType === 'multi-destination') {
            $originDestinations = $parsedTrips->map(function ($trip, $index) {
                return [
                    'id' => (string)($index + 1),
                    'originLocationCode' => $trip['from'],
                    'destinationLocationCode' => $trip['to'],
                    'departureDateTimeRange' => [
                        'date' => $trip['date'],
                    ],
                ];
            })->all();

            $queryParams = [
                'currencyCode' => $currency,
                'originDestinations' => $originDestinations,
                'travelers' => $travelers,
                'sources' => ['GDS'],
                'max' => $max,
            ];

            $response = AmadeusService::call(
                'POST',
                '/v2/shopping/flight-offers',
                $queryParams,
                []
            );
        } else {
            // For one-way and round-trip
            $queryParams = [
                'originLocationCode' => $parsedTrips[0]['from'],
                'destinationLocationCode' => $parsedTrips[0]['to'],
                'departureDate' => $parsedTrips[0]['date'],
                'adults' => $adult,
                'children' => $children,
                'infants' => $infants,
                'travelClass' => $normalizedClass,
                'currencyCode' => $currency,
                'max' => $max,
            ];

            if ($tripType === 'round-trip' && isset($parsedTrips[1]['date'])) {
                $queryParams['returnDate'] = $parsedTrips[1]['date'];
            } elseif ($tripType === 'round-trip' && $returnDate) {
                $queryParams['returnDate'] = $returnDate;
            }

            $queryParams = array_filter($queryParams, fn($v) => !is_null($v) && $v !== '');

            $response = AmadeusService::call(
                'GET',
                '/v2/shopping/flight-offers',
                [],
                $queryParams
            );
        }

        $offers = $response['data'] ?? [];

        // ✅ Format offers
        $formatted = FlightOfferResource::collection($offers);

        // ✅ Optional field filter
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
                'trip_type' => $tripType,
                'formatted' => $formatted,
            ],
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Flight Search Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Flight search failed',
            'error' => $e->getMessage(),
        ], 500);
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

            // Decode input
            if (is_string($rawInput)) {
                $decoded = base64_decode($rawInput, true);
                $flightOffer = json_decode($decoded, true);
            } elseif (is_array($rawInput)) {
                $flightOffer = $rawInput;
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

            /*
            |------------------------------------------------------------------
            | Step 1: Pricing API
            |------------------------------------------------------------------
            */
            $pricingPayload = [
                'data' => [
                    'type' => 'flight-offers-pricing',
                    'flightOffers' => [$flightOffer],
                ],
            ];

            $pricingResponse = AmadeusService::call(
                'POST',
                '/v1/shopping/flight-offers/pricing',
                $pricingPayload
            );

            /*
            |------------------------------------------------------------------
            | Step 2: Seat Map API
            |------------------------------------------------------------------
            */
            $seatMapResponse = null;
            try {
                $seatMapPayload = [
                    'data' => [
                        'type' => 'flight-offers',
                        'flightOffers' => [$flightOffer],
                    ],
                ];

                $seatMapResponse = AmadeusService::call(
                    'POST',
                    '/v1/shopping/seatmaps',
                    $seatMapPayload
                );
            } catch (\Exception $e) {
                Log::warning('SeatMap unavailable: ' . $e->getMessage());
            }

            /*
            |------------------------------------------------------------------
            | Step 3: Ancillary Services (Optional)
            |------------------------------------------------------------------
            */
            $ancillaryResponse = null;
            try {
                $ancillaryResponse = AmadeusService::call(
                    'POST',
                    '/v1/booking/ancillary-services',
                    $seatMapPayload // reused
                );
            } catch (\Exception $e) {
                Log::info('Ancillary API not available: ' . $e->getMessage());
            }

            /*
            |------------------------------------------------------------------
            | Step 4: Store into database
            |------------------------------------------------------------------
            */
            $pricing = FlightPricing::create([
                'full_offer_encoded' => $rawInput,
                'flight_offer_json' => $flightOffer,
                'pricing_response' => $pricingResponse,
                'seatmap_response' => $seatMapResponse,
                'ancillary_response' => $ancillaryResponse,
            ]);

            /*
            |------------------------------------------------------------------
            | Step 5: Final Response
            |------------------------------------------------------------------
            */
            return response()->json([
                'success' => true,
                'message' => 'Flight pricing successful',
                'unique_key' => $pricing->unique_key,
                'pricing_payload' => $pricingResponse,
                'seatmap' => $seatMapResponse,
                'ancillary' => $ancillaryResponse,
                'details' => FlightOfferResource::collection([$flightOffer]),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Flight pricing failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Flight pricing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}

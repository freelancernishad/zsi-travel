<?php

namespace App\Http\Controllers\Hotels;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AmadeusService;

class HotelSearchController extends Controller
{
    /**
     * Get multiple hotel offers by hotelIds, dates, guests, etc.
     * Endpoint: GET /shopping/hotel-offers
     */
    public function getMultiHotelOffers(Request $request)
    {
        $request->validate([
            'hotelIds'      => 'required|string', // comma-separated hotel IDs
            'adults'        => 'required|integer|min:1|max:9',
            'checkInDate'   => 'required|date_format:Y-m-d',
            'checkOutDate'  => 'required|date_format:Y-m-d|after:checkInDate',
            'roomQuantity'  => 'nullable|integer|min:1',
            'paymentPolicy' => 'nullable|string|in:NONE,PREPAID',
            'currency'      => 'nullable|string|size:3',
        ]);

        $query = [
            'hotelIds'     => $request->hotelIds,
            'adults'       => $request->adults,
            'checkInDate'  => $request->checkInDate,
            'checkOutDate' => $request->checkOutDate,
        ];

        if ($request->roomQuantity) {
            $query['roomQuantity'] = $request->roomQuantity;
        }

        if ($request->paymentPolicy) {
            $query['paymentPolicy'] = $request->paymentPolicy;
        }

        if ($request->currency) {
            $query['currency'] = $request->currency;
        }

        try {
            $data = AmadeusService::call('GET', '/v3/shopping/hotel-offers', [], $query);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pricing details for a specific hotel offer
     * Endpoint: GET /shopping/hotel-offers/{offerId}
     */
    public function getOfferPricing($offerId)
    {
        try {
            $data = AmadeusService::call('GET', "/v2/shopping/hotel-offers/{$offerId}");
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




        /**
     * Hotel name autocomplete (suggestions) based on keyword input.
     * Endpoint: GET /v1/reference-data/locations/hotel
     */
    public function hotelSearchWithOffers(Request $request)
{
    // Validate request input
    $request->validate([
        'keyword'       => 'required|string|min:1',
        'adults'        => 'required|integer|min:1|max:9',
        'checkInDate'   => 'required|date_format:Y-m-d',
        'checkOutDate'  => 'required|date_format:Y-m-d|after:checkInDate',
        'roomQuantity'  => 'nullable|integer|min:1',
        'subType'       => 'nullable|string|in:HOTEL_LEISURE,HOTEL_GDS',
        'countryCode'   => 'nullable|string|size:2',
        'lang'          => 'nullable|string|size:2',
        'max'           => 'nullable|integer|min:1|max:20',
    ]);

    // Step 1: Prepare autocomplete query
    $autocompleteQuery = [
        'keyword' => $request->keyword,
        'subType' => $request->subType ?? 'HOTEL_LEISURE',
    ];

    if ($request->countryCode) {
        $autocompleteQuery['countryCode'] = strtoupper($request->countryCode);
    }
    if ($request->lang) {
        $autocompleteQuery['lang'] = strtolower($request->lang);
    }
    if ($request->max) {
        $autocompleteQuery['max'] = $request->max;
    }

    try {
        // Step 2: Get hotelIds from autocomplete
        $autocompleteResponse = AmadeusService::call('GET', '/v1/reference-data/locations/hotel', [], $autocompleteQuery);
        $locations = $autocompleteResponse['data'] ?? [];

        // Step 3: Extract all hotelIds
        $allHotelIds = collect($locations)
            ->pluck('hotelIds')
            ->flatten()
            ->unique()
            ->filter()
            ->implode(',');


        if (empty($allHotelIds)) {
            return response()->json([
                'success' => false,
                'error' => 'No hotel IDs found for the given keyword.'
            ], 404);
        }

        // Step 4: Build offer query
        $offerQuery = [
            // 'hotelIds'     => $allHotelIds,
            'hotelIds'     => "MCLONGHM",
            'adults'       => $request->adults,
            'checkInDate'  => $request->checkInDate,
            'checkOutDate' => $request->checkOutDate,
        ];

        if ($request->roomQuantity) {
            $offerQuery['roomQuantity'] = $request->roomQuantity;
        }

        // Step 5: Get hotel offers in a single API call
        $offersResponse = AmadeusService::call('GET', '/v3/shopping/hotel-offers', [], $offerQuery);

        return response()->json([
            'success' => true,
            'offers'  => $offersResponse['data'] ?? [],
            'meta'    => $offersResponse['meta'] ?? [],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => 'Failed to fetch hotel offers: ' . $e->getMessage(),
        ], 500);
    }
}





}

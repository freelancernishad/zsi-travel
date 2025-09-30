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
}

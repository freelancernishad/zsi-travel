<?php

namespace App\Http\Controllers\Hotels;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AmadeusService;

class HotelListController extends Controller
{
    /**
     * Get Hotels by City
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotelsByCity(Request $request)
    {
        $request->validate([
            'cityCode' => 'required|string|max:3', // e.g. 'NYC'
        ]);

        try {
            $data = AmadeusService::call('GET', '/v1/reference-data/locations/hotels/by-city', [], [
                'cityCode' => $request->cityCode,
                'radius' => $request->input('radius', 20),
                'radiusUnit' => $request->input('radiusUnit', 'KM')
            ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Hotels by Geocode
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotelsByGeocode(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $data = AmadeusService::call('GET', '/v1/reference-data/locations/hotels/by-geocode', [], [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->input('radius', 20),
                'radiusUnit' => $request->input('radiusUnit', 'KM')
            ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Hotels by Hotel IDs
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotelsByIds(Request $request)
    {
        $request->validate([
            'hotelIds' => 'required|array|min:1',
        ]);

        try {
            $data = AmadeusService::call('GET', '/v1/reference-data/locations/hotels/by-hotels', [], [
                'hotelIds' => implode(',', $request->hotelIds),
            ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Flights;

use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Http\Controllers\Controller;
use App\Http\Resources\AirportResource;

class AirportController extends Controller
{
    /**
     * Get list of airports from Amadeus
     */
   public function getAirportList(Request $request)
{
    $query = $request->query('query', 'US');

    try {
        $response = AmadeusService::call(
            'GET',
            'v1/reference-data/locations',
            [],
            [
                'subType' => 'AIRPORT',
                'keyword' => $query,
            ]
        );

        $items = collect($response['data'] ?? [])
            ->filter(fn($item) => $item['type'] === 'location' && !empty($item['iataCode']));

        return AirportResource::collection($items)->response()->setStatusCode(200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


}

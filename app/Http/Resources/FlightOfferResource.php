<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;


class FlightOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        static $total = 0;
        $total += $this->additional['total'] ?? 1;

        Log::info("Flight Offer Resource: ", $this->resource);
        $full_offer_encoded = base64_encode(json_encode($this->resource));

        $firstSegment = $this['itineraries'][0]['segments'][0] ?? [];
        $airlineCode = $firstSegment['carrierCode'] ?? 'XX';

        $itineraryCount = count($this['itineraries'] ?? []);
        $dealType = match (true) {
            $itineraryCount === 1 => 'one-way',
            $itineraryCount === 2 => 'round-trip',
            $itineraryCount > 2 => 'multi-destination',
            default => 'unknown',
        };
        $isOneWay = $dealType === 'one-way';

        return [
            'id' => $this['id'],
            'validatingAirlineCodes' => $this['validatingAirlineCodes'] ?? [],
            'instantTicketingRequired' => $this['instantTicketingRequired'] ?? false,
            'lastTicketingDate' => $this['lastTicketingDate'] ?? null,
            'isOneWay' => $isOneWay,
            'isUpsellOffer' => $this['isUpsellOffer'] ?? false,
            'numberOfSeats' => $this['numberOfBookableSeats'] ?? null,
            'refundable' => $this['refundable'] ?? 'Partially Refundable',
            'airline' => $this['airline_name'] ?? $airlineCode,
            'airline_name' => AmadeusService::getAirlineNameByCode($airlineCode),
            'airlineLogo' => "https://logos.skyscnr.com/images/airlines/favicon/{$airlineCode}.png",
            'price' => (float) $this['price']['total'],
            'basePrice' => (float) $this['price']['base'],
            'grandTotal' => isset($this['price']['grandTotal']) ? (float) $this['price']['grandTotal'] : (float) $this['price']['total'],
            'currency' => $this['price']['currency'] ?? 'USD',
            'fareType' => $this['pricingOptions']['fareType'] ?? [],
            'includedCheckedBagsOnly' => $this['pricingOptions']['includedCheckedBagsOnly'] ?? false,
            'dealType' => $dealType,
            'totalDuration' => $this->calculateTotalDuration($this['itineraries'] ?? []),
            'travelerPricing' => $this->mapTravelerPricing($this['travelerPricings'] ?? []),
            ...$this->formatLegsByType($dealType, $this['itineraries'], $this['travelerPricings'][0]['fareDetailsBySegment'] ?? [],$total),
            'full_offer_encoded' => $full_offer_encoded,
        ];
    }

    private function formatLegsByType($type, $itineraries, $fareDetailsBySegment,$total)
    {
        if ($type === 'multi-destination') {
            return [
                'legs' => collect($itineraries)->map(function ($itinerary, $index) use ($fareDetailsBySegment) {
                    return array_merge(
                        $this->mapItinerary($itinerary, $fareDetailsBySegment),
                        ['label' => 'Flight ' . ($index + 1)]
                    );
                }),
            ];
        }

        return [
            'outbound' => array_merge(
                $this->mapItinerary($itineraries[0] ?? [], $fareDetailsBySegment,$total),
                ['label' => 'Outbound']
            ),
            'return' => $type === 'round-trip'
                ? array_merge(
                    $this->mapItinerary($itineraries[1] ?? [], $fareDetailsBySegment),
                    ['label' => 'Return']
                )
                : null,
        ];
    }

    private function mapTravelerPricing($travelerPricings)
    {
        return collect($travelerPricings)->map(function ($tp) {
            return [
                'travelerId' => $tp['travelerId'],
                'travelerType' => $tp['travelerType'],
                'fareOption' => $tp['fareOption'],
                'totalPrice' => (float) $tp['price']['total'],
                'basePrice' => (float) $tp['price']['base'],
                'fareDetailsBySegment' => collect($tp['fareDetailsBySegment'])->map(function ($seg) {
                    return [
                        'segmentId' => $seg['segmentId'],
                        'brandedFare' => $seg['brandedFareLabel'] ?? null,
                        'fareBasis' => $seg['fareBasis'],
                        'cabin' => $seg['cabin'],
                        'class' => $seg['class'],
                        'amenities' => collect($seg['amenities'] ?? [])->map(function ($a) {
                            return [
                                'type' => $a['amenityType'],
                                'description' => $a['description'],
                                'isChargeable' => $a['isChargeable'],
                            ];
                        }),
                    ];
                }),
            ];
        });
    }

    private function mapItinerary($itinerary, $fareDetailsBySegment,$total=2)
    {




        $segments = $itinerary['segments'] ?? [];
        $first = $segments[0] ?? [];
        $last = end($segments) ?: [];

        return [
            'from' => $first['departure']['iataCode'] ?? null,
            'to' => $last['arrival']['iataCode'] ?? null,
            'departureDate' => $this->formatDate($first['departure']['at'] ?? null),
            'departureTime' => $this->formatTime($first['departure']['at'] ?? null),
            'arrivalDate' => $this->formatDate($last['arrival']['at'] ?? null),
            'arrivalTime' => $this->formatTime($last['arrival']['at'] ?? null),
            'duration' => $this->formatDuration($itinerary['duration'] ?? null),
            'numberOfStops' => count($segments) > 0 ? count($segments) - 1 : 0,
            'nonStop' => count($segments) === 1,
            'segments' => collect($segments)->map(function ($seg) use ($fareDetailsBySegment, $total) {
                $amenities = $this->getSegmentAmenities($fareDetailsBySegment, $seg['id'] ?? null);

                $departureIata = $seg['departure']['iataCode'];
                $arrivalIata = $seg['arrival']['iataCode'];

                $segmentData = [
                    'departureAirport' => $departureIata,
                    'departureTime' => $this->formatTime($seg['departure']['at']),
                    'arrivalAirport' => $arrivalIata,
                    'arrivalTime' => $this->formatTime($seg['arrival']['at']),
                    'terminalFrom' => $seg['departure']['terminal'] ?? null,
                    'terminalTo' => $seg['arrival']['terminal'] ?? null,
                    'flightNumber' => $seg['number'] ?? null,
                    'carrierCode' => $seg['carrierCode'],
                    'operatingCarrier' => $seg['operating']['carrierCode'] ?? $seg['carrierCode'],
                    'aircraftCode' => $seg['aircraft']['code'] ?? null,
                    'duration' => $this->formatDuration($seg['duration']),
                    ...$amenities,
                ];

                // শুধু যদি একটাই ফ্লাইট থাকে, তখন অতিরিক্ত airport details যোগ করবো
                // if ($total == 1) {
                //     $departureDetails = $this->formatAirportDetails($departureIata);
                //     $arrivalDetails = $this->formatAirportDetails($arrivalIata);

                //     $segmentData['departureAirport_full_name'] = $departureDetails['name'];
                //     $segmentData['departureAirport_city_name'] = $departureDetails['cityName'];
                //     $segmentData['departureAirport_country_name'] = $departureDetails['countryName'];

                //     $segmentData['arrivalAirport_full_name'] = $arrivalDetails['name'];
                //     $segmentData['arrivalAirport_city_name'] = $arrivalDetails['cityName'];
                //     $segmentData['arrivalAirport_country_name'] = $arrivalDetails['countryName'];
                // }

                return $segmentData;
            }),
            'stopsDetails' => collect($segments)->slice(1)->values()->map(function ($seg, $index) {
                return [
                    'stopNumber' => $index + 1,
                    'airportCode' => $seg['departure']['iataCode'],
                    'layoverDuration' => $this->formatDuration($seg['duration']),
                    'layoverAirportName' => $seg['departure']['iataCode'],
                ];
            }),
        ];
    }

    private function getSegmentAmenities($fareDetailsBySegment, $segmentId)
    {
        $segment = collect($fareDetailsBySegment)->firstWhere('segmentId', $segmentId);

        return [
            'brandedFare' => $segment['brandedFareLabel'] ?? null,
            'travelClass' => $segment['class'] ?? null,
            'cabin' => $segment['cabin'] ?? null,
            'amenities' => collect($segment['amenities'] ?? [])->map(function ($a) {
                return [
                    'type' => $a['amenityType'],
                    'description' => $a['description'],
                    'chargeable' => $a['isChargeable'],
                ];
            }),
        ];
    }

    private function formatDate($datetime)
    {
        return $datetime ? Carbon::parse($datetime)->format('Y-m-d') : null;
    }

    private function formatTime($datetime)
    {
        return $datetime ? Carbon::parse($datetime)->format('H:i') : null;
    }

    private function formatDuration($duration)
    {
        if (!$duration) return null;
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $duration, $matches);

        $hours = isset($matches[1]) ? $matches[1] : 0;
        $minutes = isset($matches[2]) ? $matches[2] : 0;

        return sprintf('%02dh %02dm', $hours, $minutes);
    }

    private function calculateTotalDuration($itineraries)
    {
        $totalMinutes = 0;

        foreach ($itineraries as $it) {
            preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $it['duration'], $matches);
            $hours = isset($matches[1]) ? (int) $matches[1] : 0;
            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
            $totalMinutes += ($hours * 60 + $minutes);
        }

        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        return sprintf('%02dh %02dm', $totalHours, $remainingMinutes);
    }

    private function formatAirportDetails(string $iataCode): array
    {
        $cacheKey = "airport_info_{$iataCode}";

        // return cache()->remember($cacheKey, now()->addDays(1), function () use ($iataCode) {
            $info = AmadeusService::getAirportDetailsByIata($iataCode);

            Log::info("Fetched airport info for {$iataCode}", ['info' => $info]);
            return $info
                ? [
                    'name' => $info['name'] ?? $iataCode,
                    'cityName' => $info['address']['cityName'] ?? null,
                    'countryName' => $info['address']['countryName'] ?? null,
                ]
                : [
                    'name' => $iataCode,
                    'cityName' => null,
                    'countryName' => null,
                ];
        // });
    }



    //     private function formatAirportDetails(string $iataCode): string
    // {
    //     $cacheKey = "airport_info_{$iataCode}";

    //     return cache()->remember($cacheKey, now()->addDays(1), function () use ($iataCode) {
    //         $info = AmadeusService::getAirportDetailsByIata($iataCode);

    //         Log::info("Fetched airport info for {$iataCode}", ['info' => $info]);
    //         return $info
    //             ? "{$info['name']} ({$info['address']['cityName']})"
    //             : $iataCode;
    //     });
    // }





}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FlightOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstSegment = $this['itineraries'][0]['segments'][0] ?? [];
        $airlineCode = $firstSegment['carrierCode'] ?? 'XX';

        // ✅ Infer deal type and isOneWay based on itinerary count
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
            ...$this->formatLegsByType($dealType, $this['itineraries'], $this['travelerPricings'][0]['fareDetailsBySegment'] ?? []),

            // ✅ Full encoded offer
            'full_offer_encoded' => base64_encode(json_encode($this->resource)),
        ];
    }

    private function formatLegsByType($type, $itineraries, $fareDetailsBySegment)
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
                $this->mapItinerary($itineraries[0] ?? [], $fareDetailsBySegment),
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

    private function mapItinerary($itinerary, $fareDetailsBySegment)
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
            'segments' => collect($segments)->map(function ($seg) use ($fareDetailsBySegment) {
                $amenities = $this->getSegmentAmenities($fareDetailsBySegment, $seg['id'] ?? null);
                return [
                    'departureAirport' => $seg['departure']['iataCode'],
                    'departureTime' => $this->formatTime($seg['departure']['at']),
                    'arrivalAirport' => $seg['arrival']['iataCode'],
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
}

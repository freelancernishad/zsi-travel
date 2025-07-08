<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;

class AirportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $this হচ্ছে একক airport item


        return [
            'city' => $this['name'] ?? '',
            'cityName' => $this['address']['cityName'] ?? '',
            'cityCode' => $this['address']['cityCode'] ?? '',
            'countryCode' => $this['address']['countryCode'] ?? '',
            'stateCode' => $this['address']['stateCode'] ?? '',
            'regionCode' => $this['address']['regionCode'] ?? '',
            'country' => $this['address']['countryName'] ?? '',
            'airport' => ($this['subType'] === 'AIRPORT' || $this['subType'] === 'airport')
                        ? $this['name']
                        : ($this['name'] ? $this['name'] . ' AIRPORT' : ''),
            'code' => $this['iataCode'] ?? '',
        ];
    }
}

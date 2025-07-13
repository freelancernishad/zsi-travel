<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlightPricing extends Model
{
    protected $fillable = [
        'unique_key',
        'full_offer_encoded',
        'flight_offer_json',
        'pricing_response',
        'seatmap_response',
        'ancillary_response',
    ];

    protected $casts = [
        'flight_offer_json' => 'array',
        'pricing_response' => 'array',
        'seatmap_response' => 'array',
        'ancillary_response' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->unique_key = (string) Str::uuid(); // Generates unique key automatically
        });
    }
}

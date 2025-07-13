<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightBooking extends Model
{
    protected $fillable = [
        'unique_key',
        'booking_id',
        'currency',
        'amount',
        'flight_offer',
        'travelers',
        'contacts',
        'amadeus_response',
        'payment_gateway',
        'payment_status',
        'transaction_id',
    ];

    protected $casts = [
        'flight_offer' => 'array',
        'travelers' => 'array',
        'contacts' => 'array',
        'amadeus_response' => 'array',
    ];

    /**
     * Optional: If you're associating bookings with users.
     */
    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
}

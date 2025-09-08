<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Flights\AirportController;
use App\Http\Controllers\Flights\FlightSearchController;
use App\Http\Controllers\Flights\FlightBookingController;
use App\Http\Controllers\Webhooks\StripeWebhookController;



Route::prefix('flights')->group(function () {
    Route::get('/places', [AirportController::class, 'getAirportList']);
    Route::get('/search', [FlightSearchController::class, 'search']);
    Route::post('/search', [FlightSearchController::class, 'searchPostMethod']);
    Route::post('/offers/pricing', [FlightSearchController::class, 'pricing']);

    Route::post('/booking/create-payment', [FlightBookingController::class, 'createPayment']);
    Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
    Route::get('/booking/by-transaction', [FlightBookingController::class, 'getBookingByTransactionId']);

});

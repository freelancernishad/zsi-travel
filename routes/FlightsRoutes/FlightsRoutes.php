<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Flights\AirportController;
use App\Http\Controllers\Flights\FlightSearchController;



Route::prefix('flights')->group(function () {
    Route::get('/places', [AirportController::class, 'getAirportList']);
    Route::get('/search', [FlightSearchController::class, 'search']);
    Route::post('/offers/pricing', [FlightSearchController::class, 'pricing']);

});

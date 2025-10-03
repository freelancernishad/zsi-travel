<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hotels\HotelListController;
use App\Http\Controllers\Hotels\HotelSearchController;


Route::prefix('hotels')->group(function () {
    Route::get('/by-city', [HotelListController::class, 'getHotelsByCity']);
    Route::get('/by-geocode', [HotelListController::class, 'getHotelsByGeocode']);
    Route::get('/by-ids', [HotelListController::class, 'getHotelsByIds']);

    Route::get('/search', [HotelSearchController::class, 'hotelSearchWithOffers']);

});

Route::prefix('hotels')->group(function () {
    Route::get('/offers', [HotelSearchController::class, 'getMultiHotelOffers']);
    Route::get('/offer/{offerId}', [HotelSearchController::class, 'getOfferPricing']);
});

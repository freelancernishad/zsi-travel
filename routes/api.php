<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Server\ServerStatusController;
use App\Http\Controllers\Public\TouristPlacePublicController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\User\PackageAddon\UserPackageAddonController;

// Load InitialRoutes
if (file_exists($userRoutes = __DIR__.'/InitialRoutes/example.php')) {
    require $userRoutes;
}


if (file_exists($userRoutes = __DIR__.'/InitialRoutes/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/InitialRoutes/admins.php')) {
    require $adminRoutes;
}

if (file_exists($FlightsRoutes = __DIR__.'/FlightsRoutes/FlightsRoutes.php')) {
    require $FlightsRoutes;
}




// Load users and admins route files

if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}





if (file_exists($stripeRoutes = __DIR__.'/Gateways/stripe.php')) {
    require $stripeRoutes;
}



Route::get('/server-status', [ServerStatusController::class, 'checkStatus']);






// Route to get all packages with discounts (query params for discount_months)
Route::get('global/packages', [UserPackageController::class, 'index']);

// Route to get a single package by ID with discounts
Route::get('global/package/{id}', [UserPackageController::class, 'show']);

Route::prefix('global/')->group(function () {
    Route::get('package-addons/', [UserPackageAddonController::class, 'index']); // List all addons
    Route::get('package-addons/{id}', [UserPackageAddonController::class, 'show']); // Get a specific addon
});


// 🌐 Public TouristPlace list (all or filtered by category)
Route::get('tourist-places', [TouristPlacePublicController::class, 'index']);

// 🌐 Public single get by name
Route::get('tourist-places/name/{name}', [TouristPlacePublicController::class, 'showByName']);

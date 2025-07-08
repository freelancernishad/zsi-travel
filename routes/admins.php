<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;



Route::middleware(AuthenticateAdmin::class)->group(function () {
    Route::prefix('admin')->group(function () {

    });
});


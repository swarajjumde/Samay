<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes  (har route ke aage /api lagta hai)
|--------------------------------------------------------------------------
| 1) Public      : bina login ke
| 2) auth:sanctum: login ke baad (header ->  Authorization: Bearer <token>)
| 3) admin       : sirf service owner
*/

// ---- 1) Public ------------------------------------------------------------
Route::middleware('throttle:10,1')->group(function () {      // 1 minute mein max 10 koshish
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::get('business', [ServiceController::class, 'business']);
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{service}', [ServiceController::class, 'show']);
Route::get('services/{service}/slots', [ServiceController::class, 'slots']);

// ---- 2) Logged-in users ----------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // ---- 3) Admin (service owner) -------------------------------------------
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', Admin\DashboardController::class);

        Route::get('services', [Admin\ServiceController::class, 'index']);
        Route::post('services', [Admin\ServiceController::class, 'store']);
        Route::put('services/{service}', [Admin\ServiceController::class, 'update']);
        Route::delete('services/{service}', [Admin\ServiceController::class, 'destroy']);

        Route::get('bookings', [Admin\BookingController::class, 'index']);
        Route::patch('bookings/{booking}/status', [Admin\BookingController::class, 'updateStatus']);

        Route::get('customers', [Admin\CustomerController::class, 'index']);
    });
});

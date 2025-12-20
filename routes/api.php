<?php

use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\ApartmentSearchController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

    // Logged-in user info
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (Request $r) => $r->user());
        Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('search')->group(function () {
    Route::get('/apartments', [ApartmentSearchController::class, 'search']);
    Route::get('/cities', [ApartmentSearchController::class, 'getCities']);
    Route::get('/price-range', [ApartmentSearchController::class, 'getPriceRange']);
});

Route::prefix('/apartments')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ApartmentController::class, 'store']);
    Route::get('/{id}', [ApartmentController::class, 'show']);
    Route::put('/{id}', [ApartmentController::class, 'update']);
    Route::delete('/{id}', [ApartmentController::class, 'destroy']);
});

<?php

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

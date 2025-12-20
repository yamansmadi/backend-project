<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('welcome');
});



Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/approve-user/{id}', [AdminController::class, 'approveUser'])->name('admin.approveUser');
    Route::post('/reject-user/{id}', [AdminController::class, 'rejectUser'])->name('admin.rejectUser');
});

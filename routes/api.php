<?php

use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\ApartmentSearchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Logged-in user info
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('search')->group(function () {
    Route::get('/apartments', [ApartmentSearchController::class, 'search']);
    Route::get('/cities', [ApartmentSearchController::class, 'getCities']);
    Route::get('/price-range', [ApartmentSearchController::class, 'getPriceRange']);
});

Route::prefix('/apartments')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ApartmentController::class, 'store']);
    Route::put('/{id}', [ApartmentController::class, 'update']);
    Route::delete('/{id}', [ApartmentController::class, 'destroy']);
});

Route::get('apartments/{id}', [ApartmentController::class, 'show']);
Route::get('apartments', [ApartmentController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {

    // --- روابط المستأجر (Tenant) ---

    // استعراض كافة الحجوزات الخاصة بالمستأجر (الفلترة ستكون داخل الـ Controller)
    Route::get('bookings', [BookingController::class, 'index']);

    // إنشاء حجز جديد، عرض تفاصيل حجز، تعديل، حذف
    Route::apiResource('bookings', BookingController::class)->except(['index']);

    // إلغاء الحجز من قبل المستأجر
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);


    // --- روابط المالك (Owner) ---

    // استعراض الحجوزات الواردة لمالك الشقق
    Route::get('owner/bookings', [BookingController::class, 'ownerBookings']);

    // الموافقة على الحجز أو التعديل
    Route::post('bookings/{booking}/approve', [BookingController::class, 'approve']);

    // رفض طلب الحجز من قبل المالك
    Route::post('bookings/{booking}/reject', [BookingController::class, 'reject']);

    // للمسؤول (Admin): الحذف النهائي من قاعدة البيانات
    Route::delete('bookings/{id}', [BookingController::class, 'destroy']);
});


Route::get('owner/apartments', [ApartmentController::class, 'myApartments'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ratings', [RatingController::class, 'index']);
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{rating}', [RatingController::class, 'update']);
    Route::delete('/ratings/{rating}', [RatingController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    // عرض المفضلات
    Route::get('favorites', [FavoriteController::class, 'index']);
    // إضافة أو حذف (نرسل ID الشقة في الرابط)
    Route::post('apartments/{id}/favorite', [FavoriteController::class, 'toggle']);
});
Route::middleware('auth:sanctum')->group(function () {

    // روابط عامة (لا تحتاج تسجيل دخول) يعني ما بيهم انت مالك او مستأجر
    Route::get('search/apartments', [ApartmentSearchController::class, 'search']);
    Route::get('search/cities', [ApartmentSearchController::class, 'search']);
    Route::get('apartments/{id}', [ApartmentController::class, 'show']);

    // إدارة الشقق للمالك والأدمن
    Route::apiResource('apartments', ApartmentController::class)->except(['index', 'show']);
    Route::get('my-apartments', [ApartmentController::class, 'myApartments']);
    Route::delete('apartments/{id}', [ApartmentController::class, 'destroy']); //فقط من قبل المالك او الادمن
});

<?php

use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\RestaurantMenuController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
            Route::get('/orders/{order}/edit-data', [OrderController::class, 'editData']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::put('/orders/{order}/', [OrderController::class, 'update']);

    Route::get('/restaurants-menus', [RestaurantMenuController::class, 'index']);

});
Route::middleware('auth:sanctum','customer')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);



});
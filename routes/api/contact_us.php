<?php

use App\Http\Controllers\Api\ContactUsController;
use Illuminate\Support\Facades\Route;

Route::prefix('contact-us')->group(function () {

    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::post('/customer', [ContactUsController::class, 'storeCustomer']);
        Route::get('/customer/messages', [ContactUsController::class, 'customerMessages']);

    });

    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('/', [ContactUsController::class, 'index']);
        Route::get('/{contactUs}', [ContactUsController::class, 'show']);
        Route::patch('/{contactUs}/read', [ContactUsController::class, 'markAsRead']);
        Route::patch('/{contactUs}/close', [ContactUsController::class, 'close']);
        Route::post('/{contactUs}/reply', [ContactUsController::class, 'reply']);
    });
});
<?php

use App\Http\Controllers\Api\Admin\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/restaurants/trashed', [RestaurantController::class, 'trashed']);

    Route::apiResource('restaurants', RestaurantController::class);
    Route::get('restaurants/{restaurant}/menu-categories', [RestaurantController::class, 'getByRestaurant']);
    Route::patch('/restaurants/{restaurantId}/restore', [RestaurantController::class, 'restore']);
    Route::delete('/restaurants/{restaurantId}/force-delete', [RestaurantController::class, 'forceDelete']);
});
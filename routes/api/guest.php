<?php

use App\Http\Controllers\Api\Guest\GuestContactUsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Guest\GuestRestaurantController;
use App\Http\Controllers\Api\Guest\GuestMenuCategoryController;
use App\Http\Controllers\Api\Guest\GuestMenuItemController;

// Route::prefix('guest')->group(function () {
//     Route::get('/restaurants-guest', [GuestRestaurantController::class, 'index']);
//     Route::get('/restaurants-guest/{id}', [GuestRestaurantController::class, 'show']);

//     Route::get('/menu-categories-guest', [GuestMenuCategoryController::class, 'index']);
//     Route::get('/menu-categories-guest/{id}', [GuestMenuCategoryController::class, 'show']);

//     Route::get('/menu-items-guest', [GuestMenuItemController::class, 'index']);
//     Route::get('/menu-items-guest/{id}', [GuestMenuItemController::class, 'show']);
// });
// Route::prefix('contact-us')->group(function () {
//     Route::post('/guest', [GuestContactUsController::class, 'store']);
// });
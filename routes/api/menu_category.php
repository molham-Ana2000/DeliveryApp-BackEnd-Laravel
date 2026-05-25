<?php

use App\Http\Controllers\Api\Admin\MenuCategoryController;
use App\Http\Controllers\Api\Admin\MenuItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('menu-categories', MenuCategoryController::class);
        Route::patch('menu-categories/{id}/restore', [MenuCategoryController::class, 'restore']);
        Route::delete('menu-categories/{id}/force-delete', [MenuCategoryController::class, 'forceDelete']);

        Route::apiResource('menu-items', MenuItemController::class);
        Route::patch('menu-items/{id}/restore', [MenuItemController::class, 'restore']);
        Route::delete('menu-items/{id}/force-delete', [MenuItemController::class, 'forceDelete']);
    });
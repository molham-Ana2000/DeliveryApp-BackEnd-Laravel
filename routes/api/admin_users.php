<?php

use App\Http\Controllers\Api\Admin\UserAdminController;
use Illuminate\Support\Facades\Route;

  
Route::middleware('auth:sanctum','admin')->group(function () {
    Route::get('/admin/users', [UserAdminController::class, 'index']);
    Route::post('/admin/users/admin', [UserAdminController::class, 'store']);

    Route::get('/admin/users/trashed', [UserAdminController::class, 'trashed']);
    Route::get('/admin/users/{user}', [UserAdminController::class, 'show']);

    Route::delete('/admin/users/{user}/block', [UserAdminController::class, 'block']);

    Route::patch('/admin/users/{userId}/restore', [UserAdminController::class, 'restore']);

    Route::delete('/admin/users/{userId}/force-delete', [UserAdminController::class, 'forceDelete']);
    Route::put('/admin/users/{user}', [UserAdminController::class, 'update']);

     
});
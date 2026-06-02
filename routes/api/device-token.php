<?php

use App\Http\Controllers\DeviceTokenController;
use App\Models\UserDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

Route::middleware('auth:sanctum')->post('/device-token', [DeviceTokenController::class, 'store']);
Route::post('/test-notification', function (Request $request) {
    $request->validate([
        'token' => ['nullable', 'string'],
        'title' => ['nullable', 'string'],
        'body' => ['nullable', 'string'],
    ]);

    $token = $request->token ?? UserDeviceToken::latest()->value('token');

    if (! $token) {
        return response()->json([
            'message' => 'No device token found',
        ], 404);
    }

    $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create(
            $request->title ?? 'DeliveryApp',
            $request->body ?? 'Firebase notification test from Laravel'
        ));

    app('firebase.messaging')->send($message);

    return response()->json([
        'message' => 'Notification sent',
        'token' => $token,
    ]);
});
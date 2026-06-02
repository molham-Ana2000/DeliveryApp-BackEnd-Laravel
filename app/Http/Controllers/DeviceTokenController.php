<?php

namespace App\Http\Controllers;

use App\Models\UserDeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['nullable', 'string'],
        ]);

        UserDeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()?->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'message' => 'Device token saved',
        ]);
    }
}
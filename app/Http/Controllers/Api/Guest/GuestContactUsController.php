<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Services\Api\Guest\GuestContactUsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuestContactUsController extends Controller
{
    public function __construct(
        private readonly GuestContactUsService $contactUsService
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string'],
            ]);

            $contact = $this->contactUsService->createGuest($validated);

            return response()->json([
                'success' => true,
                'message' => 'Contact message sent successfully.',
                'data' => $contact,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send contact message.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
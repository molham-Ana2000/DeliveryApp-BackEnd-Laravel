<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Services\Api\Guest\GuestRestaurantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GuestRestaurantController extends Controller
{
    public function __construct(
        private readonly GuestRestaurantService $restaurantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $restaurants = $this->restaurantService->list([
                'service_area_id' => $request->query('service_area_id'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Restaurants fetched successfully.',
                'data' => $restaurants,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurants.',
            ], 500);
        }
    }

   public function show(int $id): JsonResponse
{
    try {
        $restaurant = $this->restaurantService->findById($id);

        return response()->json([
            'success' => true,
            'message' => 'Restaurant fetched successfully.',
            'data' => $restaurant,
        ]);
    } catch (ModelNotFoundException) {
        return response()->json([
            'success' => false,
            'message' => 'Restaurant not found or inactive.',
        ], 404);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch restaurant.',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}
}
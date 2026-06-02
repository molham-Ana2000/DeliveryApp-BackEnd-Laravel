<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestaurantRequest;
use App\Http\Requests\Admin\UpdateRestaurantRequest;
use App\Models\Restaurant;
use App\Services\Api\Admin\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $restaurants = $this->restaurantService->list([
                'status' => $request->query('status'),
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

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        try {
            $restaurant = $this->restaurantService->create(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Restaurant created successfully.',
                'data' => $restaurant->load([
                    'creator:id,first_name,last_name,email',
                    'serviceArea:id,name,city,postal_code,country','photo',
                ]),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create restaurant.', 'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Restaurant fetched successfully.',
                'data' => $restaurant->load([
                    'creator:id,name,email',
                  
                    'photo'
                ]),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant.',
            ], 500);
        }
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        try {
            $restaurant = $this->restaurantService->update(
                $restaurant,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Restaurant updated successfully.',
                'data' => $restaurant,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update restaurant.',
            ], 500);
        }
    }

    public function destroy(Restaurant $restaurant): JsonResponse
    {
        try {
            $this->restaurantService->delete($restaurant);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete restaurant.',
            ], 500);
        }
    }
    public function getByRestaurant(Restaurant $restaurant, Request $request): JsonResponse
    {
        try {
            $categories = $this->restaurantService->getByRestaurant(
                $restaurant->id,
                [
                    'is_active' => $request->query('is_active'),
                    'search' => $request->query('search'),
                    'per_page' => $request->query('per_page', 15),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Restaurant menu categories fetched successfully.',
                'data' => $categories,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant menu categories.',
            ], 500);
        }
    }
    public function trashed(Request $request)
    {
        return response()->json(
            $this->restaurantService->trashed($request->all())
        );
    }
    public function restore(int $restaurantId)
    {
        $restaurant = $this->restaurantService->restore($restaurantId);

        return response()->json([
            'message' => 'Restaurant restored successfully.',
            'data' => $restaurant,
        ]);
    }

    public function forceDelete(int $restaurantId)
    {
        $this->restaurantService->forceDelete($restaurantId);

        return response()->json([
            'message' => 'Restaurant permanently deleted successfully.',
        ]);
    }
}
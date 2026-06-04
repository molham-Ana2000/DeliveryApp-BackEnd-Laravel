<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\Api\User\RestaurantMenuService;

class RestaurantMenuController extends Controller
{
    public function __construct(
        protected RestaurantMenuService $restaurantMenuService
    ) {
    }

    public function index()
    {
        $restaurants = $this->restaurantMenuService->getRestaurantsWithMenus();

        return response()->json([
            'success' => true,
            'message' => 'Restaurants menus retrieved successfully.',
            'data' => $restaurants,
        ]);
    }
}
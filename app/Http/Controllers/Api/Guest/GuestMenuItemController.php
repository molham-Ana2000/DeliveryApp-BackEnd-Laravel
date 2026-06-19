<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Services\Api\Guest\GuestMenuItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GuestMenuItemController extends Controller
{
    public function __construct(
        private readonly GuestMenuItemService $menuItemService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $items = $this->menuItemService->list([
                'restaurant_id' => $request->query('restaurant_id'),
                'category_id' => $request->query('category_id'),
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu items fetched successfully.',
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch menu items.',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->menuItemService->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu item fetched successfully.',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found.',
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch menu item.',
            ], 500);
        }
    }
}
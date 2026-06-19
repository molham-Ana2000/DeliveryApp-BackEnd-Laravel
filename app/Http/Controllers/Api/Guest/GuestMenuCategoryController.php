<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Services\Api\Guest\GuestMenuCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GuestMenuCategoryController extends Controller
{
    public function __construct(
        private readonly GuestMenuCategoryService $menuCategoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $categories = $this->menuCategoryService->list([
                'restaurant_id' => $request->query('restaurant_id'),
                'search' => $request->query('search'),
                'search_item' => $request->query('search_item'),
                'per_page' => $request->query('per_page', 15),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu categories fetched successfully.',
                'data' => $categories,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch menu categories.',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->menuCategoryService->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu category fetched successfully.',
                'data' => $category,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Menu category not found.',
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch menu category.',
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Services\Api\Admin\MenuItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly MenuItemService $menuItemService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $items = $this->menuItemService->list([
                'restaurant_id' => $request->query('restaurant_id'),
                'category_id' => $request->query('category_id'),
                'status' => $request->query('status'),
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

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        try {
            $item = $this->menuItemService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'data' => $item,
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
                'message' => 'Failed to create menu item.',
            ], 500);
        }
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        try {
            $item = $this->menuItemService->findById($menuItem->id);

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

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $item = $this->menuItemService->update(
                $menuItem,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully.',
                'data' => $item,
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
                'message' => 'Failed to update menu item.',
            ], 500);
        }
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        try {
            $this->menuItemService->delete($menuItem);

            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu item.',
            ], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $this->menuItemService->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu item restored successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore menu item.',
            ], 500);
        }
    }

    public function forceDelete(int $id): JsonResponse
    {
        try {
            $this->menuItemService->forceDelete($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu item permanently deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete menu item.',
            ], 500);
        }
    }
}
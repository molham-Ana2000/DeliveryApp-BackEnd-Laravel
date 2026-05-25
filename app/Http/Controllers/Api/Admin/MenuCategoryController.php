<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuCategoryRequest;
use App\Http\Requests\Admin\UpdateMenuCategoryRequest;
use App\Models\MenuCategory;
use App\Services\Api\Admin\MenuCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MenuCategoryController extends Controller
{
      public function __construct(
        private readonly MenuCategoryService $menuCategoryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $categories = $this->menuCategoryService->list([
                'restaurant_id' => $request->query('restaurant_id'),
                'is_active' => $request->query('is_active'),
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

    public function store(StoreMenuCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->menuCategoryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Menu category created successfully.',
                'data' => $category,
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
                'message' => 'Failed to create menu category.',
            ], 500);
        }
    }

    public function show(MenuCategory $menuCategory): JsonResponse
    {
        try {
            $category = $this->menuCategoryService->findById($menuCategory->id);

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

    public function update(UpdateMenuCategoryRequest $request, MenuCategory $menuCategory): JsonResponse
    {
        try {
            $category = $this->menuCategoryService->update(
                $menuCategory,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu category updated successfully.',
                'data' => $category,
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
                'message' => 'Failed to update menu category.',
            ], 500);
        }
    }

    public function destroy(MenuCategory $menuCategory): JsonResponse
    {
        try {
            $this->menuCategoryService->delete($menuCategory);

            return response()->json([
                'success' => true,
                'message' => 'Menu category deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu category.',
            ], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $this->menuCategoryService->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu category restored successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore menu category.',
            ], 500);
        }
    }

    public function forceDelete(int $id): JsonResponse
    {
        try {
            $this->menuCategoryService->forceDelete($id);

            return response()->json([
                'success' => true,
                'message' => 'Menu category permanently deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete menu category.',
            ], 500);
        }
    }
    // public function __construct(
    //     private readonly MenuCategoryService $menuCategoryService
    // ) {
    // }

    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $categories = $this->menuCategoryService->list([
    //             'restaurant_id' => $request->query('restaurant_id'),
    //             'is_active' => $request->query('is_active'),
    //             'search' => $request->query('search'),
    //             'per_page' => $request->query('per_page', 15),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu categories fetched successfully.',
    //             'data' => $categories,
    //         ]);
    //     } catch (Throwable $e) {
    //         report($e);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch menu categories.',
    //         ], 500);
    //     }
    // }

    // public function store(StoreMenuCategoryRequest $request): JsonResponse
    // {
    //     try {
    //         $category = $this->menuCategoryService->create(
    //             $request->validated()
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category and items created successfully.',
    //             'data' => $category,
    //         ], 201);
    //     } catch (InvalidArgumentException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 422);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ], 500);
    //     }
    // }

    // public function show(MenuCategory $menuCategory): JsonResponse
    // {
    //     try {
    //         $category = $this->menuCategoryService->findById($menuCategory->id);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category fetched successfully.',
    //             'data' => $category,
    //         ]);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Menu category not found.',
    //         ], 404);
    //     } catch (Throwable $e) {
    //         report($e);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch menu category.',
    //         ], 500);
    //     }
    // }

    // public function update(UpdateMenuCategoryRequest $request, MenuCategory $menuCategory): JsonResponse
    // {
    //     try {
    //         $category = $this->menuCategoryService->update(
    //             $menuCategory,
    //             $request->validated()
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category updated successfully.',
    //             'data' => $category,
    //         ]);
    //     } catch (InvalidArgumentException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 422);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ], 500);
    //     }
    // }

    // public function destroy(MenuCategory $menuCategory): JsonResponse
    // {
    //     try {
    //         $this->menuCategoryService->delete($menuCategory);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category deleted successfully.',
    //         ]);
    //     } catch (Throwable $e) {
    //         report($e);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete menu category.',
    //         ], 500);
    //     }
    // }
    // public function restore(int $id): JsonResponse
    // {
    //     try {
    //         $this->menuCategoryService->restore($id);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category restored successfully.',
    //         ]);
    //     } catch (Throwable $e) {
    //         report($e);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to restore menu category.',
    //         ], 500);
    //     }
    // }

    // public function forceDelete(int $id): JsonResponse
    // {
    //     try {
    //         $this->menuCategoryService->forceDelete($id);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Menu category permanently deleted successfully.',
    //         ]);
    //     } catch (Throwable $e) {
    //         report($e);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to permanently delete menu category.',
    //         ], 500);
    //     }
    // }
}
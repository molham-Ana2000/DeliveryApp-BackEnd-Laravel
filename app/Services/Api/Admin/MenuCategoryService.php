<?php

namespace App\Services\Api\Admin;

use App\Models\Media;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class MenuCategoryService
{
  public function list(array $filters = []): LengthAwarePaginator
{
    return MenuCategory::query()
        ->with([
            'restaurant:id,name,status',
            'menuItems.media' => function ($query) use ($filters) {
                if (!empty($filters['search_item'])) {
                    $query->where('name', 'like', '%' . $filters['search_item'] . '%');
                }
            }
        ])
        ->when(isset($filters['restaurant_id']), function ($query) use ($filters) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        })
        ->when(isset($filters['is_active']), function ($query) use ($filters) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        })
        ->when(isset($filters['search']), function ($query) use ($filters) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        })
        ->orderBy('sort_order')
        ->latest()
        ->paginate((int) ($filters['per_page'] ?? 15));

        }
    public function findById(int $id): MenuCategory
    {
        $category = MenuCategory::query()
            ->with([
                'restaurant:id,name,status',
                'menuItems.media',
            ])
            ->find($id);

        if (! $category) {
            throw new ModelNotFoundException('Menu category not found.');
        }

        return $category;
    }

    public function create(array $data): MenuCategory
    {
        return DB::transaction(function () use ($data) {
            $restaurant = $this->getActiveRestaurant((int) $data['restaurant_id']);
            $sortOrder = $this->resolveSortOrder(
            $restaurant->id,
            
            isset($data['sort_order']) ? (int) $data['sort_order'] : 0);
    

            $category = MenuCategory::create([
                'restaurant_id' => $restaurant->id,
                'name' => $data['name'],
                'sort_order' => $sortOrder,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['items'] as $itemData) {
                $menuItem = MenuItem::create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'status' => $itemData['status'] ?? 'active',
                ]);

                $this->storeMenuItemImages(
                    $menuItem,
                    $itemData['images'] ?? []
                );
            }

            return $category->fresh([
                'restaurant:id,name,status',
                'menuItems.media',
            ]);
        });
    }

    public function update(MenuCategory $category, array $data): MenuCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $restaurant = $this->getActiveRestaurant((int) $data['restaurant_id']);

            $categoryUpdateData = [];

            if ((int) $category->restaurant_id !== $restaurant->id) {
                $categoryUpdateData['restaurant_id'] = $restaurant->id;
            }

            if ($category->name !== $data['name']) {
                $categoryUpdateData['name'] = $data['name'];
            }

            if (array_key_exists('sort_order', $data)) {
                $requestedSortOrder = (int) $data['sort_order'];

                if ((int) $category->sort_order !== $requestedSortOrder) {
                    $categoryUpdateData['sort_order'] = $this->resolveSortOrder(
                        $restaurant->id,
                        $requestedSortOrder,
                        $category->id
                    );
                }
            }

            if (array_key_exists('is_active', $data)) {
                $requestedIsActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);

                if ((bool) $category->is_active !== $requestedIsActive) {
                    $categoryUpdateData['is_active'] = $requestedIsActive;
                }
            }

            if (! empty($categoryUpdateData)) {
                $category->update($categoryUpdateData);
            }

            if (! empty($data['delete_media_ids'])) {
                $this->deleteMediaByIds($data['delete_media_ids'], $category->id);
            }

            $requestItemIds = [];

            foreach ($data['items'] as $itemData) {
                $menuItem = null;

                if (! empty($itemData['id'])) {
                    $menuItem = MenuItem::query()
                        ->where('id', $itemData['id'])
                        ->where('category_id', $category->id)
                        ->first();
                }

                if ($menuItem) {
                    $itemUpdateData = [];

                    if ((int) $menuItem->restaurant_id !== $restaurant->id) {
                        $itemUpdateData['restaurant_id'] = $restaurant->id;
                    }

                    if ($menuItem->name !== $itemData['name']) {
                        $itemUpdateData['name'] = $itemData['name'];
                    }

                    $description = $itemData['description'] ?? null;

                    if ($menuItem->description !== $description) {
                        $itemUpdateData['description'] = $description;
                    }

                    if ((float) $menuItem->price !== (float) $itemData['price']) {
                        $itemUpdateData['price'] = $itemData['price'];
                    }

                    $status = $itemData['status'] ?? $menuItem->status;

                    if ($menuItem->status !== $status) {
                        $itemUpdateData['status'] = $status;
                    }

                    if (! empty($itemUpdateData)) {
                        $menuItem->update($itemUpdateData);
                    }
                } else {
                    $menuItem = MenuItem::create([
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'status' => $itemData['status'] ?? 'active',
                    ]);
                }

                $requestItemIds[] = $menuItem->id;

                if (! empty($itemData['images'])) {
                    $this->storeMenuItemImages(
                        $menuItem,
                        $itemData['images']
                    );
                }
            }

            if (($data['delete_missing_items'] ?? false) === true) {
                $itemsToDelete = MenuItem::query()
                    ->where('category_id', $category->id)
                    ->whereNotIn('id', $requestItemIds)
                    ->with('media')
                    ->get();

                foreach ($itemsToDelete as $item) {
                    foreach ($item->media as $media) {
                        $this->deleteMediaFile($media);
                        $media->delete();
                    }

                    $item->delete();
                }
            }

            return $category->fresh([
                'restaurant:id,name,status',
                'menuItems.media',
            ]);
        });
    }

    public function delete(MenuCategory $category): void
    {
        DB::transaction(function () use ($category) {
            $category->load('menuItems.media');

            foreach ($category->menuItems  as $item) {
                foreach ($item->media as $media) {
                    $this->deleteMediaFile($media);
                    $media->delete();
                }

                $item->delete();
            }

            $category->delete();
        });
    }

    private function getActiveRestaurant(int $restaurantId): Restaurant
    {
        $restaurant = Restaurant::query()
            ->where('id', $restaurantId)
            ->where('status', 'active')
            ->first();

        if (! $restaurant) {
            throw new InvalidArgumentException('Selected restaurant is inactive or does not exist.');
        }

        return $restaurant;
    }

    private function storeMenuItemImages(MenuItem $menuItem, array $images): void
    {
        foreach ($images as $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }

            if (! $image->isValid()) {
                throw new RuntimeException('One of the uploaded images is invalid.');
            }

            $folder = 'MenuItems';

            if (! Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }

            $path = $image->store($folder, 'public');

            Media::create([
                'menu_item_id' => $menuItem->id,
                'file_name' => $image->getClientOriginalName(),
                'file_path' => $path,
                'file_url' => Storage::disk('public')->url($path),
                'mime_type' => $image->getClientMimeType(),
                'size' => $image->getSize(),
                'type' => 'image',
            ]);
        }
    }
    private function resolveSortOrder(
        int $restaurantId,
        ?int $requestedSortOrder,
        ?int $ignoreCategoryId = null
        ): int 
    {
        $sortOrder = $requestedSortOrder ?? 0;

        if ($sortOrder < 0) {
            $sortOrder = 0;
        }

        while (
            MenuCategory::query()
                ->where('restaurant_id', $restaurantId)
                ->where('sort_order', $sortOrder)
                ->when($ignoreCategoryId, function ($query) use ($ignoreCategoryId) {
                    $query->where('id', '!=', $ignoreCategoryId);
                })
                ->exists()
        ) {
            $sortOrder++;
        }

        return $sortOrder;
    }

    private function deleteMediaByIds(array $mediaIds, int $categoryId): void
    {
        $mediaFiles = Media::query()
            ->whereIn('id', $mediaIds)
            ->whereHas('menuItem', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->get();

        foreach ($mediaFiles as $media) {
            $this->deleteMediaFile($media);
            $media->delete();
        }
    }

    private function deleteMediaFile(Media $media): void
    {
        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
    }
    public function restore(int $id): void
{
    DB::transaction(function () use ($id) {
        $category = MenuCategory::withTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $category->restore();

        $itemIds = MenuItem::withTrashed()
            ->where('category_id', $category->id)
            ->pluck('id');

        MenuItem::withTrashed()
            ->whereIn('id', $itemIds)
            ->restore();

        Media::withTrashed()
            ->whereIn('menu_item_id', $itemIds)
            ->restore();
    });
}

    public function forceDelete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $category = MenuCategory::withTrashed()
                ->where('id', $id)
                ->firstOrFail();

            $category->load(['menuItems.media']);

            foreach ($category->menuItems as $item) {
                foreach ($item->media as $media) {
                    $this->deleteMediaFile($media);
                    $media->delete();
                }

                $item->forceDelete();
            }

            $category->forceDelete();
        });
    }
}
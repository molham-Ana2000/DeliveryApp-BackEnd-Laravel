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

class MenuItemService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return MenuItem::query()
            ->with([
                'restaurant:id,name,status',
                'category:id,restaurant_id,name,is_active',
                'media',
            ])
            ->when(! empty($filters['restaurant_id']), function ($query) use ($filters) {
                $query->where('restaurant_id', $filters['restaurant_id']);
            })
            ->when(! empty($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $query->where(function ($searchQuery) use ($filters) {
                    $searchQuery
                        ->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findById(int $id): MenuItem
    {
        $item = MenuItem::query()
            ->with([
                'restaurant:id,name,status',
                'category:id,restaurant_id,name,is_active',
                'media',
            ])
            ->find($id);

        if (! $item) {
            throw new ModelNotFoundException('Menu item not found.');
        }

        return $item;
    }

    public function create(array $data): MenuItem
    {
        return DB::transaction(function () use ($data) {
            $restaurant = $this->getActiveRestaurant((int) $data['restaurant_id']);

            $categoryId = $data['category_id'] ?? null;

            if ($categoryId) {
                $this->getValidCategory((int) $categoryId, $restaurant->id);
            }

            $menuItem = MenuItem::create([
                'restaurant_id' => $restaurant->id,
                'category_id' => $categoryId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'status' => $data['status'] ?? 'active',
            ]);

            if (isset($data['image'])) {
                $this->storeMenuItemImage($menuItem, $data['image']);
            }

            return $menuItem->fresh([
                'restaurant:id,name,status',
                'category:id,restaurant_id,name,is_active',
                'media',
            ]);
        });
    }
   public function update(MenuItem $menuItem, array $data): MenuItem
{
    return DB::transaction(function () use ($menuItem, $data) {
        $restaurantId = isset($data['restaurant_id'])
            ? (int) $data['restaurant_id']
            : (int) $menuItem->restaurant_id;

        $restaurant = $this->getActiveRestaurant($restaurantId);

        $categoryId = array_key_exists('category_id', $data)
            ? $data['category_id']
            : $menuItem->category_id;

        if ($categoryId) {
            $this->getValidCategory((int) $categoryId, $restaurant->id);
        }

        $itemUpdateData = [];

        if ((int) $menuItem->restaurant_id !== $restaurant->id) {
            $itemUpdateData['restaurant_id'] = $restaurant->id;
        }

        if ((int) $menuItem->category_id !== (int) $categoryId) {
            $itemUpdateData['category_id'] = $categoryId;
        }

        if (array_key_exists('name', $data) && $menuItem->name !== $data['name']) {
            $itemUpdateData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $description = $data['description'] ?? null;

            if ($menuItem->description !== $description) {
                $itemUpdateData['description'] = $description;
            }
        }

        if (array_key_exists('price', $data) && (float) $menuItem->price !== (float) $data['price']) {
            $itemUpdateData['price'] = $data['price'];
        }

        if (array_key_exists('status', $data) && $menuItem->status !== $data['status']) {
            $itemUpdateData['status'] = $data['status'];
        }

        if (! empty($itemUpdateData)) {
            $menuItem->update($itemUpdateData);
        }

        if (isset($data['image'])) {
            $this->replaceMenuItemImage($menuItem, $data['image']);
        }

        return $menuItem->fresh([
            'restaurant:id,name,status',
            'category:id,restaurant_id,name,is_active',
            'media',
        ]);
    });
}

    public function delete(MenuItem $menuItem): void
    {
        DB::transaction(function () use ($menuItem) {
            $menuItem->load('media');

            foreach ($menuItem->media as $media) {
                $media->delete();
            }

            $menuItem->delete();
        });
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $menuItem = MenuItem::withTrashed()
                ->where('id', $id)
                ->firstOrFail();

            $menuItem->restore();

            Media::withTrashed()
                ->where('menu_item_id', $menuItem->id)
                ->restore();
        });
    }


    public function forceDelete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $menuItem = MenuItem::withTrashed()
                ->where('id', $id)
                ->firstOrFail();

            $menuItem->load('media');

            foreach ($menuItem->media as $media) {
                $this->deleteMediaFile($media);
                $media->forceDelete();
            }

            $menuItem->forceDelete();
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

    private function getValidCategory(int $categoryId, int $restaurantId): MenuCategory
    {
        $category = MenuCategory::query()
            ->where('id', $categoryId)
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            throw new InvalidArgumentException('Selected menu category is inactive, invalid, or does not belong to this restaurant.');
        }

        return $category;
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

    private function deleteMediaByIds(array $mediaIds, int $menuItemId): void
    {
        $mediaFiles = Media::query()
            ->whereIn('id', $mediaIds)
            ->where('menu_item_id', $menuItemId)
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
    private function storeMenuItemImage(MenuItem $menuItem, $image): void
{
    Storage::disk('public')->makeDirectory('menu-items');

    $mimeType = $image->getClientMimeType();
    $size = $image->getSize();

    $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

    $path = $image->storeAs('menu-items', $fileName, 'public');

    Media::create([
        'menu_item_id' => $menuItem->id,
        'restaurant_id' => null,
        'file_name' => $fileName,
        'file_path' => $path,
        'file_url' => asset('storage/' . $path),
        'mime_type' => $mimeType,
        'size' => $size,
        'type' => 'image',
    ]);
}

private function replaceMenuItemImage(MenuItem $menuItem, $image): void
{
    Storage::disk('public')->makeDirectory('menu-items');

    $mimeType = $image->getClientMimeType();
    $size = $image->getSize();

    $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

    $path = $image->storeAs('menu-items', $fileName, 'public');

    $oldMedia = $menuItem->media()->first();

    if ($oldMedia) {
        if (
            $oldMedia->file_path &&
            Storage::disk('public')->exists($oldMedia->file_path)
        ) {
            Storage::disk('public')->delete($oldMedia->file_path);
        }

        $oldMedia->update([
            'file_name' => $fileName,
            'file_path' => $path,
            'file_url' => asset('storage/' . $path),
            'mime_type' => $mimeType,
            'size' => $size,
            'type' => 'image',
        ]);

        return;
    }

    Media::create([
        'menu_item_id' => $menuItem->id,
        'restaurant_id' => null,
        'file_name' => $fileName,
        'file_path' => $path,
        'file_url' => asset('storage/' . $path),
        'mime_type' => $mimeType,
        'size' => $size,
        'type' => 'image',
    ]);
}
}
<?php

namespace App\Services\Api\Guest;

use App\Models\MenuCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GuestMenuCategoryService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return MenuCategory::query()
            ->with([
                'restaurant:id,name,status',
                'menuItems' => function ($query) {
                    $query->where('status', 'active')
                        ->with('media');
                },
            ])
            ->where('is_active', true)
            ->whereHas('restaurant', function ($query) {
                $query->where('status', 'active');
            })
            ->when(! empty($filters['restaurant_id']), function ($query) use ($filters) {
                $query->where('restaurant_id', $filters['restaurant_id']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->when(! empty($filters['search_item']), function ($query) use ($filters) {
                $query->whereHas('menuItems', function ($itemQuery) use ($filters) {
                    $itemQuery
                        ->where('status', 'active')
                        ->where('name', 'like', '%' . $filters['search_item'] . '%');
                });
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
                'menuItems' => function ($query) {
                    $query->where('status', 'active')
                        ->with('media');
                },
            ])
            ->where('is_active', true)
            ->whereHas('restaurant', function ($query) {
                $query->where('status', 'active');
            })
            ->find($id);

        if (! $category) {
            throw new ModelNotFoundException('Menu category not found.');
        }

        return $category;
    }
}
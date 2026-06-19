<?php

namespace App\Services\Api\Guest;

use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GuestMenuItemService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return MenuItem::query()
            ->with([
                'restaurant:id,name,status',
                'category:id,restaurant_id,name,is_active',
                'media',
            ])
            ->where('status', 'active')
            ->whereHas('restaurant', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->when(! empty($filters['restaurant_id']), function ($query) use ($filters) {
                $query->where('restaurant_id', $filters['restaurant_id']);
            })
            ->when(! empty($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
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
            ->where('status', 'active')
            ->whereHas('restaurant', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->find($id);

        if (! $item) {
            throw new ModelNotFoundException('Menu item not found.');
        }

        return $item;
    }
}
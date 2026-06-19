<?php

namespace App\Services\Api\Guest;

use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GuestRestaurantService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Restaurant::query()
            ->with([
                'serviceArea:id,name,city,postal_code,country',
                'photo',
            ])
            ->where('status', 'active')
            ->when(! empty($filters['service_area_id']), function ($query) use ($filters) {
                $query->where('service_area_id', $filters['service_area_id']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findById(int $id): Restaurant
    {
        $restaurant = Restaurant::query()
            ->with([
                'serviceArea:id,name,city,postal_code,country',
                'photo',
                'categories' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->with([
                            'menuItems' => function ($itemQuery) {
                                $itemQuery->where('status', 'active')
                                    ->latest()
                                    ->with('media');
                            },
                        ]);
                },
            ])
            ->where('status', 'active')
            ->find($id);

        if (! $restaurant) {
            throw new ModelNotFoundException('Restaurant not found.');
        }

        return $restaurant;
    }
}
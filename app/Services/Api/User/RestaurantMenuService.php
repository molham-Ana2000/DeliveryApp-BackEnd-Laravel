<?php

namespace App\Services\Api\User;

use App\Models\Restaurant;

class RestaurantMenuService
{
    public function getRestaurantsWithMenus()
    {
        return Restaurant::query()
            ->where('status', 'active')
            ->with([
                'photo',
                'categories' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order');
                },
                'categories.menuItems' => function ($query) {
                    $query->where('status', 'active')
                        ->with('media')
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'description' => $restaurant->description,
                    'phone' => $restaurant->phone,
                    'email' => $restaurant->email,
                    'address' => $restaurant->address,
                    'city' => $restaurant->city,
                    'postal_code' => $restaurant->postal_code,
                    'country' => $restaurant->country,
                    'latitude' => $restaurant->latitude,
                    'longitude' => $restaurant->longitude,
                    'status' => $restaurant->status,
                    'opening_time' => optional($restaurant->opening_time)->format('H:i'),
                    'closing_time' => optional($restaurant->closing_time)->format('H:i'),
                    'photo' => $restaurant->photo,
                    'categories' => $restaurant->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'restaurant_id' => $category->restaurant_id,
                            'name' => $category->name,
                            'sort_order' => $category->sort_order,
                            'is_active' => $category->is_active,
                            'items' => $category->menuItems->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'restaurant_id' => $item->restaurant_id,
                                    'category_id' => $item->category_id,
                                    'name' => $item->name,
                                    'description' => $item->description,
                                    'price' => $item->price,
                                    'status' => $item->status,
                                    'media' => $item->media,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();
    }
}
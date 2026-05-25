<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $category = $this->route('menu_category');

        $categoryId = $category?->id;
        $restaurantId = $this->input('restaurant_id') ?? $category?->restaurant_id;

        return [
            'restaurant_id' => [
                'nullable',
                'integer',
                'exists:restaurants,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('menu_categories', 'name')
                    ->where('restaurant_id', $restaurantId)
                    ->whereNull('deleted_at')
                    ->ignore($categoryId),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'items' => [
                'nullable',
                'array',
            ],

            'items.*.id' => [
                'nullable',
                'integer',
                'exists:menu_items,id',
            ],

            'items.*.name' => [
                'required_with:items',
                'string',
                'max:150',
                'distinct',
            ],

            'items.*.description' => [
                'nullable',
                'string',
            ],

            'items.*.price' => [
                'required_with:items',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'items.*.status' => [
                'nullable',
                'string',
                'in:active,inactive',
            ],

            'items.*.images' => [
                'nullable',
                'array',
            ],

            'items.*.images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'delete_media_ids' => [
                'nullable',
                'array',
            ],

            'delete_media_ids.*' => [
                'integer',
                'exists:media,id',
            ],

            'delete_missing_items' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'restaurant_id.exists' => 'Selected restaurant does not exist.',

            'name.unique' => 'This category name already exists for this restaurant.',

            'items.array' => 'Menu items must be an array.',

            'items.*.id.exists' => 'Selected menu item does not exist.',

            'items.*.name.required_with' => 'Menu item name is required.',
            'items.*.name.distinct' => 'Menu item names must be unique in the same request.',

            'items.*.price.required_with' => 'Menu item price is required.',
            'items.*.price.numeric' => 'Menu item price must be a number.',

            'items.*.status.in' => 'Menu item status must be active or inactive.',

            'items.*.images.*.image' => 'Each uploaded file must be an image.',
            'items.*.images.*.mimes' => 'Images must be jpg, jpeg, png, or webp.',
            'items.*.images.*.max' => 'Each image must not be larger than 4MB.',

            'delete_media_ids.array' => 'Deleted media IDs must be an array.',
            'delete_media_ids.*.exists' => 'Selected media file does not exist.',

            'delete_missing_items.boolean' => 'Delete missing items must be true or false.',
        ];
    }
}
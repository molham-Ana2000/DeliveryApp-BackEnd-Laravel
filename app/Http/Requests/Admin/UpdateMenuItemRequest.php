<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $menuItem = $this->route('menu_item');

        $menuItemId = $menuItem?->id;
        $restaurantId = $this->input('restaurant_id') ?? $menuItem?->restaurant_id;

        return [
            'restaurant_id' => [
                'nullable',
                'integer',
                'exists:restaurants,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:menu_categories,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('menu_items', 'name')
                    ->where('restaurant_id', $restaurantId)
                    ->whereNull('deleted_at')
                    ->ignore($menuItemId),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'status' => [
                'nullable',
                'string',
                'in:active,inactive',
            ],

            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'restaurant_id.exists' => 'Selected restaurant does not exist.',

            'category_id.exists' => 'Selected menu category does not exist.',

            'name.unique' => 'This menu item name already exists for this restaurant.',

            'price.numeric' => 'Menu item price must be a number.',

            'status.in' => 'Menu item status must be active or inactive.',

            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Image must be jpg, jpeg, png, or webp.',
            'image.max' => 'Image must not be larger than 4MB.',
        ];
    }
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => [
                'required',
                'integer',
                'exists:restaurants,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:menu_categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('menu_items', 'name')
                    ->where('restaurant_id', $this->input('restaurant_id'))
                    ->whereNull('deleted_at'),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'status' => [
                'nullable',
                'string',
                'in:active,inactive',
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
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
            'restaurant_id.required' => 'Restaurant is required.',
            'restaurant_id.exists' => 'Selected restaurant does not exist.',

            'category_id.exists' => 'Selected menu category does not exist.',

            'name.required' => 'Menu item name is required.',
            'name.unique' => 'This menu item name already exists for this restaurant.',

            'price.required' => 'Menu item price is required.',
            'price.numeric' => 'Menu item price must be a number.',

            'status.in' => 'Menu item status must be active or inactive.',

            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Images must be jpg, jpeg, png, or webp.',
            'images.*.max' => 'Each image must not be larger than 4MB.',
        ];
    }
}
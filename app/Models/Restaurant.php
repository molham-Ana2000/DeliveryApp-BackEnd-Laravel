<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'service_area_id',
        'country',
        'latitude',
        'longitude',
        'status',
        'opening_time',
        'closing_time',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories()
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class);
    }
    public function photo()
    {
        return $this->hasOne(Media::class, 'restaurant_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
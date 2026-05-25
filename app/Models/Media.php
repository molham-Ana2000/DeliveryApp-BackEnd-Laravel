<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_item_id',
        'file_name',
        'file_path',
        'file_url',
        'mime_type',
        'size',
        'type',    
        'restaurant_id',

    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function getUrlAttribute()
    {
        if ($this->file_url) {
            return $this->file_url;
        }

        if ($this->type === 'image' && $this->file_path) {
            return asset('storage/' . $this->file_path);
        }

        return null;
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
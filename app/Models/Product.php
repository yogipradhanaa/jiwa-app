<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'original_price',
        'image_url',
    ];

    protected $appends = ['image_url_text'];

    public function getImageUrlTextAttribute()
    {
        return asset(Storage::url($this->image_url));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'original_price',
        'image_url',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

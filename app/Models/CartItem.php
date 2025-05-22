<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'note',
        'parent_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function children()
    {
        return $this->hasMany(CartItem::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(CartItem::class, 'parent_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'note',
        'parent_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function children()
    {
        return $this->hasMany(OrderItem::class, 'parent_id');
    }
    public function parent()
    {
        return $this->belongsTo(OrderItem::class, 'parent_id');
    }
}

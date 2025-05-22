<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 
        'type'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function isFood()
    {
        return $this->type === 'food';
    }

    public function isDrink()
    {
        return $this->type === 'drink';
    }

    public function isCombo()
    {
        return $this->type === 'combo';
    }
}

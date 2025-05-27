<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReward extends Model
{
    protected $fillable = [
        'order_id',
        'reward_type',
        'value',
        'expired_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

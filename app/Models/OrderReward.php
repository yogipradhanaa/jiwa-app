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

    // Casting agar 'expired_at' otomatis jadi objek Carbon
    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

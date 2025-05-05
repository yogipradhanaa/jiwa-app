<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'address',
        'latitude',
        'longitude',
        'note',
        'recipient_name',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

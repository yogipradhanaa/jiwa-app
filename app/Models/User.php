<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'gender',
        'date_of_birth',
        'region',
        'job',
        'phone_number',
        'otp_code',
        'otp_expires_at',
        'referral_code',
        'referred_by',
        'pin_code',
    ];

    protected $hidden = [
        'otp_code',
        'otp_expires_at',
        'pin_code',
    ];

    // public function setPinCodeAttribute($value)
    // {
    //     $this->attributes['pin_code'] = bcrypt($value); 
    // }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

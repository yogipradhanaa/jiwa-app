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
        'otp_code',
        'otp_expires_at',
        'referral_code',
        'referral_by',
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
    
}

<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        // Endpoint login
        Route::post('login', [AuthController::class, 'login']);
        
        // Endpoint OTP
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        
        // Endpoint registrasi
        Route::post('register', [AuthController::class, 'register']);
        
        // Endpoint untuk membuat PIN
        Route::post('create-pin', [AuthController::class, 'createPin']);

        Route::get('show-otp', [AuthController::class, 'showOtpForm']);
        Route::post('/generate-otp', [AuthController::class, 'generateOtp']);
        
        // Route untuk menampilkan halaman PIN dan registrasi di web
        Route::get('pin-form/{email}', [AuthController::class, 'showPinForm'])->name('auth.pin.form');
        Route::get('otp-form', [AuthController::class, 'showOtpForm'])->name('auth.otp.form');
    });
});

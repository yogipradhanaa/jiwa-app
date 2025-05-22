<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\EditProfileController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserAddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {

        Route::post('login', [AuthController::class, 'login']);

        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

        Route::post('register', [AuthController::class, 'register']);

        Route::post('create-pin', [AuthController::class, 'createPin']);

        Route::post('pin-login', [AuthController::class, 'pinLogin']);

        Route::post('forgot-pin', [AuthController::class, 'sendOtpForResetPin']);

        Route::post('verify-otp-reset-pin', [AuthController::class, 'verifyOtpForResetPin']);

        Route::post('reset-pin', [AuthController::class, 'resetPin']);

        Route::post('/generate-otp', [AuthController::class, 'generateOtp']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);

            Route::post('/edit-profile', [EditProfileController::class, 'update']);
            Route::post('/send-otp-change-pin', [EditProfileController::class, 'sendOTPForChancePin']);
            Route::post('/verify-otp-change-pin', [EditProfileController::class, 'verifyOtpForChangePin']);
            Route::post('/change-pin', [EditProfileController::class, 'changePin']);
            Route::delete('/delete-account', [EditProfileController::class, 'destroy']);

            Route::get('/addresses', [UserAddressController::class, 'index']);
            Route::post('/add-address', [UserAddressController::class, 'store']);
            Route::post('/update-address/{id}', [UserAddressController::class, 'update']);
            Route::delete('/delete-address/{id}', [UserAddressController::class, 'destroy']);

            Route::get('/cart', [CartController::class, 'index']);
            Route::post('/cart', [CartController::class, 'store']);
            Route::delete('/cart/{id}', [CartController::class, 'destroy']);

            Route::get('/order', [OrderController::class, 'index']);
            Route::post('/order', [OrderController::class, 'store']);

            Route::post('/payments', [PaymentController::class, 'generatePayment']);

            Route::get('/referred-friends', [AuthController::class, 'invitedFriends']);
        });

        Route::get('/menus', [MenuController::class, 'index']);
        Route::get('/menus/{id}', [MenuController::class, 'show']);

    });
    Route::post('/payments/callback', [PaymentController::class, 'paymentCallback']);
});
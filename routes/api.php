<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EditProfileController;
use App\Http\Controllers\Api\MenuController;
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

        Route::post('/generate-otp', [AuthController::class, 'generateOtp']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);

            Route::post('/edit-profile', [EditProfileController::class, 'update']);

            Route::get('/addresses', [UserAddressController::class, 'index']); 
            Route::post('/add-address', [UserAddressController::class, 'store']);
            Route::post('/update-address/{id}', [UserAddressController::class, 'update']);
            Route::delete('/delete-address/{id}', [UserAddressController::class, 'destroy']);

            Route::get('/menus', [MenuController::class, 'index']);
            Route::get('/menus/{id}', [MenuController::class, 'show']);
        });
    });
});

<?php

namespace App\Http\Controllers\Api;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EditProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();


        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['required', 'date'],
            'region' => ['required', 'string', 'max:255'],
            'job' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],

        ]);

        $user->fill($request->only([
            'name',
            'gender',
            'date_of_birth',
            'region',
            'job',
            'phone_number',
        ]));

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user,
        ]);
    }
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dihapus.',
        ]);
    }

    public function sendOTPForChancePin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

         $otpCode = rand(1000, 9999);

        Mail::to($request->email)->send(new OtpMail($otpCode));

        OtpCode::updateOrCreate(
            ['email' => $request->email],
            [
                'otp_code' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes(2),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP berhasil dikirim ke email.',
        ]);
    }
    public function verifyOtpForChangePin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        $otp = OtpCode::where('email', $request->email)
            ->where('otp_code', $request->otp)
            ->first();

        if ($otp && $otp->expires_at > now()) {
            return response()->json([
                'status' => 'success',
                'message' => 'OTP valid, silakan lanjutkan ubah PIN.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'OTP tidak valid atau sudah kadaluarsa.',
        ], 400);
    }

    public function changePin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'new_pin_code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $request->email)->first();

        $user->pin_code = Hash::make($request->new_pin_code);
        $user->save();

        // Hapus OTP setelah berhasil digunakan
        OtpCode::where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'PIN berhasil diubah.',
        ]);
    }
}
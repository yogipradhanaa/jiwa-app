<?php

namespace App\Http\Controllers\Api;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    // Login atau verifikasi email
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Cek apakah email terdaftar
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Jika email terdaftar, arahkan ke halaman untuk memasukkan PIN
            return response()->json([
                'message' => 'Email sudah terdaftar. Silakan lanjutkan untuk memasukkan PIN.',
                'redirect' => route('auth.pin.form', ['email' => $request->email])
            ]);
        } else {
            // Jika email belum terdaftar, generate OTP
            $otpCode = $this->generateOtp();

            // Kirim OTP via email
            Mail::to($request->email)->send(new OtpMail($otpCode));

            // Simpan OTP dalam database dengan waktu kadaluarsa 2 menit
            $otp = OtpCode::updateOrCreate(
                ['email' => $request->email],  // Kondisi pencarian berdasarkan email
                [
                    'otp_code' => $otpCode,
                    'expires_at' => Carbon::now()->addMinutes(2)  // OTP berlaku 10 menit
                ]
            );

            // Arahkan ke halaman OTP
            return response()->json([
                'message' => 'Email belum terdaftar. Silakan lanjutkan ke halaman OTP untuk registrasi.',
                'redirect' => route('auth.otp.form')
            ]);
        }
    }

    // Fungsi untuk generate OTP 4 digit
    private function generateOtp()
    {
        return rand(1000, 9999);  // Generate OTP 4 digit
    }

    // Verifikasi OTP yang dikirim ke email
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:4',
            'email' => 'required|email',
        ]);

        // Cari OTP yang sesuai dengan email dan kode OTP yang dimasukkan
        $otp = OtpCode::where('email', $request->email)
            ->where('otp_code', $request->otp)
            ->first();

        // Cek apakah OTP ditemukan dan belum kadaluarsa
        if ($otp && $otp->expires_at > Carbon::now()) {
            // OTP valid
            return response()->json([
                'status' => 'success',
                'message' => 'OTP valid, silakan lanjutkan ke halaman registrasi.',
                'redirect' => route('auth.register.form'),
            ]);
        }

        // Jika OTP tidak valid atau sudah kadaluarsa
        return response()->json([
            'status' => 'error',
            'message' => 'OTP tidak valid atau sudah kadaluarsa. Silakan coba lagi.',
        ], 400);
    }

    // Menampilkan form OTP
    public function showOtpForm()
    {
        return response()->json([
            'message' => 'Masukkan OTP yang dikirimkan ke Email Anda',
        ]);
    }
}

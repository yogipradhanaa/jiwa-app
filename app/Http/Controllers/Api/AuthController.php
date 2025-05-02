<?php

namespace App\Http\Controllers\Api;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    // Login atau verifikasi email
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Cek apakah email terdaftar
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Jika email terdaftar, arahkan ke halaman untuk memasukkan PIN
            return response()->json([
                'message' => 'Login Bersahil. Silakan lanjutkan untuk memasukkan PIN.',
                'is_registered' => true
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
                'is_registered' => false
            ]);
        }
    }

    public function pinLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'pin_code' => ['required', 'digits:6'],
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }
    
        // Verifikasi PIN menggunakan Hash::check
        if (!Hash::check($request->pin_code, $user->pin_code)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN salah.',
            ], 401);
        }
    
        // Kalau cocok, buat token
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $user,
        ]);
    }
    
    private function generateOtp()
    {
        return rand(1000, 9999);  // Generate OTP 4 digit
    }

    // Verifikasi OTP yang dikirim ke email
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'numeric', 'digits:4'],
            'email' => ['required', 'email'],
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
            ]);
        }

        // Jika OTP tidak valid atau sudah kadaluarsa
        return response()->json([
            'status' => 'error',
            'message' => 'OTP tidak valid atau sudah kadaluarsa. Silakan coba lagi.',
        ], 400);
    }

    public function register(Request $request)
    {
        $request->validate([
            'referral_code' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['required', 'date'],
            'email' => ['required', 'email', 'unique:users', 'email'],
            'region' => ['required', 'string', 'max:255'],
            'job' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'referral_code' => $request->referral_code,
            'name' => $request->name,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'email' => $request->email,
            'region' => $request->region,
            'job' => $request->job,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil. Silakan lanjutkan untuk membuat PIN.',
            'data' => $user,
            'token' => $token,
        ], 201);
    }

    public function createPin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'pin_code' => ['required', 'digits:6'],
        ]);

        // Cari user berdasarkan email yang diberikan
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Jika user tidak ditemukan berdasarkan email
            return response()->json([
                'status' => 'error',
                'message' => 'User dengan email tersebut tidak ditemukan.',
            ], 404);
        }

        // Simpan PIN yang dimasukkan oleh pengguna
        $user->pin_code = Hash::make($request->pin_code);
        $user->save();

        // Respons sukses setelah PIN berhasil dibuat
        return response()->json([
            'status' => 'success',
            'message' => 'PIN berhasil dibuat.',
            'data' => $user,
        ]);
    }


}

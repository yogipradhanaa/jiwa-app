<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

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
            'pin_code' => ['required', 'string', 'max:60'],
        ]);

        $user->fill($request->only([
            'name',
            'gender',
            'date_of_birth',
            'region',
            'job',
            'phone_number',
        ]));

        if ($request->has('pin_code')) {
            $user->pin_code = Hash::make($request->pin_code);
        }

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
}
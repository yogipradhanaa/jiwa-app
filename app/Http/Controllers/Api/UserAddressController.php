<?php

namespace App\Http\Controllers\Api;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserAddressController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $addresses = $user->addresses;

        return response()->json([
            'status' => 'success',
            'data' => $addresses,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'note' => ['nullable', 'string'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $address = $request->user()->addresses()->create($request->only([
            'label',
            'address', 
            'latitude', 
            'longitude', 
            'note', 
            'recipient_name',
            'phone_number'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat berhasil ditambahkan.',
            'data' => $address
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'note' => ['nullable', 'string'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $address = $request->user()->addresses()->findOrFail($id);

        $address->update($request->only([
            'label',
            'address', 
            'latitude', 
            'longitude', 
            'note', 
            'recipient_name',
            'phone_number'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $address
        ]);
    }
    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat berhasil dihapus.',
        ]);
    }
}

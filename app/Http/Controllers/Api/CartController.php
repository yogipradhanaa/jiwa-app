<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $cart = $user->cart()->with('items.product')->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang kosong'], 404);
        }

        return response()->json([
            'message' => 'Keranjang berhasil diambil',
            'cart' => $cart,
        ]);
    }


    public function store(Request $request)
{
    $request->validate([
        'product_id' => ['required', 'exists:products,id'],
        'quantity' => ['required', 'integer', 'min:1'],
        'note' => ['nullable', 'string']
    ]);

    $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

    $item = $cart->items()->updateOrCreate(
        ['product_id' => $request->product_id],
        ['quantity' => $request->quantity, 'note' => $request->note]
    );

    // Memastikan item terkait sudah dimuat, termasuk field 'note'
    $item->load('product');

    return response()->json([
        'message' => 'Produk berhasil ditambahkan ke keranjang',
        'cart' => $cart,
        'item' => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'note' => $item->note,  // Menambahkan 'note' ke dalam response
        ]
    ]);
}




    public function destroy(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang tidak ditemukan'], 404);
        }

        $item = $cart->items()->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan dalam keranjang'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item berhasil dihapus dari keranjang']);
    }
}

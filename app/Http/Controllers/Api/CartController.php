<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use DB;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $cart = $user->cart()->with('items.product')->first();

        if (!$cart) {
            return response()->json([
                'status' => 'success',
                'message' => 'Keranjang kosong',
                'data' => null
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Keranjang berhasil diambil',
            'data' => $cart->load('items.product')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
            'food_id' => ['nullable', 'exists:products,id'],
            'drink_id' => ['nullable', 'exists:products,id'],
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $product = Product::with('category')->findOrFail($request->product_id);

            // Cek combo berdasarkan category_id = 1
            if ($product->category_id === 1) {
                // Validasi wajib food dan drink
                if (!$request->food_id || !$request->drink_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Produk combo wajib memilih 1 makanan dan 1 minuman.',
                        'errors' => [
                            'food_id' => ['Makanan wajib dipilih untuk produk combo.'],
                            'drink_id' => ['Minuman wajib dipilih untuk produk combo.'],
                        ]
                    ], 422);
                }

                $food = Product::with('category')->findOrFail($request->food_id);
                $drink = Product::with('category')->findOrFail($request->drink_id);

                if ($food->category->type !== 'food' || $drink->category->type !== 'drink') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Produk makanan atau minuman yang dipilih tidak valid.',
                    ], 422);
                }

                // Simpan combo utama
                $comboItem = $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'note' => $request->note,
                ]);

                // Simpan sub-item: makanan
                $cart->items()->create([
                    'product_id' => $food->id,
                    'quantity' => 1,
                    'parent_id' => $comboItem->id,
                ]);

                // Simpan sub-item: drink
                $cart->items()->create([
                    'product_id' => $drink->id,
                    'quantity' => 1,
                    'parent_id' => $comboItem->id,
                ]);

                $response = [
                    'status' => 'success',
                    'message' => 'Combo berhasil ditambahkan ke keranjang',
                    'data' => [
                        'cart' => $cart->load('items.product', 'items.children.product'),
                        'added_item' => $comboItem,
                    ]
                ];
            }

            // Non-combo: update or create biasa
            $item = $cart->items()->updateOrCreate(
                ['product_id' => $product->id, 'parent_id' => null],
                ['quantity' => $request->quantity, 'note' => $request->note]
            );

            $response = [
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan ke keranjang',
                'data' => [
                    'cart' => $cart->load('items.product'),
                    'added_item' => $item,
                ]
            ];

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 400);
        }

        $item->load('product');

        return response()->json($response);
    }

    public function destroy(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'Keranjang tidak ditemukan'
            ], 404);
        }

        $item = $cart->items()->where('id', $id)->first();

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item tidak ditemukan dalam keranjang'
            ], 404);
        }

        // Jika item adalah combo parent, hapus anak-anaknya juga
        if ($item->parent_id === null) {
            $cart->items()->where('parent_id', $item->id)->delete();
        }

        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Item berhasil dihapus dari keranjang'
        ]);
    }
    public function getCartSummary(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $subtotal = $cart->items->whereNull('parent_id')->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'message' => 'Cart summary retrieved successfully',
            'summary' => [
                'subtotal_price' => $subtotal,
                'delivery_fee' => 0, // Cart belum ada kurir
                'total_price' => $subtotal,
                'item_count' => $cart->items->whereNull('parent_id')->sum('quantity'),
            ],
        ]);
    }

}

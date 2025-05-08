<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); 
        
        $orders = $user->orders()->with('items.product')->paginate();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'address_id' => ['required', 'exists:user_addresses,id'],
        'order_type' => ['required', 'in:Take Away,Delivery'],
    ]);

    $user = $request->user();
    $cart = $user->cart()->with('items.product')->first();

    if (!$cart || $cart->items->isEmpty()) {
        return response()->json(['message' => 'Cart is empty'], 400);
    }

    $totalPrice = $cart->items->sum(function ($item) {
        return $item->product->price * $item->quantity;
    });

    $order = $user->orders()->create([
        'address_id' => $request->address_id,
        'order_type' => $request->order_type,
        'status' => 'Pending',
        'total_price' => $totalPrice,
    ]);

    foreach ($cart->items as $item) {
        $order->items()->create([
            'product_id' => $item->product_id,
            'price' => $item->product->price,
            'quantity' => $item->quantity,
            'note' => $item->note,
        ]);
    }

    return response()->json([
        'message' => 'Lanjutkan Pembayaran',
        'order' => $order
    ]);
}

}

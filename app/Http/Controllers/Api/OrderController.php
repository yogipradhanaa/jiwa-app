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

        // Tambahkan subtotal dan item count ke tiap order
        $orders->getCollection()->transform(function ($order) {
            $subtotal = $order->items->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            $order->subtotal_price = $subtotal;
            $order->item_count = $order->items->sum('quantity');
            return $order;
        });

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => ['required', 'in:Take Away,Delivery'],
            'address_id' => ['required_if:order_type,Delivery', 'nullable', 'exists:user_addresses,id'],
            'courier' => ['required_if:order_type,Delivery', 'nullable', 'in:GoSend,GrabExpress'],
        ]);

        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $subtotal = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $deliveryFee = 0;
        if ($request->order_type === 'Delivery') {
            $deliveryFee = $request->courier === 'GoSend' ? 10000 : ($request->courier === 'GrabExpress' ? 12000 : 0);
        }

        $total = $subtotal + $deliveryFee;

        $order = $user->orders()->create([
            'address_id' => $request->order_type === 'Delivery' ? $request->address_id : null,
            'order_type' => $request->order_type,
            'courier' => $request->order_type === 'Delivery' ? $request->courier : null,
            'delivery_fee' => $deliveryFee,
            'status' => 'Pending',
            'subtotal_price' => $subtotal,
            'total_price' => $total,
        ]);

        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'note' => $item->note,
            ]);
        }

        $items = $order->items()->with('product')->get();

        return response()->json([
            'message' => 'Lanjutkan Pembayaran',
            'order' => [
                'id' => $order->id,
                'subtotal_price' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_price' => $total,
                'item_count' => $items->sum('quantity'),
                'order_type' => $order->order_type,
                'courier' => $order->courier,
                'order_status' => $order->status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'items' => $items
            ]
        ]);
    }
}

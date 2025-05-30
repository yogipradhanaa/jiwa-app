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
            'order_type' => 'required|in:Take Away,Delivery',
            'address_id' => 'nullable|required_if:order_type,Delivery|exists:user_addresses,id',
            'courier' => 'nullable|required_if:order_type,Delivery|in:GoSend,GrabExpress',
        ]);

        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Hitung subtotal hanya dari parent items
        $subtotal = $cart->items->filter(fn($item) => is_null($item->parent_id))
            ->sum(fn($item) => $item->product->price * $item->quantity);

        // Hitung delivery fee jika perlu
        $deliveryFee = 0;
        if ($request->order_type === 'Delivery') {
            $deliveryFee = match ($request->courier) {
                'GoSend' => 10000,
                'GrabExpress' => 12000,
                default => 0,
            };
        }

        $total = $subtotal + $deliveryFee;

        // Simpan order baru
        $order = $user->orders()->create([
            'address_id' => $request->order_type === 'Delivery' ? $request->address_id : null,
            'order_type' => $request->order_type,
            'courier' => $request->order_type === 'Delivery' ? $request->courier : null,
            'delivery_fee' => $deliveryFee,
            'order_status' => 'Pending',
            'subtotal_price' => $subtotal,
            'total_price' => $total,
        ]);

        // Simpan order items (parent-child)
        $cartItemIdToOrderItemId = [];

        // Simpan parent items terlebih dahulu
        foreach ($cart->items->whereNull('parent_id') as $cartItem) {
            $orderItem = $order->items()->create([
                'product_id' => $cartItem->product_id,
                'price' => $cartItem->product->price,
                'quantity' => $cartItem->quantity,
                'note' => $cartItem->note,
                'parent_id' => null,
            ]);

            $cartItemIdToOrderItemId[$cartItem->id] = $orderItem->id;
        }

        // Simpan child items
        foreach ($cart->items->whereNotNull('parent_id') as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'price' => $cartItem->product->price,
                'quantity' => $cartItem->quantity,
                'note' => $cartItem->note,
                'parent_id' => $cartItemIdToOrderItemId[$cartItem->parent_id] ?? null,
            ]);
        }

        // Ambil items (dengan anak-anaknya) untuk response
        $items = $order->items()->with('product', 'children.product')->whereNull('parent_id')->get();

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
                'order_status' => $order->order_status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'items' => $items,
            ]
        ]);
    }
    public function couriers()
    {
        $couriers = [
            ['code' => 'GoSend', 'name' => 'GoSend', 'fee' => 10000],
            ['code' => 'GrabExpress', 'name' => 'GrabExpress', 'fee' => 12000],
        ];

        return response()->json([
            'message' => 'Courier list retrieved successfully',
            'couriers' => $couriers,
        ]);
    }
    public function getOrderSummary(Request $request, $orderId)
    {
        $user = $request->user();
        $order = $user->orders()->with('items')->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Hitung subtotal hanya dari parent items
        $subtotal = $order->items->whereNull('parent_id')->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return response()->json([
            'message' => 'Order summary retrieved successfully',
            'summary' => [
                'order_id' => $order->id,
                'subtotal_price' => $subtotal,
                'delivery_fee' => $order->delivery_fee,
                'total_price' => $subtotal + $order->delivery_fee,
                'item_count' => $order->items->whereNull('parent_id')->sum('quantity'),
                'order_type' => $order->order_type,
                'courier' => $order->courier,
                'status' => $order->order_status,
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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


    private function generateOrderCode()
    {
        $datePrefix = now()->format('Ymd'); // contoh: 20250603
        do {
            $randomPart = strtoupper(Str::random(8));
            $code = 'J+' . $datePrefix . $randomPart;
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }


    public function store(Request $request)
    {
        if ($request->order_type === 'Take Away') {
            $request->request->remove('address_id');
            $request->request->remove('courier');
        }

        $request->validate([
            'order_type' => 'required|in:Take Away,Delivery',
            'address_id' => 'required_if:order_type,Delivery|exists:user_addresses,id',
            'courier' => 'nullable|required_if:order_type,Delivery|in:GoSend,GrabExpress',
        ]);

        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Hitung subtotal hanya dari item utama (tanpa parent_id)
        $subtotal = $cart->items->whereNull('parent_id')->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $deliveryFee = 0;
        if ($request->order_type === 'Delivery') {
            $deliveryFee = match ($request->courier) {
                'GoSend' => 10000,
                'GrabExpress' => 12000,
                default => 0,
            };
        }

        $total = $subtotal + $deliveryFee;

        // Generate order_code unik
        $orderCode = $this->generateOrderCode();

        // Simpan order
        $order = $user->orders()->create([
            'order_code' => $orderCode,
            'address_id' => $request->order_type === 'Delivery' ? $request->address_id : null,
            'order_type' => $request->order_type,
            'courier' => $request->order_type === 'Delivery' ? $request->courier : null,
            'delivery_fee' => $deliveryFee,
            'order_status' => 'Pending',
            'total_price' => $total,
        ]);

        // Simpan item ke order
        $cartItemIdToOrderItemId = [];

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

        foreach ($cart->items->whereNotNull('parent_id') as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'price' => $cartItem->product->price,
                'quantity' => $cartItem->quantity,
                'note' => $cartItem->note,
                'parent_id' => $cartItemIdToOrderItemId[$cartItem->parent_id] ?? null,
            ]);
        }

        $items = $order->items()->with('product', 'children.product')->whereNull('parent_id')->get();

        return response()->json([
            'message' => 'Lanjutkan Pembayaran',
            'order' => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'address_id' => $order->address_id,
                'subtotal_price' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_price' => $total,
                'item_count' => $items->sum('quantity'),
                'order_type' => $order->order_type,
                'courier' => $order->courier,
                'order_status' => $order->order_status,
                'created_at' => $order->created_at,
                'items' => $items,
            ]
        ]);
    }
}
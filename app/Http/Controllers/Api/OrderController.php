<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()->with('items.product')->paginate();

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
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = $user->orders()
            ->with('items.product')
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or does not belong to user',
            ], 404);
        }

        $subtotal = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $order->subtotal_price = $subtotal;
        $order->item_count = $order->items->sum('quantity');

        return response()->json([
            'message' => 'Order retrieved successfully',
            'order' => $order,
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

        $subtotal = $cart->items->filter(fn($item) => is_null($item->parent_id))
            ->sum(fn($item) => $item->product->price * $item->quantity);

        $deliveryFee = 0;
        if ($request->order_type === 'Delivery') {
            $deliveryFee = match ($request->courier) {
                'GoSend' => 10000,
                'GrabExpress' => 12000,
                default => 0,
            };
        }

        $total = $subtotal + $deliveryFee;

        $orderCode = $this->generateOrderCode();

        $order = $user->orders()->create([
            'order_code' => $orderCode,
            'address_id' => $request->order_type === 'Delivery' ? $request->address_id : null,
            'order_type' => $request->order_type,
            'courier' => $request->order_type === 'Delivery' ? $request->courier : null,
            'delivery_fee' => $deliveryFee,
            'order_status' => 'Pending',
            'subtotal_price' => $subtotal,
            'total_price' => $total,
        ]);

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
                'updated_at' => $order->updated_at,
                'items' => $items,
            ]
        ]);
    }

    private function generateOrderCode()
    {
        $date = now()->format('Ymd');
        do {
            $random = strtoupper(Str::random(6));
            $code = 'J+' . $date . $random;
        } while (Order::where('order_code', $code)->exists());

        return $code;
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

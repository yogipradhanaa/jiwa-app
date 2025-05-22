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
    // Validasi dan ambil user & cart
    $user = $request->user();
    $cart = $user->cart()->with('items.product')->first();

    if (!$cart || $cart->items->isEmpty()) {
        return response()->json(['message' => 'Cart is empty'], 400);
    }

    // Hitung subtotal hanya dari item yang parent_id null (item utama)
    $subtotal = $cart->items->filter(fn($item) => is_null($item->parent_id))
                             ->sum(fn($item) => $item->product->price * $item->quantity);

    $deliveryFee = 0;
    if ($request->order_type === 'Delivery') {
        $deliveryFee = $request->courier === 'GoSend' ? 10000 : ($request->courier === 'GrabExpress' ? 12000 : 0);
    }

    $total = $subtotal + $deliveryFee;

    // Buat order
    $order = $user->orders()->create([
        'address_id' => $request->order_type === 'Delivery' ? $request->address_id : null,
        'order_type' => $request->order_type,
        'courier' => $request->order_type === 'Delivery' ? $request->courier : null,
        'delivery_fee' => $deliveryFee,
        'order_status' => 'Pending',
        'subtotal_price' => $subtotal,
        'total_price' => $total,
    ]);

    // Simpan order items
    // Karena ada parent-child, simpan dulu parent, lalu anak dengan parent_id order item
    $cartItemIdToOrderItemId = [];

    // Simpan parent items
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

    // Simpan anak items
    foreach ($cart->items->whereNotNull('parent_id') as $cartItem) {
        $order->items()->create([
            'product_id' => $cartItem->product_id,
            'price' => $cartItem->product->price,
            'quantity' => $cartItem->quantity,
            'note' => $cartItem->note,
            'parent_id' => $cartItemIdToOrderItemId[$cartItem->parent_id] ?? null,
        ]);
    }

    // Load items beserta relasi children (untuk response)
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
}

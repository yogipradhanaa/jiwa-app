<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Support\Str;
use App\Models\Order;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function generatePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = $request->user();
        $order = $user->orders()->where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->order_status !== 'Pending') {
            return response()->json(['message' => 'Order is already paid or invalid'], 400);
        }

        $createTransaction = \Midtrans\Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => $order->total_price,
            ],
            // // 'enabled_payments' => [
            // //     'gopay'
            // ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ]);

        return response()->json([
            'message' => 'Snap token created',
            'url' => $createTransaction->redirect_url,
            'order' => $order,
        ]);
    }

    public function cancelPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = $request->user();
        $order = $user->orders()->where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->order_status === 'Cancelled') {
            return response()->json(['message' => 'Order already cancelled'], 400);
        }

        // Update status menjadi cancelled
        $order->order_status = 'Cancelled';
        $order->save();

        return response()->json([
            'message' => 'Pembayaran dibatalkan',
            'order_status' => $order->order_status,
        ]);
    }


    public function paymentCallback(Request $request)
    {
        // return $request->all();
        // $notif = new Notification();

        $order = Order::where('order_code', $request->order_id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 200);
        }

        $transaction = $request->transaction_status;
        $fraud = $request->fraud_status;

        switch ($transaction) {
            case 'capture':
                if ($fraud == 'challenge') {
                    $order->order_status = 'Cancelled';
                } else {
                    $order->order_status = 'Processing';
                }
                break;

            case 'settlement':
                $order->order_status = 'Completed';
                break;

            case 'pending':
                $order->order_status = 'Pending';
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
                $order->order_status = 'Cancelled';
                break;
        }

        if ($transaction === 'settlement') {
            $order->order_status = 'Completed';

            // Hapus keranjang
            $user = $order->user;
            $user->cart->items()->delete();

            // Simpan XP
            $order->orderRewards()->create([
                'reward_type' => 'xp',
                'value' => rand(10, 30),
            ]);

            // Simpan Jiwa Point
            $order->orderRewards()->create([
                'reward_type' => 'jiwa_point',
                'value' => rand(500, 3000),
                'expired_at' => now()->addYear(),
            ]);
        }
        $order->save();

        return response()->json([
            'message' => 'Callback processed',
            'order_status' => $order->order_status,
        ]);

    }
}

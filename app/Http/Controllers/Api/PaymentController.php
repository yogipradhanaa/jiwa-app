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

        // Generate order_code jika belum ada
        if (!$order->order_code) {
            $order->order_code = 'J+' . strtoupper(Str::random(20));
            $order->save();
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

    public function paymentCallback(Request $request)
    {
        return $request->all();
        // $notif = new Notification();

        $order = Order::where('order_code', $request->order_id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transaction = $request->transaction_status;
        $fraud = $request->fraud_status;

        switch ($transaction) {
            case 'capture':
                $order->order_status = ($fraud === 'challenge') ? 'Failed' : 'Paid';
                break;
            case 'settlement':
                $order->order_status = 'Paid';
                break;
            case 'pending':
                $order->order_status = 'Pending';
                break;
            case 'deny':
            case 'expire':
            case 'cancel':
                $order->order_status = 'Failed';
                break;
        }

        if ($transaction === 'settlement') {
            $user = $order->user;
            $user->cart->items()->delete();
        }

        $order->save();

        return response()->json(['message' => 'Callback processed']);
    }
}

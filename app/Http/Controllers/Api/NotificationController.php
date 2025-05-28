<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderReward;

class NotificationController extends Controller
{
    public function infoNotification(Request $request)
    {
        $user = $request->user();

        // Ambil semua reward milik user melalui relasi orders
        $rewards = OrderReward::with('order')
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        $data = $rewards->map(function ($reward) {
            $order = $reward->order;

            // Format tanggal dan waktu
            $createdAt = $reward->created_at;
            $date = $createdAt->format('d M Y');
            $time = $createdAt->format('H:i');

            // Judul dan pesan berdasarkan tipe reward
            if ($reward->reward_type === 'xp') {
                $title = 'Mendapat XP dari Transaksi';
                $message = "Anda mendapatkan {$reward->value} XP dari transaksi anda";
            } else {
                $title = 'Bonus Jiwa Point dari Transaksi';
                $message = "Anda mendapatkan {$reward->value} Jiwa Point. Berlaku hingga " . $reward->expired_at->format('Y-m-d');
            }

            return [
                'type' => 'transaction',
                'order_code' => $order->order_code,
                'title' => $title,
                'message' => $message,
                'reward_type' => $reward->reward_type,
                'value' => $reward->value,
                'date' => $date,
                'time' => $time,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}

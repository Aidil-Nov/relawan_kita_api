<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Campaign;
use App\Models\Notification; // [BARU] Import Model Notifikasi
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class DonationController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    // --- BUAT TRANSAKSI DONASI BARU ---
    public function store(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'amount' => 'required|numeric|min:1000', 
        ]);

        $user = $request->user();
        $orderId = 'DON-' . time() . '-' . Str::random(5);

        $donation = Donation::create([
            'user_id' => $user->id,
            'campaign_id' => $request->campaign_id,
            'order_id' => $orderId,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $request->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789',
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $donation->update(['snap_token' => $snapToken]);

            return response()->json([
                'success' => true,
                'message' => 'Donasi berhasil dibuat',
                'data' => [
                    'donation' => $donation,
                    'snap_token' => $snapToken,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke Midtrans: ' . $e->getMessage()
            ], 500);
        }
    }

    // --- RIWAYAT DONASI SAYA (AUTO UPDATE STATUS & BUAT NOTIFIKASI) ---
    public function history(Request $request)
    {
        $donations = Donation::with('campaign') 
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($donations as $donation) {
            if ($donation->status == 'pending' && $donation->snap_token) {
                try {
                    $status = Transaction::status($donation->order_id);
                    
                    $newStatus = 'pending';
                    $transactionStatus = $status->transaction_status;
                    $fraudStatus = $status->fraud_status;

                    if ($transactionStatus == 'capture') {
                        $newStatus = ($fraudStatus == 'challenge') ? 'challenge' : 'success';
                    } else if ($transactionStatus == 'settlement') {
                        $newStatus = 'success';
                    } else if ($transactionStatus == 'deny') {
                        $newStatus = 'failed';
                    } else if ($transactionStatus == 'expire') {
                        $newStatus = 'expired';
                    } else if ($transactionStatus == 'cancel') {
                        $newStatus = 'canceled';
                    }

                    if ($newStatus != 'pending') {
                        
                        // LOGIKA SUKSES
                        if ($newStatus == 'success') {
                            $campaign = Campaign::find($donation->campaign_id);
                            if ($campaign) {
                                $campaign->increment('collected_amount', $donation->amount);
                            }

                            // [BARU] BUAT NOTIFIKASI OTOMATIS
                            // Cek dulu agar tidak duplikat (opsional tapi bagus)
                            $cekNotif = Notification::where('title', 'Pembayaran Berhasil')
                                        ->where('message', 'LIKE', '%' . $donation->order_id . '%')
                                        ->first();

                            if (!$cekNotif) {
                                Notification::create([
                                    'user_id' => $donation->user_id,
                                    'title'   => 'Pembayaran Berhasil',
                                    'message' => 'Donasi #' . $donation->order_id . ' sebesar Rp ' . number_format($donation->amount) . ' telah berhasil.',
                                    'type'    => 'success', // Tipe: success (donasi)
                                    'is_read' => false,
                                ]);
                            }
                        }

                        $donation->update(['status' => $newStatus]);
                        $donation->status = $newStatus; 
                    }

                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat Donasi',
            'data' => $donations
        ]);
    }
}
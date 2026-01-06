<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Campaign; // Pastikan Model Campaign di-import
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

    // --- RIWAYAT DONASI SAYA (AUTO UPDATE STATUS & SALDO CAMPAIGN) ---
    public function history(Request $request)
    {
        // 1. Ambil semua donasi user ini
        $donations = Donation::with('campaign') 
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Loop cek status ke Midtrans
        foreach ($donations as $donation) {
            // Hanya cek yang statusnya masih 'pending' di database kita
            if ($donation->status == 'pending' && $donation->snap_token) {
                try {
                    // Cek status real-time ke Midtrans
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

                    // Jika status berubah dari 'pending' ke yang lain
                    if ($newStatus != 'pending') {
                        
                        // [MODIFIKASI MULAI] ----------------------------------
                        // Jika status BERUBAH jadi SUCCESS, tambahkan uang ke Campaign
                        if ($newStatus == 'success') {
                            // Cari campaign terkait
                            $campaign = Campaign::find($donation->campaign_id);
                            
                            if ($campaign) {
                                // Tambahkan collected_amount
                                // increment() adalah cara aman menambahkan angka di Laravel
                                $campaign->increment('collected_amount', $donation->amount);
                            }
                        }
                        // [MODIFIKASI SELESAI] --------------------------------

                        // Update status donasi di database
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
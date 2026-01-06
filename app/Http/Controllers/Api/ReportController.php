<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Notification; // [PENTING] Pastikan ini ada
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    // --- KIRIM LAPORAN BARU ---
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'category' => 'required',
            'urgency' => 'required',
            'location_address' => 'required',
            'description' => 'required',
            'photo' => 'required|image|max:5120', // Max 5MB
        ]);

        // 2. Upload Foto
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('reports', 'public');
            $photoUrl = asset('storage/' . $path);
        } else {
            return response()->json(['message' => 'Foto wajib diisi'], 400);
        }

        // 3. Simpan Laporan ke Database
        $report = Report::create([
            'user_id' => $request->user()->id,
            'ticket_id' => 'RPT-' . strtoupper(Str::random(6)),
            'category' => $request->category,
            'urgency' => $request->urgency,
            'description' => $request->description,
            'location_address' => $request->location_address,
            'latitude' => $request->latitude ?? -0.02,
            'longitude' => $request->longitude ?? 109.33,
            'photo_url' => $photoUrl,
            'status' => 'pending',
        ]);

        // [BARU - PERBAIKAN] BUAT NOTIFIKASI LANGSUNG SAAT KIRIM
        Notification::create([
            'user_id' => $request->user()->id,
            'title' => 'Laporan Terkirim',
            'message' => 'Laporan ' . $request->category . ' Anda berhasil dikirim dan sedang menunggu verifikasi admin.',
            'type' => 'report', // Tipe report agar iconnya biru/tanda centang
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim!',
            'data' => $report
        ], 201);
    }

    // --- AMBIL LIST LAPORAN SAYA ---
    public function myReports(Request $request)
    {
        $reports = \App\Models\Report::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $reports->map(function ($report) {
            $report->image_url = $report->photo_url;
            return $report;
        });

        return response()->json([
            'success' => true,
            'message' => 'List Laporan Saya',
            'data' => $reports
        ]);
    }

    // --- BATALKAN LAPORAN ---
    public function cancel($id, Request $request)
    {
        $report = Report::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        if ($report->status != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak bisa dibatalkan karena sudah diproses.'
            ], 400);
        }

        $report->update(['status' => 'canceled']);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibatalkan.'
        ]);
    }

    // --- HAPUS LAPORAN ---
    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        $deletedCount = Report::whereIn('id', $request->ids)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['canceled', 'rejected', 'verified'])
            ->delete();

        if ($deletedCount > 0) {
            return response()->json(['success' => true, 'message' => "$deletedCount laporan dihapus."]);
        } else {
            return response()->json(['success' => false, 'message' => 'Tidak ada data yang bisa dihapus.'], 400);
        }
    }

    // --- LIST PUBLIC (UNTUK PETA) ---
    public function publicIndex()
    {
        $reports = Report::where('status', 'verified')
            ->orderBy('created_at', 'desc')
            ->get();

        $reports->transform(function ($report) {
            $report->latitude = (double) $report->latitude;
            $report->longitude = (double) $report->longitude;
            return $report;
        });

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // --- SIMULASI ADMIN VERIFIKASI (OPSIONAL) ---
    public function verify($id)
    {
        $report = Report::find($id);

        if (!$report)
            return response()->json(['message' => 'Not found'], 404);

        $report->update(['status' => 'verified']);

        // Notifikasi kedua: Saat diverifikasi
        Notification::create([
            'user_id' => $report->user_id,
            'title' => 'Laporan Diverifikasi',
            'message' => 'Laporan Anda telah dicek oleh Admin. Bantuan segera dikirim.',
            'type' => 'alert',
            'is_read' => false,
        ]);

        return response()->json(['message' => 'Laporan diverifikasi & Notifikasi dikirim']);
    }
    // --- KHUSUS SOS / PANIC BUTTON ---
    public function sos(Request $request)
    {
        // Validasi hanya lokasi (karena user panik)
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // Simpan sebagai Laporan tapi dengan data otomatis
        $report = \App\Models\Report::create([
            'user_id' => $request->user()->id,
            'ticket_id' => 'SOS-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'category' => 'DARURAT', // Kategori Khusus
            'urgency' => 'Tinggi',
            'description' => 'Sinyal SOS Darurat! Membutuhkan pertolongan segera.',
            'location_address' => 'Titik Koordinat GPS Darurat', // Nanti bisa di-reverse geocode di flutter
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_url' => 'https://via.placeholder.com/150/FF0000/FFFFFF?text=SOS', // Gambar Default Merah
            'status' => 'verified', // Langsung Verified agar langsung muncul di peta semua orang!
        ]);

        // Buat Notifikasi ke Admin/Semua User
        \App\Models\Notification::create([
            'user_id' => $request->user()->id,
            'title'   => 'BAHAYA / SOS!',
            'message' => 'Seseorang mengirim sinyal darurat di lokasi Anda!',
            'type'    => 'alert',
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sinyal SOS Terkirim!',
            'data' => $report
        ], 201);
    }
}
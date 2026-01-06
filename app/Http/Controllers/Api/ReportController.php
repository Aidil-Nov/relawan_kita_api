<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'category' => 'required',
            'urgency' => 'required',
            'location_address' => 'required',
            'description' => 'required',
            'photo' => 'required|image|max:2048',
        ]);

        // 2. Upload Foto
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('reports', 'public');
            // Simpan Full URL agar mudah diakses Flutter
            $photoUrl = asset('storage/' . $path);
        } else {
            return response()->json(['message' => 'Foto wajib diisi'], 400);
        }

        // 3. Simpan ke Database
        $report = Report::create([
            // PERBAIKAN 1: Jangan Hardcode! Gunakan ID user yang sedang login
            'user_id' => $request->user()->id,

            'ticket_id' => 'RPT-' . strtoupper(Str::random(6)),
            'category' => $request->category,
            'urgency' => $request->urgency,
            'description' => $request->description,
            'location_address' => $request->location_address,
            'latitude' => -0.02,
            'longitude' => 109.33,
            'photo_url' => $photoUrl, // Pastikan nama variabel sama
            'status' => 'pending',
            'latitude' => $request->latitude ?? -0.02,
            'longitude' => $request->longitude ?? 109.33,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim!',
            'data' => $report
        ], 201);
    }

    public function myReports(Request $request)
    {
        // Ambil laporan milik user yang login
        $reports = \App\Models\Report::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // PERBAIKAN 2: Mapping Gambar
        $reports->map(function ($report) {
            // Di database Anda kolomnya 'photo_url' dan isinya sudah Full URL (http://...)
            // Jadi langsung assign saja, tidak perlu url('storage/...') lagi
            $report->image_url = $report->photo_url;
            return $report;
        });

        return response()->json([
            'success' => true,
            'message' => 'List Laporan Saya',
            'data' => $reports
        ]);
    }
    public function cancel($id, Request $request)
    {
        // 1. Cari laporan berdasarkan ID dan User ID (Security check)
        $report = Report::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        // 2. Cek Status: Hanya boleh cancel jika 'pending'
        if ($report->status != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak bisa dibatalkan karena sudah diproses.'
            ], 400);
        }

        // 3. Update Status jadi 'canceled' (Pastikan kolom enum di db mendukung, atau pakai string biasa)
        $report->update(['status' => 'canceled']);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibatalkan.'
        ]);
    }
    // HAPUS LAPORAN (SINGLE ATAU MULTIPLE)
    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array', // Menerima list ID: [1, 2, 5]
            'ids.*' => 'integer'
        ]);

        // Hapus laporan dimana:
        // 1. ID ada di dalam list yang dikirim
        // 2. Milik user yang sedang login
        // 3. Statusnya SUDAH SELESAI/BATAL (Pending tidak boleh dihapus agar data aman)
        $deletedCount = Report::whereIn('id', $request->ids)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['canceled', 'rejected', 'verified']) 
            ->delete();

        if ($deletedCount > 0) {
            return response()->json(['success' => true, 'message' => "$deletedCount laporan dihapus."]);
        } else {
            return response()->json(['success' => false, 'message' => 'Tidak ada data yang bisa dihapus (Mungkin status masih Pending).'], 400);
        }
    }
}
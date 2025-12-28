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
            'photo' => 'required|image|max:2048', // Wajib file gambar max 2MB
        ]);

        // 2. Upload Foto ke Server
        if ($request->hasFile('photo')) {
            // Simpan di folder public/reports
            $path = $request->file('photo')->store('reports', 'public');
            // Buat URL lengkap agar bisa diakses Flutter (Ganti localhost dengan IP Laptop otomatis)
            $photoUrl = asset('storage/' . $path);
        } else {
            return response()->json(['message' => 'Foto wajib diisi'], 400);
        }

        // 3. Simpan ke Database
        $report = Report::create([
            'user_id' => 1, // HARDCODE DULU: Anggap User ID 1 yang lapor
            'ticket_id' => 'RPT-' . strtoupper(Str::random(6)),
            'category' => $request->category,
            'urgency' => $request->urgency,
            'description' => $request->description,
            'location_address' => $request->location_address,
            'latitude' => -0.02, // Dummy dulu
            'longitude' => 109.33, // Dummy dulu
            'photo_url' => $photoUrl,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim!',
            'data' => $report
        ], 201);
    }
}
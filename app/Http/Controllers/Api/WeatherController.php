<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// [PENTING] Baris ini WAJIB ada agar Http::get berfungsi
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getCurrentWeather(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'lat' => 'required',
            'lon' => 'required',
        ]);

        // 2. Ambil API Key dari .env
        $apiKey = env('OPENWEATHER_API_KEY');

        // Pastikan API Key terbaca
        if (!$apiKey) {
            return response()->json([
                'message' => 'API Key Server belum disetting'
            ], 500);
        }

        $lat = $request->lat;
        $lon = $request->lon;

        // 3. Panggil API OpenWeather (GUNAKAN Http::get)
        // Pastikan penulisannya Http::get (Huruf besar H)
        $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'id'
        ]);

        // 4. Kembalikan Respon ke Flutter
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            // Jika gagal, kirim error asli dari OpenWeather untuk debugging
            return response()->json([
                'message' => 'Gagal mengambil data cuaca',
                'error_detail' => $response->body()
            ], $response->status());
        }
    }
}
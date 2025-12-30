<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Import Controller
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// 1. PUBLIC ROUTES (Bisa diakses tanpa Token/Login)
// ============================================================================

// Cek Koneksi Server
Route::get('/test', function () {
    return response()->json(['message' => 'API Berjalan 100%!']);
});

// Authentication (Daftar & Masuk)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Kampanye Donasi (Orang belum login boleh lihat donasi)
Route::get('/campaigns', [CampaignController::class, 'index']);


// ============================================================================
// 2. PROTECTED ROUTES (Wajib Login / Sertakan Bearer Token)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);

    // Features
    // Lapor Bencana (Pindah ke sini agar user_id otomatis terdeteksi)
    Route::post('/reports', [ReportController::class, 'store']);

    // Cek User Sendiri (Opsional, untuk debugging)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/update-password', [AuthController::class, 'updatePassword']);
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Import Controller
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DonationController; // Pastikan ini ada

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// 1. PUBLIC ROUTES (Bisa diakses Guest)
// ============================================================================

Route::get('/test', function () { return response()->json(['message' => 'API OK']); });
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// [PENTING] Pindahkan ini ke Public agar Guest bisa lihat list donasi
Route::get('/campaigns', [CampaignController::class, 'index']);

// ============================================================================
// 2. PROTECTED ROUTES
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // Auth Actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // Features
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/my-reports', [ReportController::class, 'myReports']);
    
    Route::get('/certificates', [App\Http\Controllers\Api\CertificateController::class, 'index']);

    // DONASI & RIWAYAT
    Route::post('/donate', [DonationController::class, 'store']);
    Route::get('/donations/history', [DonationController::class, 'history']); // Endpoint ini sekarang Auto-Update

    // Cek User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/reports/{id}/cancel', [App\Http\Controllers\Api\ReportController::class, 'cancel']);
    Route::post('/reports/delete', [App\Http\Controllers\Api\ReportController::class, 'destroy']);
});
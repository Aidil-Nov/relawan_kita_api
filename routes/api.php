<?php

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Endpoint Public (Bisa diakses tanpa login)
Route::get('/campaigns', [CampaignController::class, 'index']);

// Endpoint Test
Route::get('/test', function () {
    return response()->json(['message' => 'API Berjalan!']);
});

Route::post('/reports', [ReportController::class, 'store']);

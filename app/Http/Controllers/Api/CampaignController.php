<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // Fungsi untuk mengambil semua data campaign
    public function index()
    {
        $campaigns = Campaign::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Campaign',
            'data' => $campaigns
        ], 200);
    }
}
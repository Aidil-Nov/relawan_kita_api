<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Certificate;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        // Ambil sertifikat HANYA milik user yang sedang login
        $certificates = Certificate::where('user_id', $request->user()->id)
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Sertifikat',
            'data' => $certificates
        ], 200);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule; // Import ini untuk validasi update yang lebih rapi

class AuthController extends Controller
{
    // --- LOGIN ---
    public function login(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari User berdasarkan Email
        $user = User::where('email', $request->email)->first();

        // 3. Cek Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah',
            ], 401);
        }

        // 4. Jika Benar, Buat Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    // --- REGISTER ---
    public function register(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'nik' => 'required|string|size:16|unique:users', // Wajib 16 digit & Unik
            'phone' => 'required|string|max:20',
        ]);

        // 2. Buat User Baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nik' => $request->nik,
            'phone' => $request->phone,
            'role' => 'user',
        ]);

        // 3. Langsung buat token (Auto Login setelah daftar)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201);
    }

    // --- UPDATE PROFILE ---
    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Ambil user yang sedang login dari Token

        // 1. Validasi
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            // PENTING: Validasi unique NIK harus mengecualikan ID user yang sedang login
            // Artinya: "Cek apakah NIK ini unik, KECUALI untuk user ini sendiri"
            'nik' => [
                'nullable',
                'string',
                'size:16',
                'unique:users,nik,' . $user->id
            ],
        ]);

        // 2. Update Data
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'nik' => $request->nik,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ], 200);
    }

    // --- LOGOUT (SUDAH DITAMBAHKAN) ---
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil Logout'
        ], 200);
    }
    // --- GANTI PASSWORD ---
    public function updatePassword(Request $request)
    {
        $user = $request->user(); // Ambil user yg login

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed', // confirmed artinya butuh field new_password_confirmation
        ]);

        // 1. Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama salah!',
            ], 400);
        }

        // 2. Update Password Baru
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
        ], 200);
    }
}
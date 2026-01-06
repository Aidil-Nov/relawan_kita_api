<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        // Mapping agar field 'avatar_url' dikirim sebagai 'photo_url' ke Flutter
        $userData = $user->toArray();
        $userData['photo_url'] = $user->avatar_url;
        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $userData,
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
        // Mapping agar field 'avatar_url' dikirim sebagai 'photo_url' ke Flutter
        $userData = $user->toArray();
        $userData['photo_url'] = $user->avatar_url;
        return response()->json([
            'success' => true,
            'message' => 'Registrasi Berhasil',
            'data' => [
                'user' => $userData,
                'token' => $token
            ]
        ], 201);
    }

    // --- UPDATE PROFILE ---
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // 1. Validasi
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'nik' => [
                'nullable',
                'string',
                'size:16',
                'unique:users,nik,' . $user->id
            ],
            // Validasi Foto: Harus Gambar, Max 2MB, Format tertentu
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2. Logic Upload Foto
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada (agar hemat storage)
            if ($user->avatar_url) {
                // Ambil path relatif dari URL (misal: "http://.../storage/avatars/abc.jpg" -> "avatars/abc.jpg")
                $oldPath = str_replace(asset('storage/'), '', $user->avatar_url);
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
            }

            // Simpan foto baru
            $path = $request->file('photo')->store('avatars', 'public');
            $fullUrl = asset('storage/' . $path);

            // Update kolom avatar_url di user object
            $user->avatar_url = $fullUrl;
        }

        // 3. Update Data Teks
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->nik = $request->nik;
        $user->save(); // Simpan perubahan ke DB

        // 4. Return User Data (PENTING: Tambahkan 'photo_url' di response JSON agar Flutter mengerti)
        // Kita mapping 'avatar_url' (DB) menjadi 'photo_url' (Flutter)
        $userData = $user->toArray();
        $userData['photo_url'] = $user->avatar_url;

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'user' => $userData
            ]
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
    // --- RESET PASSWORD (KHUSUS DEVELOPMENT) ---
    public function resetPasswordDev(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'email' => 'required|email|exists:users,email', // Pastikan email ada di DB
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // 2. Cari User
        $user = User::where('email', $request->email)->first();

        // 3. Update Password Langsung
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login.'
        ], 200);
    }
}
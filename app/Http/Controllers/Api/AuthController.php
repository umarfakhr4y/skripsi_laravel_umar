<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login 
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah.',
            ], 401);
        }

        // Cek jika user adalah peserta magang
        $peserta = $user->peserta;
        if ($peserta) {
            // Cek apakah data belum lengkap (nim masih '-')
            if ($peserta->nim === '-') {
                // Generate Token Sanctum agar peserta bisa update data
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'is_incomplete' => true,
                    'message' => 'Silakan lengkapi data diri Anda terlebih dahulu.',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'data' => $user
                ], 200);
            }

            // Jika data sudah lengkap tapi status dinonaktifkan / belum diaktifkan admin (0)
            if ($peserta->status == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda belum aktif. Silakan tunggu konfirmasi dari Admin.',
                ], 403);
            }
        }

        // Generate Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Response ke Flutter
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $user
        ], 200);
    }

    /**
     * Register Peserta atau Mentor
     */
    public function register(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'no_telpon' => 'nullable|string|max:20', // Mentor mungkin tidak butuh ini
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:peserta,mentor',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            $profile = null;

            if ($request->role === 'peserta') {
                $profile = \App\Models\pesertaMagang::create([
                    'user_id' => $user->id,
                    'nama_lengkap' => $request->nama_lengkap,
                    'no_telpon' => $request->no_telpon ?? '-',
                    'nim' => '-',
                    'universitas' => '-',
                    'prodi' => '-',
                    'alamat' => '-',
                    'periode_masuk' => now()->format('Y-m-d'),
                    'periode_keluar' => now()->format('Y-m-d'),
                    'status' => 0, // 0 artinya tidak aktif / pending
                ]);
            } else if ($request->role === 'mentor') {
                // Berikan nilai default sementara untuk field yang required di tabel mentor_magangs
                // Jika nanti ada form khusus mentor, field ini bisa diupdate.
                $divisiDefault = \App\Models\Divisi::first(); 
                
                $profile = \App\Models\mentorMagang::create([
                    'user_id' => $user->id,
                    'nama_lengkap' => $request->nama_lengkap,
                    'divisi_id' => $divisiDefault ? $divisiDefault->id : 1,
                    'nip_karyawan' => 'MENTOR-' . time(),
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'user' => $user,
                    'profile' => $profile
                ]
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat registrasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout 
     */
    public function logout(Request $request)
{
    /** @var \App\Models\User $user */
    $user = $request->user();

    if ($user && $user->currentAccessToken()) {
        // Menghapus token yang sedang digunakan
        $user->currentAccessToken()->delete();
    }

    return response()->json([
        'success' => true,
        'message' => 'Berhasil logout'
    ], 200);
}

//update token fcm
public function updateFcmToken(Request $request)
{
    $request->validate(['fcm_token' => 'required|string']);
    $user = $request->user();
    $user->update(['fcm_token' => $request->fcm_token]);
    
    return response()->json(['success' => true, 'message' => 'FCM Token updated']);
}

}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\mentorMagang;
use App\Models\pesertaMagang;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Update Profile Data (Personal Info)
     */
    public function updateData(Request $request)
    {
        $user = $request->user();

        // Validasi input
        $request->validate([
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'nama_lengkap' => 'nullable|string',
            'no_telpon' => 'nullable|string',
            'alamat' => 'nullable|string',
            'nim' => 'nullable|string',
            'universitas' => 'nullable|string',
            'prodi' => 'nullable|string',
            'password' => 'nullable|string|min:6', // Optional password update
        ]);

        // Update email / password di tabel users
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        
        \Illuminate\Support\Facades\Log::info('ProfileController updateData called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'request_data' => $request->all(),
        ]);

        if ($user->role === 'mentor') {
            $mentor = mentorMagang::where('user_id', $user->id)->first();
            if ($mentor) {
                if ($request->has('nama_lengkap')) $mentor->nama_lengkap = $request->nama_lengkap;
                if ($request->has('no_telpon')) $mentor->no_telpon = $request->no_telpon;
                if ($request->has('alamat')) $mentor->alamat = $request->alamat;
                // Add more fields if needed
                $mentor->save();
            }
        } elseif ($user->role === 'peserta') {
            $peserta = pesertaMagang::where('user_id', $user->id)->first();
            if ($peserta) {
                if ($request->filled('nama_lengkap')) $peserta->nama_lengkap = $request->nama_lengkap;
                if ($request->filled('no_telpon')) $peserta->no_telpon = $request->no_telpon;
                if ($request->filled('alamat')) $peserta->alamat = $request->alamat;
                if ($request->filled('nim')) $peserta->nim = $request->nim;
                if ($request->filled('universitas')) $peserta->universitas = $request->universitas;
                if ($request->filled('prodi')) $peserta->prodi = $request->prodi;
                
                \Illuminate\Support\Facades\Log::info('Updating Peserta', [
                    'request' => $request->all(),
                    'peserta_before' => $peserta->getAttributes(),
                ]);

                $saved = $peserta->save();

                \Illuminate\Support\Facades\Log::info('Peserta saved', ['saved' => $saved]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data peserta tidak ditemukan.'
                ], 404);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate.',
            'data' => $user->role === 'peserta' ? $peserta : null
        ]);
    }

    /**
     * Update Profile Picture
     */
    public function updatePicture(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $path = $file->store('profile_pictures', 'public');

            if ($user->role === 'mentor') {
                $mentor = mentorMagang::where('user_id', $user->id)->first();
                if ($mentor) {
                    // Delete old picture if exists
                    if ($mentor->profile_picture && Storage::disk('public')->exists($mentor->profile_picture)) {
                        Storage::disk('public')->delete($mentor->profile_picture);
                    }
                    $mentor->profile_picture = $path;
                    $mentor->save();
                }
            } elseif ($user->role === 'peserta') {
                $peserta = pesertaMagang::where('user_id', $user->id)->first();
                if ($peserta) {
                    // Delete old picture if exists
                    if ($peserta->profile_picture && Storage::disk('public')->exists($peserta->profile_picture)) {
                        Storage::disk('public')->delete($peserta->profile_picture);
                    }
                    $peserta->profile_picture = $path;
                    $peserta->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
                'path' => asset('storage/' . $path)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengunggah foto.'
        ], 400);
    }
    /**
     * Update Password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.'
        ]);
    }
}

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
        $user = clone $request->user();

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
                if ($request->has('nama_lengkap')) $peserta->nama_lengkap = $request->nama_lengkap;
                if ($request->has('no_telpon')) $peserta->no_telpon = $request->no_telpon;
                if ($request->has('alamat')) $peserta->alamat = $request->alamat;
                if ($request->has('nim')) $peserta->nim = $request->nim;
                if ($request->has('universitas')) $peserta->universitas = $request->universitas;
                if ($request->has('prodi')) $peserta->prodi = $request->prodi;
                $peserta->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate.'
        ]);
    }

    /**
     * Update Profile Picture
     */
    public function updatePicture(Request $request)
    {
        $user = clone $request->user();

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
}

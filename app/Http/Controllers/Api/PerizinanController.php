<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\pesertaMagang;

class PerizinanController extends Controller
{
    // Mengambil semua perizinan milik peserta yang login
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'peserta') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $peserta = pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $perizinan = Perizinan::where('peserta_magang_id', $peserta->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $perizinan
        ]);
    }

    // Peserta mengajukan izin baru
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Pastikan hanya peserta yang bisa mengajukan
        if ($user->role !== 'peserta') {
            return response()->json(['message' => 'Unauthorized. Hanya peserta yang dapat mengajukan perizinan.'], 403);
        }

        // Cari data peserta_magang berdasarkan user_id
        $peserta = pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'jenis_izin' => 'required|string|max:255',
            'keterangan' => 'required|string',
            'tanggal_izin' => 'required|date',
            'file_pendukung' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = null;
        if ($request->hasFile('file_pendukung')) {
            $file = $request->file('file_pendukung');
            // Simpan ke storage/app/public/perizinan_pendukung
            $filePath = $file->store('perizinan_pendukung', 'public');
        }

        $perizinan = Perizinan::create([
            'peserta_magang_id' => $peserta->id,
            'jenis_izin' => $request->jenis_izin,
            'keterangan' => $request->keterangan,
            'tanggal_izin' => $request->tanggal_izin,
            'file_pendukung' => $filePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dibuat',
            'data' => $perizinan
        ], 201);
    }
    // Mentor melihat daftar perizinan peserta bimbingannya
    public function indexMentor(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'mentor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentor = \App\Models\mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['message' => 'Data mentor tidak ditemukan'], 404);
        }

        $pesertaIds = $mentor->peserta()->pluck('id');

        $perizinan = Perizinan::whereIn('peserta_magang_id', $pesertaIds)
            ->with('peserta.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $perizinan
        ]);
    }

    // Mentor mengupdate status perizinan
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'mentor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentor = \App\Models\mentorMagang::where('user_id', $user->id)->first();
        $pesertaIds = $mentor->peserta()->pluck('id');

        $perizinan = Perizinan::whereIn('peserta_magang_id', $pesertaIds)->find($id);
        if (!$perizinan) {
            return response()->json(['message' => 'Data perizinan tidak ditemukan atau bukan milik peserta bimbingan Anda'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,diproses,selesai,ditolak',
            'catatan_mentor' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $perizinan->status = $request->status;
        if ($request->has('catatan_mentor')) {
            $perizinan->catatan_mentor = $request->catatan_mentor;
        }

        $perizinan->save();

        return response()->json([
            'success' => true,
            'message' => 'Status izin berhasil diperbarui',
            'data' => $perizinan
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Absensi;
use App\Models\PesertaMagang;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'status' => 'required|in:hadir,izin,sakit',
            'keterangan' => 'nullable|string',
            'foto_bukti' => 'nullable|image|max:2048', // opsional, max 2MB
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Pastikan yang login adalah peserta magang
        $peserta = PesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json(['message' => 'Hanya peserta magang yang dapat melakukan absen.'], 403);
        }

        $tanggal_hari_ini = Carbon::now()->toDateString();
        $waktu_sekarang = Carbon::now()->toTimeString();

        // Cek apakah hari ini sudah pernah absen
        $absensi = Absensi::where('peserta_magang_id', $peserta->id)
                          ->where('tanggal', $tanggal_hari_ini)
                          ->first();

        // Upload foto bukti (jika ada)
        $foto_path = null;
        if ($request->hasFile('foto_bukti')) {
            $foto_path = $request->file('foto_bukti')->store('absensi', 'public');
        }

        // Skenario 1: Sudah ada record hari ini
        if ($absensi) {
            // Jika sebelumnya status hadir, dan belum absen pulang -> maka lakukan absen pulang
            if ($request->status == 'hadir' && $absensi->waktu_masuk && !$absensi->waktu_keluar) {
                $absensi->update([
                    'waktu_keluar' => $waktu_sekarang,
                    'latitude' => $request->latitude ?? $absensi->latitude,
                    'longitude' => $request->longitude ?? $absensi->longitude,
                ]);
                return response()->json([
                    'message' => 'Berhasil melakukan absen pulang', 
                    'data' => $absensi
                ]);
            }

            return response()->json(['message' => 'Anda sudah melakukan absen hari ini.'], 400);
        }

        // Skenario 2: Belum ada record hari ini -> buat absen baru
        $data_absen = [
            'peserta_magang_id' => $peserta->id,
            'tanggal' => $tanggal_hari_ini,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
            'foto_bukti' => $foto_path,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        // Jika statusnya hadir, set waktu_masuk
        if ($request->status == 'hadir') {
            $data_absen['waktu_masuk'] = $waktu_sekarang;
        }

        $absen_baru = Absensi::create($data_absen);

        return response()->json([
            'message' => 'Berhasil mengirim absen',
            'data' => $absen_baru
        ], 201);
    }
}

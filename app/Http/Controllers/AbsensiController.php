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
            'status' => 'required|in:hadir,izin,sakit,telat',
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
            // Jika sebelumnya status hadir/telat, dan belum absen pulang -> maka lakukan absen pulang
            if (($request->status == 'hadir' || $request->status == 'telat') && $absensi->waktu_masuk && !$absensi->waktu_keluar) {
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

        $statusToSave = $request->status;

        // Cek Batas Waktu Absen (Hanya untuk absen datang/hadir pertama kali)
        if ($request->status == 'hadir') {
            $batas = \App\Models\PengaturanAbsensi::where('mentor_magang_id', $peserta->mentor_magang_id)
                ->where('tanggal_berlaku', '<=', $tanggal_hari_ini)
                ->orderBy('tanggal_berlaku', 'desc')
                ->first();
            
            $waktu_batas = $batas ? $batas->batas_waktu : '08:00:00';

            if ($waktu_sekarang > $waktu_batas) {
                $statusToSave = 'telat';
            }
        }

        // Skenario 2: Belum ada record hari ini -> buat absen baru
        $data_absen = [
            'peserta_magang_id' => $peserta->id,
            'tanggal' => $tanggal_hari_ini,
            'status' => $statusToSave,
            'keterangan' => $request->keterangan,
            'foto_bukti' => $foto_path,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        // Jika statusnya hadir atau telat, set waktu_masuk
        if ($statusToSave == 'hadir' || $statusToSave == 'telat') {
            $data_absen['waktu_masuk'] = $waktu_sekarang;
        }

        $absen_baru = Absensi::create($data_absen);

        if ($statusToSave == 'telat') {
            return response()->json([
                'success' => true,
                'message' => "Anda telah berhasil absen, namun tercatat sebagai TERLAMBAT (lewat dari batas waktu).",
                'data' => $absen_baru
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengirim absen',
            'data' => $absen_baru
        ], 201);
    }
}

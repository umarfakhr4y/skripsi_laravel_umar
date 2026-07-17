<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\mentorMagang;
use Carbon\Carbon;

class MentorController extends Controller
{
    public function getPesertaAbsensi(Request $request)
    {
        $user = $request->user();
        
        // Ensure user is a mentor
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        $tanggal_hari_ini = Carbon::now()->toDateString();

        // Get participants under this mentor with their attendance for today
        $peserta = $mentor->peserta()->with(['user', 'absensi' => function($query) use ($tanggal_hari_ini) {
            $query->where('tanggal', $tanggal_hari_ini);
        }])->get();

        // Format the data
        $data = $peserta->map(function ($p) use ($mentor) {
            $absensi_hari_ini = $p->absensi->first();
            
            // Cek apakah sudah dievaluasi bulan ini
            $months = [
                'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 
                'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
            ];
            $bulanTahun = $months[Carbon::now()->month - 1] . ' ' . Carbon::now()->year;
            $sudahDievaluasi = \App\Models\EvaluasiBulanan::where('peserta_magang_id', $p->id)
                ->where('mentor_magang_id', $mentor->id)
                ->where('bulan_tahun', $bulanTahun)
                ->exists();

            return [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'nama_lengkap' => $p->nama_lengkap,
                'nim' => $p->nim,
                'email' => $p->user ? $p->user->email : null,
                'sudah_absen_masuk' => $absensi_hari_ini ? true : false,
                'sudah_absen_pulang' => ($absensi_hari_ini && $absensi_hari_ini->waktu_keluar) ? true : false,
                'absen_hari_ini' => $absensi_hari_ini,
                'sudah_dievaluasi_bulan_ini' => $sudahDievaluasi
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getLaporanHarian(Request $request)
    {
        $user = $request->user();
        
        // Ensure user is a mentor
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        // Fetch all daily reports for this mentor's participants
        $laporan = \App\Models\LaporanHarian::with(['peserta'])
            ->where('mentor_magang_id', $mentor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $laporan->map(function ($lap) {
            return [
                'id' => $lap->id,
                'peserta_id' => $lap->peserta_magang_id,
                'nama_peserta' => $lap->peserta ? $lap->peserta->nama_lengkap : 'Tanpa Nama',
                'laporan' => $lap->laporan,
                'komentar_mentor' => $lap->komentar_mentor,
                'tanggal' => $lap->created_at ? $lap->created_at->format('Y-m-d H:i:s') : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getLaporanHarianByPeserta(Request $request, $peserta_id)
    {
        $user = $request->user();
        
        // Ensure user is a mentor
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        // Fetch daily reports for specific participant under this mentor
        $laporan = \App\Models\LaporanHarian::with(['peserta'])
            ->where('mentor_magang_id', $mentor->id)
            ->where('peserta_magang_id', $peserta_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $laporan->map(function ($lap) {
            return [
                'id' => $lap->id,
                'peserta_id' => $lap->peserta_magang_id,
                'nama_peserta' => $lap->peserta ? $lap->peserta->nama_lengkap : 'Tanpa Nama',
                'laporan' => $lap->laporan,
                'komentar_mentor' => $lap->komentar_mentor,
                'tanggal' => $lap->created_at ? $lap->created_at->format('Y-m-d H:i:s') : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function storeEvaluasiBulanan(Request $request)
    {
        $user = $request->user();
        
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengirim evaluasi.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'peserta_magang_id' => 'required|exists:peserta_magangs,id',
            'produktivitas' => 'required|integer|min:1|max:5',
            'komunikasi' => 'required|integer|min:1|max:5',
            'keahlian_teknis' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string',
            'bulan_tahun' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluasi = \App\Models\EvaluasiBulanan::create([
            'peserta_magang_id' => $request->peserta_magang_id,
            'mentor_magang_id' => $mentor->id,
            'produktivitas' => $request->produktivitas,
            'komunikasi' => $request->komunikasi,
            'keahlian_teknis' => $request->keahlian_teknis,
            'feedback' => $request->feedback,
            'bulan_tahun' => $request->bulan_tahun,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Evaluasi bulanan berhasil disimpan',
            'data' => $evaluasi
        ], 201);
    }
}

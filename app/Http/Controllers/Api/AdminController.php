<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\pesertaMagang;
use App\Models\mentorMagang;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all Peserta Magang.
     */
    public function getPeserta(Request $request)
    {
        try {
            $peserta = pesertaMagang::with(['user', 'mentor.user', 'mentor.divisi'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data peserta magang',
                'data' => $peserta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data peserta magang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all Mentor.
     */
    public function getMentor(Request $request)
    {
        try {
            $mentor = mentorMagang::with(['user', 'divisi'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data mentor',
                'data' => $mentor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data mentor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all Divisi.
     */
    public function getDivisi(Request $request)
    {
        try {
            $divisi = Divisi::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data divisi',
                'data' => $divisi
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data divisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Kehadiran (Attendance) stats for today.
     */
    public function getKehadiranHariIni(Request $request)
    {
        try {
            $totalActivePeserta = pesertaMagang::where('status', 'Aktif')->count();
            
            $today = \Carbon\Carbon::today()->toDateString();
            
            // Hitung absensi hari ini yang statusnya hadir/terlambat (mengindikasikan masuk)
            $hadirHariIni = \App\Models\Absensi::where('tanggal', $today)
                ->where(function($query) {
                    $query->where('status', 'like', '%Hadir%')
                          ->orWhere('status', 'like', '%hadir%')
                          ->orWhere('status', 'like', '%telat%')
                          ->orWhere('status', 'like', '%telat%');
                })->count();
                
            $persentase = $totalActivePeserta > 0 ? round(($hadirHariIni / $totalActivePeserta) * 100) : 0;
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data kehadiran hari ini',
                'data' => [
                    'total_peserta_aktif' => $totalActivePeserta,
                    'hadir_hari_ini' => $hadirHariIni,
                    'persentase' => $persentase
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kehadiran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Tugas stats.
     */
    public function getTugas(Request $request)
    {
        try {
            $totalTugas = \App\Models\Penugasan::count();
            $tugasAktif = \App\Models\Penugasan::where('status_tugas', 'Aktif')->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data jumlah tugas',
                'data' => [
                    'total_tugas' => $totalTugas,
                    'tugas_aktif' => $tugasAktif
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

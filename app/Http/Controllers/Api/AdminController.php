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
     * Get Detail Peserta.
     */
    public function getDetailPeserta($id)
    {
        try {
            $peserta = \App\Models\pesertaMagang::with(['user'])->find($id);

            if (!$peserta) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            $tugas = \App\Models\Penugasan::where('peserta_magang_id', $peserta->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $bulanIni = \Carbon\Carbon::now()->month;
            $tahunIni = \Carbon\Carbon::now()->year;

            $absensiBulanIni = \App\Models\Absensi::where('peserta_magang_id', $peserta->id)
                ->whereMonth('tanggal', $bulanIni)
                ->whereYear('tanggal', $tahunIni)
                ->get();

            $rekapAbsensi = [
                'hadir' => $absensiBulanIni->where('status', 'hadir')->count(),
                'izin' => $absensiBulanIni->where('status', 'izin')->count(),
                'sakit' => $absensiBulanIni->where('status', 'sakit')->count(),
                'telat' => $absensiBulanIni->where('status', 'telat')->count(),
                'alpa' => $absensiBulanIni->where('status', 'alpa')->count(),
            ];

            $data = [
                'id' => $peserta->id,
                'user_id' => $peserta->user_id,
                'nama_lengkap' => $peserta->nama_lengkap,
                'nim' => $peserta->nim,
                'universitas' => $peserta->universitas,
                'prodi' => $peserta->prodi,
                'periode_masuk' => $peserta->periode_masuk ? \Carbon\Carbon::parse($peserta->periode_masuk)->format('d M Y') : null,
                'periode_keluar' => $peserta->periode_keluar ? \Carbon\Carbon::parse($peserta->periode_keluar)->format('d M Y') : null,
                'no_telpon' => $peserta->no_telpon,
                'status' => $peserta->status,
                'alamat' => $peserta->alamat,
                'email' => $peserta->user ? $peserta->user->email : null,
                'profile_picture_url' => $peserta->profile_picture ? asset('storage/' . $peserta->profile_picture) : null,
                'tugas' => $tugas,
                'rekap_absensi' => $rekapAbsensi
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data detail peserta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Peserta.
     */
    public function updatePeserta(Request $request, $id)
    {
        try {
            $peserta = \App\Models\pesertaMagang::find($id);

            if (!$peserta) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'universitas' => 'nullable|string|max:255',
                'prodi' => 'nullable|string|max:255',
                'no_telpon' => 'nullable|string|max:20',
            ]);

            $peserta->update([
                'nama_lengkap' => $request->nama_lengkap,
                'universitas' => $request->universitas,
                'prodi' => $request->prodi,
                'no_telpon' => $request->no_telpon,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengubah data peserta',
                'data' => $peserta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah data peserta',
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
     * Create new Divisi.
     */
    public function postDivisi(Request $request)
    {
        try {
            $request->validate([
                'nama_divisi' => 'required|string|max:255',
            ]);

            $divisi = Divisi::create([
                'nama_divisi' => $request->nama_divisi,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambah data divisi',
                'data' => $divisi
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah data divisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Divisi.
     */
    public function putDivisi(Request $request, $id)
    {
        try {
            $divisi = Divisi::findOrFail($id);
            $request->validate([
                'nama_divisi' => 'required|string|max:255',
            ]);

            $divisi->update([
                'nama_divisi' => $request->nama_divisi,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengubah data divisi',
                'data' => $divisi
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah data divisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Divisi.
     */
    public function deleteDivisi($id)
    {
        try {
            $divisi = Divisi::findOrFail($id);
            $divisi->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data divisi',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data divisi',
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

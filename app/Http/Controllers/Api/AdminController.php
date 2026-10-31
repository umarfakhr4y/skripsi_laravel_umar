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
     * Get pending registrations (Peserta dengan status = 0 & mentor_magang_id = null)
     */
    public function getPendingRegistrations(Request $request)
    {
        try {
            $pendingPeserta = pesertaMagang::with('user')
                ->where('status', 0)
                ->whereNull('mentor_magang_id')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil pendaftaran pending',
                'data' => $pendingPeserta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil pendaftaran pending',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve pending registration and assign mentor
     */
    public function approveRegistration(Request $request, $id)
    {
        try {
            $peserta = pesertaMagang::find($id);
            if (!$peserta) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            $request->validate([
                'mentor_magang_id' => 'required|exists:mentor_magangs,id',
            ]);

            $peserta->update([
                'mentor_magang_id' => $request->mentor_magang_id,
                'status' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Peserta berhasil disetujui',
                'data' => $peserta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui peserta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject pending registration (Hard Delete)
     */
    public function rejectRegistration($id)
    {
        try {
            DB::beginTransaction();
            
            $peserta = pesertaMagang::find($id);
            if (!$peserta) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            $userId = $peserta->user_id;
            
            // Delete peserta magang
            $peserta->delete();
            
            // Delete user account
            $user = User::find($userId);
            if ($user) {
                $user->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran peserta berhasil ditolak dan dihapus.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak peserta',
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
     * Create new Mentor.
     */
    public function postMentor(Request $request)
    {
        try {
            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'nip_karyawan' => 'nullable|string|max:255',
                'divisi_id' => 'required|exists:divisis,id',
            ]);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'role' => 'mentor',
            ]);

            $mentor = mentorMagang::create([
                'user_id' => $user->id,
                'nama_lengkap' => $request->nama_lengkap,
                'nip_karyawan' => $request->nip_karyawan ?? '-',
                'divisi_id' => $request->divisi_id,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambah mentor baru',
                'data' => $mentor
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah data mentor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Detail Mentor.
     */
    public function getDetailMentor($id)
    {
        try {
            $mentor = \App\Models\mentorMagang::with(['user', 'divisi', 'peserta'])->find($id);

            if (!$mentor) {
                return response()->json(['success' => false, 'message' => 'Mentor tidak ditemukan.'], 404);
            }

            $data = [
                'id' => $mentor->id,
                'user_id' => $mentor->user_id,
                'nama_lengkap' => $mentor->nama_lengkap,
                'nip_karyawan' => $mentor->nip_karyawan,
                'divisi_id' => $mentor->divisi_id,
                'divisi' => $mentor->divisi ? $mentor->divisi->nama_divisi : '-',
                'email' => $mentor->user ? $mentor->user->email : null,
                'profile_picture_url' => $mentor->profile_picture ? asset('storage/' . $mentor->profile_picture) : null,
                'jumlah_peserta' => $mentor->peserta->count(),
                'peserta' => $mentor->peserta->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'nama_lengkap' => $p->nama_lengkap,
                        'universitas' => $p->universitas,
                        'profile_picture_url' => $p->profile_picture ? asset('storage/' . $p->profile_picture) : null,
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data detail mentor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Mentor.
     */
    public function updateMentor(Request $request, $id)
    {
        try {
            $mentor = \App\Models\mentorMagang::find($id);

            if (!$mentor) {
                return response()->json(['success' => false, 'message' => 'Mentor tidak ditemukan.'], 404);
            }

            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'nip_karyawan' => 'nullable|string|max:255',
                'divisi_id' => 'nullable|exists:divisis,id',
            ]);

            $mentor->update([
                'nama_lengkap' => $request->nama_lengkap,
                'nip_karyawan' => $request->nip_karyawan,
                'divisi_id' => $request->divisi_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengubah data mentor',
                'data' => $mentor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah data mentor',
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
                'status' => $peserta->status ? 1 : 0,
                'is_active' => (bool) $peserta->status,
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
     * Toggle status peserta magang (Aktif / Nonaktif).
     */
    public function toggleStatusPeserta($id)
    {
        try {
            $peserta = \App\Models\pesertaMagang::find($id);

            if (!$peserta) {
                return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
            }

            // Toggle the boolean status
            $peserta->status = !$peserta->status;
            $peserta->save();

            $statusText = $peserta->status ? 'Aktif' : 'Nonaktif';

            return response()->json([
                'success' => true,
                'message' => "Status peserta berhasil diubah menjadi $statusText",
                'data' => $peserta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status peserta',
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
            // Hitung seluruh peserta magang yang ada di database
            $totalPeserta = pesertaMagang::count();
            
            $today = \Carbon\Carbon::today()->toDateString();
            
            // Hitung absensi unik peserta hari ini yang berstatus hadir atau telat (atau waktu_masuk terisi)
            $hadirHariIni = \App\Models\Absensi::where('tanggal', $today)
                ->where(function($query) {
                    $query->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), ['hadir', 'telat'])
                          ->orWhereNotNull('waktu_masuk');
                })
                ->distinct('peserta_magang_id')
                ->count('peserta_magang_id');
                
            $persentase = $totalPeserta > 0 ? round(($hadirHariIni / $totalPeserta) * 100) : 0;
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data kehadiran hari ini',
                'data' => [
                    'total_peserta' => $totalPeserta,
                    'total_peserta_aktif' => $totalPeserta,
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

    /**
     * Get Laporan Harian by Peserta (For Admin)
     */
    public function getLaporanHarianByPeserta(Request $request, $peserta_id)
    {
        try {
            $laporan = \App\Models\LaporanHarian::with(['peserta'])
                ->where('peserta_magang_id', $peserta_id)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $laporan->map(function ($lap) {
                return [
                    'id' => $lap->id,
                    'peserta_id' => $lap->peserta_magang_id,
                    'nama_peserta' => $lap->peserta ? $lap->peserta->nama_lengkap : 'Tanpa Nama',
                    'profile_picture_url' => $lap->peserta && $lap->peserta->profile_picture ? asset('storage/' . $lap->peserta->profile_picture) : null,
                    'laporan' => $lap->laporan,
                    'komentar_mentor' => $lap->komentar_mentor,
                    'tanggal' => $lap->created_at ? $lap->created_at->format('Y-m-d H:i:s') : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Bimbingan by Peserta (For Admin)
     */
    public function getBimbinganByPeserta(Request $request, $peserta_id)
    {
        try {
            $bimbingan = \App\Models\Bimbingan::with(['peserta', 'mentor.user'])
                ->where('peserta_magang_id', $peserta_id)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $bimbingan->map(function ($bim) {
                return [
                    'id' => $bim->id,
                    'peserta_id' => $bim->peserta_magang_id,
                    'peserta' => [
                        'nama' => $bim->peserta ? $bim->peserta->nama_lengkap : 'Tanpa Nama',
                        'profile_picture_url' => $bim->peserta && $bim->peserta->profile_picture ? asset('storage/' . $bim->peserta->profile_picture) : null,
                    ],
                    'topik' => $bim->topik,
                    'tanggal' => $bim->tanggal ? \Carbon\Carbon::parse($bim->tanggal)->format('d M Y') : null,
                    'link_meet' => $bim->link_meet,
                    'status' => $bim->status
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bimbingan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

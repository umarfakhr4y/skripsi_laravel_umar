<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\mentorMagang;
use App\Models\PengaturanAbsensi;
use Carbon\Carbon;
use App\Services\FirebaseNotificationService;

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
        $total_peserta = $peserta->count();
        $sudah_clock_in = 0;
        $sudah_laporan = 0;

        $data = $peserta->map(function ($p) use ($mentor, $tanggal_hari_ini, &$sudah_clock_in, &$sudah_laporan) {
            $absensi_hari_ini = $p->absensi->first();
            
            // Cek apakah sudah mengisi laporan hari ini
            $sudah_isi_laporan = \App\Models\LaporanHarian::where('peserta_magang_id', $p->id)
                ->whereDate('created_at', $tanggal_hari_ini)
                ->exists();

            if ($absensi_hari_ini) {
                $sudah_clock_in++;
            }
            if ($sudah_isi_laporan) {
                $sudah_laporan++;
            }
            
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
                'profile_picture' => $p->profile_picture ?? ($p->user ? $p->user->profile_picture : null),
                'profile_picture_url' => ($p->profile_picture || ($p->user && $p->user->profile_picture))
                    ? asset('storage/' . ($p->profile_picture ?: $p->user->profile_picture))
                    : null,
                'sudah_absen_masuk' => $absensi_hari_ini ? true : false,
                'sudah_absen_pulang' => ($absensi_hari_ini && $absensi_hari_ini->waktu_keluar) ? true : false,
                'absen_hari_ini' => $absensi_hari_ini,
                'sudah_isi_laporan_hari_ini' => $sudah_isi_laporan,
                'sudah_dievaluasi_bulan_ini' => $sudahDievaluasi
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => [
                'total_peserta' => $total_peserta,
                'sudah_clock_in' => $sudah_clock_in,
                'sudah_laporan' => $sudah_laporan,
            ],
            'data' => $data
        ]);
    }

    public function getDetailPeserta(Request $request, $id)
    {
        $user = $request->user();
        
        // Ensure user is a mentor
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        // Get participant data
        $peserta = \App\Models\pesertaMagang::with(['user'])
            ->where('id', $id)
            ->where('mentor_magang_id', $mentor->id)
            ->first();

        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan atau bukan bimbingan Anda.'], 404);
        }

        // Ambil daftar tugas yang diberikan mentor ini ke peserta ini
        $tugas = \App\Models\Penugasan::where('peserta_magang_id', $peserta->id)
            ->where('mentor_magang_id', $mentor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil rekap absensi bulan ini
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

        // Prepare response data
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

        $peserta = \App\Models\pesertaMagang::find($request->peserta_magang_id);
        if ($peserta) {
            $notifTitle = 'Evaluasi Bulanan Terbit';
            $notifPesan = 'Mentor telah memberikan hasil evaluasi bulanan Anda untuk periode ' . $request->bulan_tahun . '.';

            \App\Models\Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'evaluasi_baru',
                'judul' => $notifTitle,
                'pesan' => $notifPesan,
                'is_read' => false,
                'data_terkait' => json_encode(['evaluasi_id' => $evaluasi->id])
            ]);

            $pesertaUser = \App\Models\User::find($peserta->user_id);
            if ($pesertaUser) {
                FirebaseNotificationService::sendToUser(
                    $pesertaUser,
                    $notifTitle,
                    $notifPesan,
                    [
                        'tipe' => 'evaluasi_baru',
                        'evaluasi_id' => (string) $evaluasi->id,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Evaluasi bulanan berhasil disimpan',
            'data' => $evaluasi
        ], 201);
    }

    public function getRiwayatEvaluasi(Request $request, $peserta_id = null)
    {
        $user = $request->user();
        
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        $query = \App\Models\EvaluasiBulanan::where('mentor_magang_id', $mentor->id)
                    ->with('peserta.user')
                    ->orderBy('created_at', 'desc');

        if ($peserta_id) {
            $query->where('peserta_magang_id', $peserta_id);
        }

        $evaluasi = $query->get();

        $formattedData = $evaluasi->map(function ($item) {
            $totalRating = $item->produktivitas + $item->komunikasi + $item->keahlian_teknis;
            $ratingAkhir = round($totalRating / 3, 1);

            return [
                'id' => $item->id,
                'bulan_tahun' => $item->bulan_tahun,
                'rating_akhir' => $ratingAkhir,
                'feedback' => $item->feedback,
                'produktivitas' => $item->produktivitas,
                'komunikasi' => $item->komunikasi,
                'keahlian_teknis' => $item->keahlian_teknis,
                'tanggal' => $item->created_at->format('d M Y'),
                'peserta' => [
                    'id' => $item->peserta->id ?? null,
                    'nama' => $item->peserta->nama_lengkap ?? 'Unknown',
                    'nim' => $item->peserta->nim ?? '-',
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil riwayat evaluasi',
            'data' => $formattedData
        ], 200);
    }

    public function getBimbingan(Request $request)
    {
        $user = $request->user();

        $mentor = \App\Models\mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $bimbingans = \App\Models\Bimbingan::where('mentor_magang_id', $mentor->id)
            ->with(['peserta.user'])
            ->orderBy('tanggal', 'desc')
            ->get();

        $formattedData = $bimbingans->map(function ($item) {
            return [
                'id' => $item->id,
                'tanggal' => \Carbon\Carbon::parse($item->tanggal)->format('d M Y'),
                'is_past' => \Carbon\Carbon::parse($item->tanggal)->startOfDay()->lt(\Carbon\Carbon::now()->startOfDay()),
                'topik' => $item->topik,
                'status' => $item->status,
                'link_meet' => $item->link_meet,
                'peserta' => [
                    'id' => $item->peserta->id ?? null,
                    'nama' => $item->peserta->nama_lengkap ?? 'Unknown',
                    'nim' => $item->peserta->nim ?? '-',
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data bimbingan',
            'data' => $formattedData
        ], 200);
    }

    public function updateBimbinganStatus(Request $request, $id)
    {
        $user = $request->user();
        $mentor = \App\Models\mentorMagang::where('user_id', $user->id)->first();
        
        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $bimbingan = \App\Models\Bimbingan::where('id', $id)
            ->where('mentor_magang_id', $mentor->id)
            ->first();

        if (!$bimbingan) {
            return response()->json([
                'success' => false,
                'message' => 'Data bimbingan tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'status' => 'required|string',
            'link_meet' => 'nullable|string'
        ]);

        $bimbingan->update([
            'status' => $request->status,
            'link_meet' => $request->has('link_meet') ? $request->link_meet : $bimbingan->link_meet
        ]);

        $peserta = \App\Models\pesertaMagang::find($bimbingan->peserta_magang_id);
        if ($peserta) {
            $statusIndo = $bimbingan->status;
            if (strtolower($statusIndo) == 'accepted') {
                $statusIndo = 'disetujui';
            } elseif (strtolower($statusIndo) == 'rejected') {
                $statusIndo = 'ditolak';
            } elseif (strtolower($statusIndo) == 'completed') {
                $statusIndo = 'diselesaikan';
            }
            
            $tanggalBimbingan = date('d-m-Y', strtotime($bimbingan->tanggal));

            $notifTitle = 'Update Status Bimbingan';
            $notifPesan = 'Bimbingan Anda pada tanggal ' . $tanggalBimbingan . ' telah ' . $statusIndo . ' oleh mentor.';

            \App\Models\Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'update_bimbingan',
                'judul' => $notifTitle,
                'pesan' => $notifPesan,
                'is_read' => false,
                'data_terkait' => json_encode(['bimbingan_id' => $bimbingan->id])
            ]);

            $pesertaUser = \App\Models\User::find($peserta->user_id);
            if ($pesertaUser) {
                FirebaseNotificationService::sendToUser(
                    $pesertaUser,
                    $notifTitle,
                    $notifPesan,
                    [
                        'tipe' => 'update_bimbingan',
                        'bimbingan_id' => (string) $bimbingan->id,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status bimbingan berhasil diperbarui',
            'data' => $bimbingan
        ], 200);
    }

    public function getPengaturanAbsen(Request $request)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Hanya mentor yang dapat mengakses data ini.'], 403);
        }

        $tanggal_hari_ini = Carbon::now()->toDateString();
        $batas = PengaturanAbsensi::where('mentor_magang_id', $mentor->id)
            ->where('tanggal_berlaku', '<=', $tanggal_hari_ini)
            ->orderBy('tanggal_berlaku', 'desc')
            ->first();
            
        $batasMendatang = PengaturanAbsensi::where('mentor_magang_id', $mentor->id)
            ->where('tanggal_berlaku', '>', $tanggal_hari_ini)
            ->orderBy('tanggal_berlaku', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'batas_waktu_sekarang' => $batas ? $batas->batas_waktu : '08:00:00',
            'batas_waktu_mendatang' => $batasMendatang ? $batasMendatang->batas_waktu : null,
            'tanggal_berlaku_mendatang' => $batasMendatang ? $batasMendatang->tanggal_berlaku : null,
        ]);
    }

    public function setPengaturanAbsen(Request $request)
    {
        $request->validate([
            'batas_waktu' => 'required|date_format:H:i'
        ]);

        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();
        if (!$mentor) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Aturan baru berlaku besok
        $besok = Carbon::tomorrow()->toDateString();
        
        $batas = PengaturanAbsensi::updateOrCreate(
            ['mentor_magang_id' => $mentor->id, 'tanggal_berlaku' => $besok],
            ['batas_waktu' => $request->batas_waktu . ':00']
        );

        return response()->json([
            'success' => true,
            'message' => 'Batas waktu absen berhasil diperbarui dan akan berlaku mulai besok.',
            'data' => $batas
        ]);
    }

    /**
     * Update komentar mentor pada laporan harian
     */
    public function updateKomentarLaporan(Request $request, $id)
    {
        try {
            $request->validate([
                'komentar' => 'required|string',
            ]);

            $user = $request->user();
            $mentor = mentorMagang::where('user_id', $user->id)->first();
            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya mentor yang dapat memberi komentar.',
                ], 403);
            }

            $laporan = \App\Models\LaporanHarian::find($id);
            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan',
                ], 404);
            }

            // Pastikan peserta yang laporannya dikomentari adalah peserta bimbingan mentor ini
            $peserta = \App\Models\pesertaMagang::find($laporan->peserta_magang_id);
            if (!$peserta || $peserta->mentor_magang_id != $mentor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya dapat mengomentari laporan peserta bimbingan Anda.',
                ], 403);
            }

            $laporan->komentar_mentor = $request->komentar;
            $laporan->save();

            // Notifikasi ke peserta
            if ($peserta && $peserta->user_id) {
                \App\Models\Notifikasi::create([
                    'user_id' => $peserta->user_id,
                    'judul' => 'Komentar Laporan Harian',
                    'pesan' => 'Mentor telah memberikan feedback pada laporan harian Anda: "' . \Illuminate\Support\Str::limit($request->komentar, 60) . '"',
                    'tipe' => 'laporan',
                    'data_id' => $laporan->id,
                    'is_read' => false,
                ]);

                $userPeserta = \App\Models\User::find($peserta->user_id);
                if ($userPeserta && $userPeserta->fcm_token) {
                    FirebaseNotificationService::sendToUser(
                        $userPeserta,
                        'Komentar Laporan Harian',
                        'Mentor telah memberikan feedback pada laporan harian Anda.',
                        [
                            'type' => 'laporan',
                            'laporan_id' => (string) $laporan->id,
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil disimpan',
                'data' => $laporan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan komentar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

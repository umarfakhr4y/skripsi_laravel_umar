<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\pesertaMagang;
use App\Services\FirebaseNotificationService;

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

        if ($peserta->mentor_magang_id) {
            $mentor = \App\Models\mentorMagang::find($peserta->mentor_magang_id);
            if ($mentor) {
                \App\Models\Notifikasi::create([
                    'user_id' => $mentor->user_id,
                    'tipe' => 'izin_baru',
                    'judul' => 'Pengajuan Izin Baru',
                    'pesan' => "Peserta " . $peserta->nama_lengkap . " mengajukan izin: " . $request->jenis_izin,
                    'is_read' => false,
                    'data_terkait' => json_encode(['perizinan_id' => $perizinan->id])
                ]);

                $mentorUser = \App\Models\User::find($mentor->user_id);
                if ($mentorUser) {
                    FirebaseNotificationService::sendToUser(
                        $mentorUser,
                        'Pengajuan Izin Baru!',
                        $peserta->nama_lengkap . " mengajukan izin: " . $request->jenis_izin,
                        [
                            'tipe' => 'izin_baru',
                            'perizinan_id' => (string) $perizinan->id,
                        ]
                    );
                }
            }
        }

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

    // Admin melihat daftar seluruh perizinan peserta
    public function indexAdmin(Request $request)
    {
        $perizinan = Perizinan::with('peserta.user')
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

        // Jika perizinan di ACC (selesai), buatkan/update record di tabel Absensi
        if ($perizinan->status === 'selesai') {
            $statusAbsensi = 'izin'; // Default
            $jenis = strtolower(trim($perizinan->jenis_izin));
            
            if ($jenis === 'sakit' || str_contains($jenis, 'sakit')) {
                $statusAbsensi = 'sakit';
            } elseif ($jenis === 'terlambat' || str_contains($jenis, 'terlambat')) {
                $statusAbsensi = 'hadir';
            } else {
                // Untuk "Lainnya" atau jenis lain
                $statusAbsensi = 'izin';
            }

            \App\Models\Absensi::updateOrCreate(
                [
                    'peserta_magang_id' => $perizinan->peserta_magang_id,
                    'tanggal' => $perizinan->tanggal_izin,
                ],
                [
                    'status' => $statusAbsensi,
                    'keterangan' => 'Di-ACC otomatis dari Perizinan (' . $perizinan->jenis_izin . ')',
                ]
            );
        }

        $peserta = \App\Models\pesertaMagang::find($perizinan->peserta_magang_id);
        if ($peserta) {
            $notifTitle = 'Update Status Izin';
            $notifPesan = 'Pengajuan izin ' . $perizinan->jenis_izin . ' Anda telah ' . $perizinan->status . ' oleh Mentor.';

            \App\Models\Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'update_izin',
                'judul' => $notifTitle,
                'pesan' => $notifPesan,
                'is_read' => false,
                'data_terkait' => json_encode(['perizinan_id' => $perizinan->id])
            ]);

            $pesertaUser = \App\Models\User::find($peserta->user_id);
            if ($pesertaUser) {
                FirebaseNotificationService::sendToUser(
                    $pesertaUser,
                    $notifTitle,
                    $notifPesan,
                    [
                        'tipe' => 'update_izin',
                        'perizinan_id' => (string) $perizinan->id,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status izin berhasil diperbarui',
            'data' => $perizinan
        ]);
    }
}

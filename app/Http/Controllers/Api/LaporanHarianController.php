<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\pesertaMagang;
use Illuminate\Support\Facades\Validator;

class LaporanHarianController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        
        $peserta = pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Hanya peserta magang yang dapat mengirim laporan.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'laporan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $laporan = LaporanHarian::create([
            'peserta_magang_id' => $peserta->id,
            'mentor_magang_id' => $peserta->mentor_magang_id,
            'laporan' => $request->laporan,
        ]);

        // Notifikasi ke mentor pembimbing
        if ($peserta->mentor_magang_id) {
            $mentor = \App\Models\mentorMagang::find($peserta->mentor_magang_id);
            if ($mentor && $mentor->user_id) {
                \App\Models\Notifikasi::create([
                    'user_id' => $mentor->user_id,
                    'judul' => 'Laporan Harian Baru',
                    'pesan' => $peserta->nama_lengkap . ' telah mengirimkan laporan harian.',
                    'tipe' => 'laporan',
                    'data_id' => $laporan->id,
                    'is_read' => false,
                ]);

                $userMentor = \App\Models\User::find($mentor->user_id);
                if ($userMentor && $userMentor->fcm_token) {
                    \App\Services\FirebaseNotificationService::sendToUser(
                        $userMentor,
                        'Laporan Harian Baru',
                        $peserta->nama_lengkap . ' telah mengirimkan laporan harian.',
                        [
                            'type' => 'laporan',
                            'peserta_id' => (string) $peserta->id,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan harian berhasil disimpan',
            'data' => $laporan
        ], 201);
    }
}

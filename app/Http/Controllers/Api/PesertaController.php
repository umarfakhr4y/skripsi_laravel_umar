<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EvaluasiBulanan;

class PesertaController extends Controller
{
    /**
     * Get Riwayat Evaluasi untuk Peserta yang sedang login
     */
    public function getEvaluasiBulanan(Request $request)
    {
        $user = $request->user();

        $peserta = \App\Models\pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya peserta magang yang dapat mengakses data ini.'
            ], 403);
        }

        $pesertaId = $peserta->id;

        $evaluasi = EvaluasiBulanan::where('peserta_magang_id', $pesertaId)
            ->with(['mentor.user', 'mentor.divisi'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Format data agar mudah digunakan di Flutter
        $formattedData = $evaluasi->map(function ($item) {
            // Hitung rata-rata rating
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
                'mentor' => [
                    'nama' => $item->mentor->nama_lengkap ?? 'Unknown',
                    'jabatan' => 'Mentor Senior • ' . ($item->mentor->divisi->nama_divisi ?? 'Umum'),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data evaluasi bulanan',
            'data' => $formattedData
        ], 200);
    }

    public function storeBimbingan(Request $request)
    {
        $user = $request->user();

        $peserta = \App\Models\pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'topik' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$peserta->mentor_magang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki mentor.'
            ], 400);
        }

        $bimbingan = \App\Models\Bimbingan::create([
            'peserta_magang_id' => $peserta->id,
            'mentor_magang_id' => $peserta->mentor_magang_id,
            'tanggal' => $request->tanggal,
            'topik' => $request->topik,
            'status' => 'requested'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengajukan bimbingan',
            'data' => $bimbingan
        ], 201);
    }

    public function getRiwayatBimbingan(Request $request)
    {
        $user = $request->user();

        $peserta = \App\Models\pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $bimbingans = \App\Models\Bimbingan::where('peserta_magang_id', $peserta->id)
            ->with(['mentor.user'])
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
                'mentor' => [
                    'nama' => $item->mentor->nama_lengkap ?? 'Unknown',
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data riwayat bimbingan',
            'data' => $formattedData
        ], 200);
    }
}

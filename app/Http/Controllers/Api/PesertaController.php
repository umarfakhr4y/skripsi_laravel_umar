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
                    'nama' => $item->mentor->user->name ?? 'Unknown',
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
}

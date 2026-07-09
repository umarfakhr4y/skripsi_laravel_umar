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

        return response()->json([
            'success' => true,
            'message' => 'Laporan harian berhasil disimpan',
            'data' => $laporan
        ], 201);
    }
}

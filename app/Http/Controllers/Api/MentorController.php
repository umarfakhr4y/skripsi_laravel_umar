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
        $data = $peserta->map(function ($p) {
            $absensi_hari_ini = $p->absensi->first();
            return [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'nama_lengkap' => $p->nama_lengkap,
                'nim' => $p->nim,
                'email' => $p->user ? $p->user->email : null,
                'sudah_absen_masuk' => $absensi_hari_ini ? true : false,
                'sudah_absen_pulang' => ($absensi_hari_ini && $absensi_hari_ini->waktu_keluar) ? true : false,
                'absen_hari_ini' => $absensi_hari_ini
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}

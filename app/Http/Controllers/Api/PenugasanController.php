<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penugasan;
use App\Models\mentorMagang;
use App\Models\pesertaMagang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PenugasanController extends Controller
{
    // Ambil daftar tugas untuk Mentor
    public function indexMentor(Request $request)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentor yang dapat mengakses data penugasan ini.'
            ], 403);
        }

        $penugasans = Penugasan::with(['peserta.user'])
            ->where('mentor_magang_id', $mentor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $penugasans->map(function ($item) {
            return [
                'id' => $item->id,
                'judul_tugas' => $item->judul_tugas,
                'deskripsi' => $item->deskripsi,
                'foto_petunjuk' => $item->foto_petunjuk ?? [],
                'deadline' => $item->deadline ? $item->deadline->format('Y-m-d') : null,
                'status_tugas' => $item->status_tugas,
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                'peserta' => [
                    'id' => $item->peserta->id ?? null,
                    'nama_lengkap' => $item->peserta->nama_lengkap ?? 'Unknown',
                    'nim' => $item->peserta->nim ?? '-',
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Ambil daftar tugas untuk Peserta
    public function indexPeserta(Request $request)
    {
        $user = $request->user();
        $peserta = pesertaMagang::where('user_id', $user->id)->first();

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya peserta yang dapat mengakses data tugas ini.'
            ], 403);
        }

        $penugasans = Penugasan::with(['mentor.user'])
            ->where('peserta_magang_id', $peserta->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $penugasans->map(function ($item) {
            return [
                'id' => $item->id,
                'judul_tugas' => $item->judul_tugas,
                'deskripsi' => $item->deskripsi,
                'foto_petunjuk' => $item->foto_petunjuk ?? [],
                'deadline' => $item->deadline ? $item->deadline->format('Y-m-d') : null,
                'status_tugas' => $item->status_tugas,
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                'mentor' => [
                    'id' => $item->mentor->id ?? null,
                    'nama_lengkap' => $item->mentor->nama_lengkap ?? 'Mentor',
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Simpan tugas baru oleh Mentor (support upload lebih dari 1 foto petunjuk)
    public function store(Request $request)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentor yang dapat membuat penugasan.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'peserta_magang_id' => 'required|exists:peserta_magangs,id',
            'judul_tugas' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'deadline' => 'required|date',
            'foto_petunjuk' => 'nullable',
            'foto_petunjuk.*' => 'nullable|file|max:20480', // maksimal 20MB per foto
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $fotoPaths = [];
        $allFiles = $request->allFiles();
        $files = $allFiles['foto_petunjuk'] ?? ($allFiles['foto_petunjuk[]'] ?? ($request->file('foto_petunjuk') ?: $request->file('foto_petunjuk[]')));
        if ($files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('penugasans', 'public');
                        $fotoPaths[] = $path;
                    }
                }
            } elseif ($files->isValid()) {
                $path = $files->store('penugasans', 'public');
                $fotoPaths[] = $path;
            }
        }

        $penugasan = Penugasan::create([
            'mentor_magang_id' => $mentor->id,
            'peserta_magang_id' => $request->peserta_magang_id,
            'judul_tugas' => $request->judul_tugas,
            'deskripsi' => $request->deskripsi,
            'foto_petunjuk' => !empty($fotoPaths) ? $fotoPaths : null,
            'deadline' => $request->deadline,
            'status_tugas' => 'Aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dibuat dan diberikan kepada peserta',
            'data' => $penugasan
        ], 201);
    }

    // Update status tugas (Aktif, Ditinjau, Selesai)
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_tugas' => 'required|string|in:Aktif,Ditinjau,Selesai'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $penugasan = Penugasan::find($id);
        if (!$penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan'
            ], 404);
        }

        $penugasan->update([
            'status_tugas' => $request->status_tugas
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui',
            'data' => $penugasan
        ]);
    }

    // Hapus tugas
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentor yang dapat menghapus tugas ini.'
            ], 403);
        }

        $penugasan = Penugasan::where('id', $id)
            ->where('mentor_magang_id', $mentor->id)
            ->first();

        if (!$penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        if (!empty($penugasan->foto_petunjuk)) {
            foreach ($penugasan->foto_petunjuk as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $penugasan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus'
        ]);
    }
}

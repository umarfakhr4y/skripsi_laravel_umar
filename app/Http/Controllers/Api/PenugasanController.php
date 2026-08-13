<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penugasan;
use App\Models\mentorMagang;
use App\Models\pesertaMagang;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

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
                'catatan_peserta' => $item->catatan_peserta,
                'file_pengumpulan' => $item->file_pengumpulan ?? [],
                'catatan_mentor' => $item->catatan_mentor,
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
                'catatan_peserta' => $item->catatan_peserta,
                'file_pengumpulan' => $item->file_pengumpulan ?? [],
                'catatan_mentor' => $item->catatan_mentor,
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

        $peserta = pesertaMagang::find($request->peserta_magang_id);
        if ($peserta) {
            Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'tugas_baru',
                'judul' => 'Tugas Baru Diberikan',
                'pesan' => "Mentor telah memberikan tugas baru: " . $request->judul_tugas,
                'is_read' => false,
                'data_terkait' => json_encode([
                    'penugasan_id' => $penugasan->id
                ])
            ]);

            // Push Notification FCM
            $pesertaUser = User::find($peserta->user_id);
            if ($pesertaUser && $pesertaUser->fcm_token) {
                try {
                    $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
                    $messaging = $factory->createMessaging();

                    $notification = FcmNotification::create(
                        'Tugas Baru Diberikan!', 
                        "Mentor telah memberikan tugas baru: " . $request->judul_tugas
                    );

                    $androidConfig = \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                        ],
                    ]);

                    $message = CloudMessage::withTarget('token', $pesertaUser->fcm_token)
                        ->withNotification($notification)
                        ->withAndroidConfig($androidConfig)
                        ->withData([
                            'tipe' => 'tugas_baru',
                            'penugasan_id' => (string) $penugasan->id
                        ]);

                    $messaging->send($message);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("FCM Send Error: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dibuat dan diberikan kepada peserta',
            'data' => $penugasan
        ], 201);
    }

    // Update semua isi tugas oleh Mentor
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentor yang dapat mengubah penugasan.'
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

        $validator = Validator::make($request->all(), [
            'peserta_magang_id' => 'nullable|exists:peserta_magangs,id',
            'judul_tugas' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_tugas' => 'nullable|string|in:Aktif,Ditinjau,Selesai',
            'foto_petunjuk' => 'nullable',
            'foto_petunjuk.*' => 'nullable|file|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $fotoPaths = $penugasan->foto_petunjuk ?? [];
        if ($request->has('existing_foto_petunjuk')) {
            $keptPaths = $request->input('existing_foto_petunjuk');
            if (empty($keptPaths) || $keptPaths === '' || $keptPaths === 'empty') {
                $keptPaths = [];
            } elseif (!is_array($keptPaths)) {
                $keptPaths = [$keptPaths];
            }
            if (!empty($penugasan->foto_petunjuk)) {
                foreach ($penugasan->foto_petunjuk as $oldPath) {
                    if (!in_array($oldPath, $keptPaths) && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            }
            $fotoPaths = array_values($keptPaths);
        }

        $allFiles = $request->allFiles();
        $files = $allFiles['foto_petunjuk'] ?? ($allFiles['foto_petunjuk[]'] ?? ($request->file('foto_petunjuk') ?: $request->file('foto_petunjuk[]')));
        if ($files) {
            $newPaths = [];
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('penugasans', 'public');
                        $newPaths[] = $path;
                    }
                }
            } elseif ($files->isValid()) {
                $path = $files->store('penugasans', 'public');
                $newPaths[] = $path;
            }
            if (!empty($newPaths)) {
                $fotoPaths = array_merge($fotoPaths, $newPaths);
            }
        }

        $penugasan->update([
            'peserta_magang_id' => $request->peserta_magang_id ?? $penugasan->peserta_magang_id,
            'judul_tugas' => $request->judul_tugas ?? $penugasan->judul_tugas,
            'deskripsi' => $request->deskripsi ?? $penugasan->deskripsi,
            'deadline' => $request->deadline ?? $penugasan->deadline,
            'status_tugas' => $request->status_tugas ?? $penugasan->status_tugas,
            'foto_petunjuk' => $fotoPaths,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil diperbarui',
            'data' => $penugasan
        ]);
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

    // Pengumpulan tugas oleh Peserta Magang
    public function submitTugas(Request $request, $id)
    {
        $user = $request->user();
        $peserta = pesertaMagang::where('user_id', $user->id)->first();

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya peserta magang yang dapat mengumpulkan tugas.'
            ], 403);
        }

        $penugasan = Penugasan::where('id', $id)
            ->where('peserta_magang_id', $peserta->id)
            ->first();

        if (!$penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan atau bukan tugas Anda.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'catatan_peserta' => 'nullable|string',
            'file_pengumpulan' => 'nullable',
            'file_pengumpulan.*' => 'nullable|file|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $filePaths = $penugasan->file_pengumpulan ?? [];
        $allFiles = $request->allFiles();
        $files = $allFiles['file_pengumpulan'] ?? ($allFiles['file_pengumpulan[]'] ?? ($request->file('file_pengumpulan') ?: $request->file('file_pengumpulan[]')));

        if ($files) {
            $newPaths = [];
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('pengumpulans', 'public');
                        $newPaths[] = $path;
                    }
                }
            } elseif ($files->isValid()) {
                $path = $files->store('pengumpulans', 'public');
                $newPaths[] = $path;
            }
            if (!empty($newPaths)) {
                if (!empty($penugasan->file_pengumpulan)) {
                    foreach ($penugasan->file_pengumpulan as $oldPath) {
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                }
                $filePaths = $newPaths;
            }
        }

        $penugasan->update([
            'catatan_peserta' => $request->catatan_peserta ?? $penugasan->catatan_peserta,
            'file_pengumpulan' => $filePaths,
            'status_tugas' => 'Ditinjau',
        ]);

        $mentorTugas = mentorMagang::find($penugasan->mentor_magang_id);
        if ($mentorTugas) {
            Notifikasi::create([
                'user_id' => $mentorTugas->user_id,
                'tipe' => 'tugas_dikumpulkan',
                'judul' => 'Tugas Dikumpulkan',
                'pesan' => "Peserta " . $peserta->nama_lengkap . " telah mengumpulkan tugas: " . $penugasan->judul_tugas,
                'is_read' => false,
                'data_terkait' => json_encode([
                    'penugasan_id' => $penugasan->id
                ])
            ]);

            $mentorUser = User::find($mentorTugas->user_id);
            if ($mentorUser && $mentorUser->fcm_token) {
                try {
                    $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
                    $messaging = $factory->createMessaging();
                    $notification = FcmNotification::create(
                        'Tugas Dikumpulkan!', 
                        $peserta->nama_lengkap . " telah mengumpulkan tugas: " . $penugasan->judul_tugas
                    );
                    $androidConfig = \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => ['sound' => 'default']
                    ]);
                    $message = CloudMessage::withTarget('token', $mentorUser->fcm_token)
                        ->withNotification($notification)
                        ->withAndroidConfig($androidConfig)
                        ->withData([
                            'tipe' => 'tugas_dikumpulkan', 
                            'penugasan_id' => (string) $penugasan->id
                        ]);
                    $messaging->send($message);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("FCM Send Error: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan dan menunggu review mentor.',
            'data' => $penugasan
        ]);
    }

    // Review tugas oleh Mentor (ACC atau Reject/Revisi)
    public function reviewTugas(Request $request, $id)
    {
        $user = $request->user();
        $mentor = mentorMagang::where('user_id', $user->id)->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentor yang dapat me-review tugas.'
            ], 403);
        }

        $penugasan = Penugasan::where('id', $id)
            ->where('mentor_magang_id', $mentor->id)
            ->first();

        if (!$penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:acc,reject',
            'catatan_mentor' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->action === 'acc') {
            $penugasan->update([
                'status_tugas' => 'Selesai',
                'catatan_mentor' => $request->catatan_mentor ?? $penugasan->catatan_mentor,
            ]);
            $message = 'Tugas berhasil disetujui (ACC).';
            $notifTitle = 'Tugas Disetujui';
            $notifPesan = 'Tugas "' . $penugasan->judul_tugas . '" telah disetujui oleh Mentor.';
        } else {
            $penugasan->update([
                'status_tugas' => 'Revisi',
                'catatan_mentor' => $request->catatan_mentor,
            ]);
            $message = 'Tugas dikembalikan ke peserta untuk revisi.';
            $notifTitle = 'Tugas Perlu Revisi';
            $notifPesan = 'Tugas "' . $penugasan->judul_tugas . '" dikembalikan untuk revisi.';
        }

        $peserta = pesertaMagang::find($penugasan->peserta_magang_id);
        if ($peserta) {
            Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'review_tugas',
                'judul' => $notifTitle,
                'pesan' => $notifPesan,
                'is_read' => false,
                'data_terkait' => json_encode(['penugasan_id' => $penugasan->id])
            ]);

            $pesertaUser = User::find($peserta->user_id);
            if ($pesertaUser && $pesertaUser->fcm_token) {
                try {
                    $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
                    $messaging = $factory->createMessaging();
                    $notification = FcmNotification::create($notifTitle, $notifPesan);
                    $androidConfig = \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => ['sound' => 'default']
                    ]);
                    $fcmMessage = CloudMessage::withTarget('token', $pesertaUser->fcm_token)
                        ->withNotification($notification)
                        ->withAndroidConfig($androidConfig)
                        ->withData([
                            'tipe' => 'review_tugas', 
                            'penugasan_id' => (string) $penugasan->id
                        ]);
                    $messaging->send($fcmMessage);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("FCM Send Error: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $penugasan
        ]);
    }
}

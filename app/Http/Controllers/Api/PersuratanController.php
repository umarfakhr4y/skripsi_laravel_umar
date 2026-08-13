<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persuratan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\pesertaMagang;

class PersuratanController extends Controller
{
    // Mengambil semua surat milik peserta yang login
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

        $surat = Persuratan::where('peserta_magang_id', $peserta->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $surat
        ]);
    }

    // Peserta mengajukan surat baru
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Pastikan hanya peserta yang bisa mengajukan
        if ($user->role !== 'peserta') {
            return response()->json(['message' => 'Unauthorized. Hanya peserta yang dapat mengajukan surat.'], 403);
        }

        // Cari data peserta_magang berdasarkan user_id
        $peserta = pesertaMagang::where('user_id', $user->id)->first();
        if (!$peserta) {
            return response()->json(['message' => 'Data peserta tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'jenis_surat' => 'required|string|max:255',
            'keperluan' => 'required|string',
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
            // Simpan ke storage/app/public/persuratan_pendukung
            $filePath = $file->store('persuratan_pendukung', 'public');
        }

        $surat = Persuratan::create([
            'peserta_magang_id' => $peserta->id,
            'jenis_surat' => $request->jenis_surat,
            'keperluan' => $request->keperluan,
            'file_pendukung' => $filePath,
            'status' => 'pending', // Default status
        ]);

        if ($peserta->mentor_magang_id) {
            $mentor = \App\Models\mentorMagang::find($peserta->mentor_magang_id);
            if ($mentor) {
                \App\Models\Notifikasi::create([
                    'user_id' => $mentor->user_id,
                    'tipe' => 'persuratan_baru',
                    'judul' => 'Pengajuan Persuratan Baru',
                    'pesan' => "Peserta " . $peserta->nama_lengkap . " mengajukan persuratan: " . $request->jenis_surat,
                    'is_read' => false,
                    'data_terkait' => json_encode(['persuratan_id' => $surat->id])
                ]);

                $mentorUser = \App\Models\User::find($mentor->user_id);
                if ($mentorUser && $mentorUser->fcm_token) {
                    try {
                        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
                        $messaging = $factory->createMessaging();
                        $notification = \Kreait\Firebase\Messaging\Notification::create(
                            'Pengajuan Persuratan Baru!', 
                            $peserta->nama_lengkap . " mengajukan persuratan: " . $request->jenis_surat
                        );
                        $androidConfig = \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                            'priority' => 'high',
                            'notification' => ['sound' => 'default']
                        ]);
                        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $mentorUser->fcm_token)
                            ->withNotification($notification)
                            ->withAndroidConfig($androidConfig)
                            ->withData([
                                'tipe' => 'persuratan_baru', 
                                'persuratan_id' => (string) $surat->id
                            ]);
                        $messaging->send($message);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("FCM Send Error: " . $e->getMessage());
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan surat berhasil dibuat',
            'data' => $surat
        ], 201);
    }

    // Mentor melihat daftar persuratan peserta bimbingannya
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

        $surat = Persuratan::whereIn('peserta_magang_id', $pesertaIds)
            ->with('peserta.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $surat
        ]);
    }

    // Mentor mengupdate status persuratan
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'mentor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentor = \App\Models\mentorMagang::where('user_id', $user->id)->first();
        $pesertaIds = $mentor->peserta()->pluck('id');

        $surat = Persuratan::whereIn('peserta_magang_id', $pesertaIds)->find($id);
        if (!$surat) {
            return response()->json(['message' => 'Data persuratan tidak ditemukan atau bukan milik peserta bimbingan Anda'], 404);
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

        $surat->status = $request->status;
        if ($request->has('catatan_mentor')) {
            $surat->catatan_mentor = $request->catatan_mentor;
        }

        // Mentor bisa mengupload dokumen balasan jika diperlukan
        if ($request->hasFile('link_dokumen')) {
            $file = $request->file('link_dokumen');
            $filePath = $file->store('persuratan_mentor', 'public');
            $surat->link_dokumen = $filePath;
        } elseif ($request->has('link_dokumen') && is_string($request->link_dokumen)) {
            $surat->link_dokumen = $request->link_dokumen;
        }

        $surat->save();

        $peserta = \App\Models\pesertaMagang::find($surat->peserta_magang_id);
        if ($peserta) {
            $notifTitle = 'Update Status Surat';
            $notifPesan = 'Pengajuan surat ' . $surat->jenis_surat . ' Anda telah ' . $surat->status . ' oleh Mentor.';

            \App\Models\Notifikasi::create([
                'user_id' => $peserta->user_id,
                'tipe' => 'update_surat',
                'judul' => $notifTitle,
                'pesan' => $notifPesan,
                'is_read' => false,
                'data_terkait' => json_encode(['persuratan_id' => $surat->id])
            ]);

            $pesertaUser = \App\Models\User::find($peserta->user_id);
            if ($pesertaUser && $pesertaUser->fcm_token) {
                try {
                    $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
                    $messaging = $factory->createMessaging();
                    $notification = \Kreait\Firebase\Messaging\Notification::create($notifTitle, $notifPesan);
                    $androidConfig = \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => ['sound' => 'default']
                    ]);
                    $fcmMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $pesertaUser->fcm_token)
                        ->withNotification($notification)
                        ->withAndroidConfig($androidConfig)
                        ->withData([
                            'tipe' => 'update_surat', 
                            'persuratan_id' => (string) $surat->id
                        ]);
                    $messaging->send($fcmMessage);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("FCM Send Error: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status surat berhasil diperbarui',
            'data' => $surat
        ]);
    }
}

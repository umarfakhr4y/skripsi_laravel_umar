<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        if ($this->role === 'peserta') {
            $profileData = $this->peserta;
        } elseif ($this->role === 'mentor') {
            $profileData = $this->mentor;
            if ($profileData) {
                $profileData->load('divisi');
            }
        } elseif ($this->role === 'admin') {
            $profileData = (object) [
                'id' => $this->id,
                'nama_lengkap' => 'Administrator',
                'email' => $this->email,
                'profile_picture' => $this->profile_picture,
                'profile_picture_url' => $this->profile_picture ? asset('storage/' . $this->profile_picture) : null,
            ];
        } else {
            $profileData = null;
        }

        $sudahAbsen = false;
        $dataAbsenHariIni = null;
        $sudahIsiLaporan = false;
        $batasWaktuAbsen = '08:00:00';
        $ringkasanMingguan = [
            'tugas_selesai' => 0,
            'kehadiran_tepat_waktu' => 0,
        ];

        if ($this->role === 'peserta' && $this->peserta) {
            $now = \Carbon\Carbon::now();
            $startOfWeek = $now->copy()->startOfWeek()->toDateString();
            $endOfWeek = $now->copy()->endOfWeek()->toDateString();

            // Hitung kehadiran masuk tepat waktu (tidak telat) di minggu ini
            $kehadiranTepatWaktu = \App\Models\Absensi::where('peserta_magang_id', $this->peserta->id)
                ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
                ->where('status', 'hadir')
                ->whereNotNull('waktu_masuk')
                ->count();

            // Hitung tugas selesai di minggu ini
            $tugasSelesai = \App\Models\Penugasan::where('peserta_magang_id', $this->peserta->id)
                ->where(function ($q) {
                    $q->where('status_tugas', 'Selesai')
                      ->orWhere('status_tugas', 'selesai')
                      ->orWhere('status_tugas', 'completed');
                })
                ->where(function ($q) use ($startOfWeek, $endOfWeek, $now) {
                    $q->whereBetween('updated_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                      ->orWhereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                      ->orWhereBetween('deadline', [$startOfWeek, $endOfWeek]);
                })
                ->count();

            $ringkasanMingguan = [
                'tugas_selesai' => $tugasSelesai,
                'kehadiran_tepat_waktu' => $kehadiranTepatWaktu,
            ];

            $absen = \App\Models\Absensi::where('peserta_magang_id', $this->peserta->id)
                            ->where('tanggal', \Carbon\Carbon::now()->toDateString())
                            ->first();
            
            if ($absen) {
                $sudahAbsen = true;
                $dataAbsenHariIni = $absen;
            }

            $laporan = \App\Models\LaporanHarian::where('peserta_magang_id', $this->peserta->id)
                            ->whereDate('created_at', \Carbon\Carbon::now()->toDateString())
                            ->first();
            
            if ($laporan) {
                $sudahIsiLaporan = true;
            }

            if ($this->peserta->mentor_magang_id) {
                $batas = \App\Models\PengaturanAbsensi::where('mentor_magang_id', $this->peserta->mentor_magang_id)
                    ->where('tanggal_berlaku', '<=', \Carbon\Carbon::now()->toDateString())
                    ->orderBy('tanggal_berlaku', 'desc')
                    ->first();
                if ($batas) {
                    $batasWaktuAbsen = $batas->batas_waktu;
                }
            }
        }

        if ($profileData && isset($profileData->profile_picture) && $profileData->profile_picture && !isset($profileData->profile_picture_url)) {
            $profileData->profile_picture_url = asset('storage/' . $profileData->profile_picture);
        } else if ($profileData && !isset($profileData->profile_picture_url)) {
            $profileData->profile_picture_url = null;
        }

        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'role'       => $this->role,
            'sudah_absen'=> $sudahAbsen,
            'sudah_isi_laporan' => $sudahIsiLaporan,
            'absen_hari_ini' => $dataAbsenHariIni,
            'batas_waktu_absen' => substr($batasWaktuAbsen, 0, 5),
            'ringkasan_mingguan' => $ringkasanMingguan,
            'data'       => $profileData,
        ];
    }
}

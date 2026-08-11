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
        $profileData = $this->role === 'peserta' ? $this->peserta : $this->mentor;

        if ($this->role === 'mentor' && $profileData) {
            $profileData->load('divisi');
        }

        $sudahAbsen = false;
        $dataAbsenHariIni = null;
        $sudahIsiLaporan = false;
        $batasWaktuAbsen = '08:00:00';

        if ($this->role === 'peserta' && $this->peserta) {
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

        if ($profileData && isset($profileData->profile_picture) && $profileData->profile_picture) {
            $profileData->profile_picture_url = asset('storage/' . $profileData->profile_picture);
        } else if ($profileData) {
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
            'data'       => $profileData,
        ];
    }
}

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

        $sudahAbsen = false;
        $dataAbsenHariIni = null;

        if ($this->role === 'peserta' && $this->peserta) {
            $absen = \App\Models\Absensi::where('peserta_magang_id', $this->peserta->id)
                            ->where('tanggal', \Carbon\Carbon::now()->toDateString())
                            ->first();
            
            if ($absen) {
                $sudahAbsen = true;
                $dataAbsenHariIni = $absen;
            }
        }

        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'role'       => $this->role,
            'sudah_absen'=> $sudahAbsen,
            'absen_hari_ini' => $dataAbsenHariIni,
            'data'       => $profileData,
        ];
    }
}

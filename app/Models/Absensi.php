<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'peserta_magang_id',
        'tanggal',
        'waktu_masuk',
        'waktu_keluar',
        'status',
        'keterangan',
        'foto_bukti',
        'latitude',
        'longitude'
    ];

    public function peserta()
    {
        return $this->belongsTo(PesertaMagang::class, 'peserta_magang_id');
    }
}

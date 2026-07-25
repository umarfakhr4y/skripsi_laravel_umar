<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perizinan extends Model
{
    protected $fillable = [
        'peserta_magang_id',
        'jenis_izin',
        'keterangan',
        'tanggal_izin',
        'file_pendukung',
        'status',
        'catatan_mentor',
    ];

    public function peserta()
    {
        return $this->belongsTo(PesertaMagang::class, 'peserta_magang_id');
    }
}

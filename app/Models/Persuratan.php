<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persuratan extends Model
{
    protected $fillable = [
        'peserta_magang_id',
        'jenis_surat',
        'keperluan',
        'file_pendukung',
        'status',
        'link_dokumen',
        'catatan_mentor',
    ];

    public function peserta()
    {
        return $this->belongsTo(pesertaMagang::class, 'peserta_magang_id');
    }
}

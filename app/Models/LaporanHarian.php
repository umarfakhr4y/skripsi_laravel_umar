<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    protected $table = 'laporan_harians';

    protected $fillable = [
        'peserta_magang_id',
        'mentor_magang_id',
        'laporan',
        'komentar_mentor',
    ];

    public function peserta()
    {
        return $this->belongsTo(pesertaMagang::class, 'peserta_magang_id');
    }

    public function mentor()
    {
        return $this->belongsTo(mentorMagang::class, 'mentor_magang_id');
    }
}

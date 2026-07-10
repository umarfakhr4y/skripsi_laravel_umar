<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluasiBulanan extends Model
{
    protected $fillable = [
        'peserta_magang_id',
        'mentor_magang_id',
        'produktivitas',
        'komunikasi',
        'keahlian_teknis',
        'feedback',
        'bulan_tahun',
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

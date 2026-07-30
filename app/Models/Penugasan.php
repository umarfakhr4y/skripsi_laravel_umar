<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penugasan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'foto_petunjuk' => 'array',
        'deadline' => 'date',
    ];

    public function mentor()
    {
        return $this->belongsTo(mentorMagang::class, 'mentor_magang_id');
    }

    public function peserta()
    {
        return $this->belongsTo(pesertaMagang::class, 'peserta_magang_id');
    }
}

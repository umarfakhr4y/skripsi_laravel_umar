<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengaturanAbsensi extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function mentor()
    {
        return $this->belongsTo(mentorMagang::class, 'mentor_magang_id');
    }
}

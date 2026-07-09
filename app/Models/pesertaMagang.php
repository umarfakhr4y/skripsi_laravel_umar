<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pesertaMagang extends Model
{
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function mentor() {
        return $this->belongsTo(mentorMagang::class, 'mentor_magang_id');
    }

    public function absensi() {
        return $this->hasMany(Absensi::class, 'peserta_magang_id');
    }
}

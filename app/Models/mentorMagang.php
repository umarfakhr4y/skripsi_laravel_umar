<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mentorMagang extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function divisi()
    {
        return $this->belongsTo(Divisi::class); 
    }

    public function peserta()
    {
        return $this->hasMany(pesertaMagang::class, 'mentor_magang_id');
    }
}

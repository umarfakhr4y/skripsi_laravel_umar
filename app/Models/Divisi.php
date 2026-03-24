<?php

namespace App\Models;

use app\Models\mentorMagang;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    protected $fillable = ['nama_divisi', 'user_id'];
    public function mentors()
    {
        return $this->hasMany(mentorMagang::class); // Satu divisi memiliki BANYAK mentor
    }
}

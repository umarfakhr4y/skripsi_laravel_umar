<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasis';

    protected $fillable = [
        'user_id',
        'tipe',
        'judul',
        'pesan',
        'is_read',
        'data_terkait',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data_terkait' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

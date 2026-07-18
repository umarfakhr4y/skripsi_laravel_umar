<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\pesertaMagang;
use App\Models\mentorMagang;
use App\Models\Divisi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $itDivisi = Divisi::where('nama_divisi', 'IT Development')->first();
        // ADMIN
        User::create([
            'email' => 'admin@mail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $mentorId = null;
        // MENTOR
        if ($itDivisi) {
            // Buat Mentor 1
            DB::transaction(function () use ($itDivisi, &$mentorId) {
                $user = User::create([
                    'email' => 'mentor@vocasia.com',
                    'password' => Hash::make('123123123'),
                    'role' => 'mentor',
                ]);

                $mentor = mentorMagang::create([
                    'user_id' => $user->id,
                    'divisi_id' => $itDivisi->id, // Menggunakan ID dari divisi yang dicari
                    'nip_karyawan' => 'VOC-IT-001',
                    'nama_lengkap' => 'Andi Wijaya',
                ]);
                $mentorId = $mentor->id;
            });
        }

        // PESERTA
        DB::transaction(function () use ($mentorId) {
            $userPeserta = User::create([
                'email' => 'peserta@vocasia.com',
                'password' => Hash::make('123123123'),
                'role' => 'peserta',
            ]);

            pesertaMagang::create([
                'user_id' => $userPeserta->id,
                'nim' => '1234567890',
                'nama_lengkap' => 'Budi Santoso',
                'prodi' => 'Pendidikan Teknik Informatika',
                'mentor_magang_id' => $mentorId,
                'alamat' => 'Jakarta Timur',
            ]);
        });
    }
}

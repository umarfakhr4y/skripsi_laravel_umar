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
        $dmDivisi = Divisi::where('nama_divisi', 'Digital Marketing')->first();
        $ccDivisi = Divisi::where('nama_divisi', 'Content creator')->first();
        // ADMIN
        User::create([
            'email' => 'admin@mail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $mentorIds = [];
        // MENTOR
        if ($itDivisi) {
            // Buat Mentor 1
            DB::transaction(function () use ($itDivisi, &$mentorIds) {
                $user = User::create([
                    'email' => 'mentor@vocasia.com',
                    'password' => Hash::make('123123123'),
                    'role' => 'mentor',
                ]);

                $mentor = mentorMagang::create([
                    'user_id' => $user->id,
                    'divisi_id' => $itDivisi->id,
                    'nip_karyawan' => 'VOC-IT-001',
                    'nama_lengkap' => 'Andi Wijaya',
                ]);
                $mentorIds[] = $mentor->id;
            });
        }

        if ($dmDivisi) {
            // Buat Mentor 2
            DB::transaction(function () use ($dmDivisi, &$mentorIds) {
                $user = User::create([
                    'email' => 'mentor2@vocasia.com',
                    'password' => Hash::make('123123123'),
                    'role' => 'mentor',
                ]);

                $mentor = mentorMagang::create([
                    'user_id' => $user->id,
                    'divisi_id' => $dmDivisi->id,
                    'nip_karyawan' => 'VOC-DM-001',
                    'nama_lengkap' => 'Siti Nurhaliza',
                ]);
                $mentorIds[] = $mentor->id;
            });
        }

        if ($ccDivisi) {
            // Buat Mentor 3
            DB::transaction(function () use ($ccDivisi, &$mentorIds) {
                $user = User::create([
                    'email' => 'mentor3@vocasia.com',
                    'password' => Hash::make('123123123'),
                    'role' => 'mentor',
                ]);

                $mentor = mentorMagang::create([
                    'user_id' => $user->id,
                    'divisi_id' => $ccDivisi->id,
                    'nip_karyawan' => 'VOC-CC-001',
                    'nama_lengkap' => 'Bambang Pamungkas',
                ]);
                $mentorIds[] = $mentor->id;
            });
        }

        // PESERTA
        DB::transaction(function () use ($mentorIds) {
            $pesertaData = [
                ['email' => 'peserta@vocasia.com', 'nim' => '1234567890', 'nama_lengkap' => 'Budi Santoso', 'universitas' => 'Universitas Indonesia', 'prodi' => 'Teknik Informatika', 'no_telpon' => '081234567890'],
                ['email' => 'peserta2@vocasia.com', 'nim' => '1234567891', 'nama_lengkap' => 'Anwar garden', 'universitas' => 'Universitas Gadjah Mada', 'prodi' => 'Sistem Informasi', 'no_telpon' => '081234567891'],
                ['email' => 'peserta3@vocasia.com', 'nim' => '1234567892', 'nama_lengkap' => 'Haikal Dzaky', 'universitas' => 'Institut Teknologi Bandung', 'prodi' => 'Teknik Komputer', 'no_telpon' => '081234567892'],
                ['email' => 'peserta4@vocasia.com', 'nim' => '1234567893', 'nama_lengkap' => 'Ahmad Ghofur', 'universitas' => 'Universitas Brawijaya', 'prodi' => 'Ilmu Komputer', 'no_telpon' => '081234567893'],
                ['email' => 'peserta5@vocasia.com', 'nim' => '1234567894', 'nama_lengkap' => 'Fakrazi', 'universitas' => 'Universitas Diponegoro', 'prodi' => 'Teknik Elektro', 'no_telpon' => '081234567894'],
                ['email' => 'peserta6@vocasia.com', 'nim' => '1234567895', 'nama_lengkap' => 'Dimas Andrean', 'universitas' => 'Universitas Airlangga', 'prodi' => 'Teknik Informatika', 'no_telpon' => '081234567895'],
                ['email' => 'peserta7@vocasia.com', 'nim' => '1234567896', 'nama_lengkap' => 'Bintang Mahardika', 'universitas' => 'Universitas Padjadjaran', 'prodi' => 'Sistem Informasi', 'no_telpon' => '081234567896'],
                ['email' => 'peserta8@vocasia.com', 'nim' => '1234567897', 'nama_lengkap' => 'Rizky Febrian', 'universitas' => 'Universitas Sebelas Maret', 'prodi' => 'Ilmu Komputer', 'no_telpon' => '081234567897'],
                ['email' => 'peserta9@vocasia.com', 'nim' => '1234567898', 'nama_lengkap' => 'Tirta Mandira', 'universitas' => 'Universitas Udayana', 'prodi' => 'Teknik Komputer', 'no_telpon' => '081234567898'],
                ['email' => 'peserta10@vocasia.com', 'nim' => '1234567899', 'nama_lengkap' => 'Sarah Wijayanto', 'universitas' => 'Universitas Hasanuddin', 'prodi' => 'Sistem Informasi', 'no_telpon' => '081234567899'],
                ['email' => 'peserta11@vocasia.com', 'nim' => '1234567800', 'nama_lengkap' => 'Nadia Saphira', 'universitas' => 'Universitas Sumatera Utara', 'prodi' => 'Ilmu Komputer', 'no_telpon' => '081234567800'],
                ['email' => 'peserta12@vocasia.com', 'nim' => '1234567801', 'nama_lengkap' => 'Joko Widodo', 'universitas' => 'Universitas Indonesia', 'prodi' => 'Teknik Mesin', 'no_telpon' => '081234567801'],
                ['email' => 'peserta13@vocasia.com', 'nim' => '1234567802', 'nama_lengkap' => 'Prabowo Subianto', 'universitas' => 'Institut Pertanian Bogor', 'prodi' => 'Agribisnis', 'no_telpon' => '081234567802'],
                ['email' => 'peserta14@vocasia.com', 'nim' => '1234567803', 'nama_lengkap' => 'Anies Baswedan', 'universitas' => 'Universitas Gadjah Mada', 'prodi' => 'Manajemen', 'no_telpon' => '081234567803'],
                ['email' => 'peserta15@vocasia.com', 'nim' => '1234567804', 'nama_lengkap' => 'Ganjar Pranowo', 'universitas' => 'Universitas Gadjah Mada', 'prodi' => 'Ilmu Hukum', 'no_telpon' => '081234567804'],
            ];

            foreach ($pesertaData as $index => $data) {
                $userPeserta = User::create([
                    'email' => $data['email'],
                    'password' => Hash::make('123123123'),
                    'role' => 'peserta',
                ]);

                // Bagi rata peserta ke mentor yang tersedia menggunakan modulus
                $assignedMentorId = count($mentorIds) > 0 ? $mentorIds[$index % count($mentorIds)] : null;

                pesertaMagang::create([
                    'user_id' => $userPeserta->id,
                    'nim' => $data['nim'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'universitas' => $data['universitas'],
                    'prodi' => $data['prodi'],
                    'mentor_magang_id' => $assignedMentorId,
                    'periode_masuk' => '2026-01-01',
                    'periode_keluar' => '2026-07-01',
                    'no_telpon' => $data['no_telpon'],
                    'status' => 'Aktif',
                    'alamat' => 'Jakarta',
                ]);
            }
        });
    }
}

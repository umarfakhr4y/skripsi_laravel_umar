<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\mentorMagang;
use App\Models\pesertaMagang;
use App\Models\Absensi;
use App\Models\Notifikasi;
use Carbon\Carbon;

class CheckAbsensiHarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-absensi-harian';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek peserta magang yang tidak absen hari ini dan kirim notifikasi ke mentor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->toDateString();
        
        // Ambil semua mentor
        $mentors = mentorMagang::with('peserta')->get();

        foreach ($mentors as $mentor) {
            $pesertaBelumAbsen = 0;
            $namaPesertaList = [];

            foreach ($mentor->peserta as $peserta) {
                // Cek apakah peserta ini ada di tabel absensi hari ini (status apa saja: hadir, izin, sakit)
                $absen = Absensi::where('peserta_magang_id', $peserta->id)
                    ->where('tanggal', $today)
                    ->first();
                
                if (!$absen) {
                    $pesertaBelumAbsen++;
                    $namaPesertaList[] = $peserta->nama_lengkap;
                }
            }

            if ($pesertaBelumAbsen > 0) {
                // Kirim notifikasi ke mentor
                $pesan = "Terdapat $pesertaBelumAbsen peserta yang belum melakukan presensi hari ini: " . implode(', ', $namaPesertaList);
                
                Notifikasi::create([
                    'user_id' => $mentor->user_id,
                    'tipe' => 'absensi_harian',
                    'judul' => 'Rekap Absensi Hari Ini',
                    'pesan' => $pesan,
                    'is_read' => false,
                    'data_terkait' => [
                        'tanggal' => $today
                    ]
                ]);

                $this->info("Notifikasi dikirim ke Mentor ID: {$mentor->id} untuk $pesertaBelumAbsen peserta.");
            }
        }

        $this->info('Pengecekan absensi harian selesai.');
    }
}

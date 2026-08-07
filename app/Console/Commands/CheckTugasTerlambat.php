<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Penugasan;
use App\Models\Notifikasi;
use Carbon\Carbon;

class CheckTugasTerlambat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-tugas-terlambat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek tugas peserta magang yang terlambat dan mengirim notifikasi ke mentor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // 1. Ambil semua tugas yang belum selesai dan sudah melewati deadline
        $tugasTerlambat = Penugasan::with(['peserta', 'mentor'])
            ->where('status_tugas', '!=', 'Selesai')
            ->whereDate('deadline', '<', $today)
            ->get();

        // 2. Kelompokkan berdasarkan mentor_magang_id
        $tugasByMentor = $tugasTerlambat->groupBy('mentor_magang_id');

        foreach ($tugasByMentor as $mentorMagangId => $tugasList) {
            $jumlahTerlambat = $tugasList->count();
            
            // 3. Ambil maksimal 3 nama unik dari peserta yang terlambat
            $namaPesertaList = $tugasList->map(function ($tugas) {
                return $tugas->peserta ? $tugas->peserta->nama_lengkap : 'Peserta';
            })->unique()->take(3)->implode(', ');

            if ($tugasList->unique('peserta_magang_id')->count() > 3) {
                $namaPesertaList .= ' dan lainnya';
            }

            // 4. Ambil user_id dari mentor
            $mentorUserId = $tugasList->first()->mentor->user_id;

            // 5. Buat record notifikasi untuk mentor ini
            Notifikasi::create([
                'user_id' => $mentorUserId,
                'tipe' => 'tugas_terlambat',
                'judul' => 'Pengingat Tugas Terlambat',
                'pesan' => "Terdapat $jumlahTerlambat tugas yang belum diselesaikan dan telah melewati tenggat waktu. Terkait peserta: $namaPesertaList.",
                'is_read' => false,
                'data_terkait' => json_encode([
                    'tanggal' => $today->format('Y-m-d')
                ])
            ]);
        }

        $this->info('Pengecekan tugas terlambat berhasil dijalankan!');
    }
}

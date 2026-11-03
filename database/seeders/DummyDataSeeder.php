<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\mentorMagang;
use App\Models\pesertaMagang;
use App\Models\Absensi;
use App\Models\LaporanHarian;
use App\Models\EvaluasiBulanan;
use App\Models\Bimbingan;
use App\Models\Perizinan;
use App\Models\Penugasan;
use App\Models\Persuratan;
use App\Models\PengaturanAbsensi;
use App\Models\Notifikasi;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Nonaktifkan foreign key checks untuk pembersihan data lama dengan aman
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Clear seluruh isi data fitur
        Absensi::truncate();
        LaporanHarian::truncate();
        EvaluasiBulanan::truncate();
        Bimbingan::truncate();
        Perizinan::truncate();
        Penugasan::truncate();
        Persuratan::truncate();
        PengaturanAbsensi::truncate();
        Notifikasi::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Ambil data mentor dan peserta yang tersedia
        $mentors = mentorMagang::orderBy('id')->get();
        $pesertas = pesertaMagang::orderBy('id')->get();

        if ($mentors->isEmpty() || $pesertas->isEmpty()) {
            $this->command->warn('Mentor atau Peserta belum tersedia. Silakan jalankan UserSeeder terlebih dahulu.');
            return;
        }

        $mentor1 = $mentors[0];
        $mentor2 = $mentors[1] ?? $mentor1;
        $mentor3 = $mentors[2] ?? $mentor1;

        // Ambil peserta utama: Budi Santoso dan 2 peserta lainnya
        $budi = $pesertas->firstWhere('nama_lengkap', 'Budi Santoso') ?? $pesertas[0];
        $anwar = $pesertas->firstWhere('nama_lengkap', 'Anwar garden') ?? ($pesertas[1] ?? $budi);
        $haikal = $pesertas->firstWhere('nama_lengkap', 'Haikal Dzaky') ?? ($pesertas[2] ?? $budi);

        // Format bulan evaluasi (uppercase)
        $bulanList = [
            'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
        ];
        $bulanSekarang = $bulanList[Carbon::now()->month - 1] . ' ' . Carbon::now()->year;
        $bulanLalu = $bulanList[Carbon::now()->subMonth()->month - 1] . ' ' . Carbon::now()->subMonth()->year;

        // =========================================================================
        // PENGATURAN ABSENSI (UNTUK SETIAP MENTOR)
        // =========================================================================
        foreach ([$mentor1, $mentor2, $mentor3] as $m) {
            PengaturanAbsensi::create([
                'mentor_magang_id' => $m->id,
                'batas_waktu' => '08:00:00',
                'tanggal_berlaku' => Carbon::now()->subDays(30)->toDateString(),
            ]);
            PengaturanAbsensi::create([
                'mentor_magang_id' => $m->id,
                'batas_waktu' => '08:15:00',
                'tanggal_berlaku' => Carbon::tomorrow()->toDateString(),
            ]);
        }

        // =========================================================================
        // DATA UNTUK PESERTA 1: BUDI SANTOSO (Divisi IT Development - Mentor: Andi Wijaya)
        // =========================================================================
        $mBudiId = $budi->mentor_magang_id ?? $mentor1->id;

        // --- PENUGASAN BUDI SANTOSO (5 Data dengan berbagai variasi status) ---
        // 1. Selesai (Sudah dikumpulkan & Sudah di-ACC mentor)
        Penugasan::create([
            'mentor_magang_id' => $mBudiId,
            'peserta_magang_id' => $budi->id,
            'judul_tugas' => 'Slicing UI Dashboard Mobile Peserta & Profil',
            'deskripsi' => 'Implementasikan antarmuka dashboard utama peserta dan profil menggunakan Flutter dengan state management BLoC/Provider.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(4)->toDateString(),
            'status_tugas' => 'Selesai',
            'catatan_peserta' => 'Semua halaman telah selesai disesuaikan dengan prototipe Figma.',
            'file_pengumpulan' => ['submissions/slicing_dashboard_budi.pdf'],
            'catatan_mentor' => 'Hasil pekerjaan sangat rapi, widget tree efisien dan responsive.',
        ]);

        // 2. Ditinjau (Sudah dikumpulkan peserta, Belum di-ACC mentor)
        Penugasan::create([
            'mentor_magang_id' => $mBudiId,
            'peserta_magang_id' => $budi->id,
            'judul_tugas' => 'Implementasi Push Notification Firebase (FCM)',
            'deskripsi' => 'Konfigurasi Firebase Cloud Messaging pada aplikasi Flutter dan Laravel backend untuk notifikasi otomatis.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(2)->toDateString(),
            'status_tugas' => 'Ditinjau',
            'catatan_peserta' => 'Notifikasi sudah berjalan normal baik di background maupun foreground pada emulator dan HP fisik.',
            'file_pengumpulan' => ['submissions/laporan_pengujian_fcm.pdf'],
            'catatan_mentor' => null, // Belum direview mentor
        ]);

        // 3. Revisi (Sudah dikumpulkan tapi diminta perbaikan oleh mentor)
        Penugasan::create([
            'mentor_magang_id' => $mBudiId,
            'peserta_magang_id' => $budi->id,
            'judul_tugas' => 'Integrasi GPS Geolocation Halaman Presensi',
            'deskripsi' => 'Hubungkan fitur clock-in dengan GPS device untuk validasi koordinat kantor secara presisi.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(1)->toDateString(),
            'status_tugas' => 'Revisi',
            'catatan_peserta' => 'Sudah dipasang geolocator package, namun respon koordinat masih sedikit lambat saat indoor.',
            'file_pengumpulan' => ['submissions/patch_geolocator_v1.zip'],
            'catatan_mentor' => 'Ubah setting LocationAccuracy menjadi medium/balanced agar tidak lambat saat locking sinyal.',
        ]);

        // 4. Aktif (Tugas berjalan normal, belum dikumpulkan peserta)
        Penugasan::create([
            'mentor_magang_id' => $mBudiId,
            'peserta_magang_id' => $budi->id,
            'judul_tugas' => 'Implementasi Modul Penugasan & Upload Dokumen',
            'deskripsi' => 'Buat halaman daftar tugas dan form pengumpulan berkas tugas peserta magang beserta preview file.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(4)->toDateString(),
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // 5. Aktif - Telat / Terlewat Deadline (Deadline telah lewat tetapi belum dikumpulkan)
        Penugasan::create([
            'mentor_magang_id' => $mBudiId,
            'peserta_magang_id' => $budi->id,
            'judul_tugas' => 'Penyusunan Dokumentasi Swagger API & Koleksi Postman',
            'deskripsi' => 'Susun dokumentasi endpoint lengkap REST API autentikasi dan absensi untuk memudahkan integrasi tim.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(2)->toDateString(), // Telat
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // --- PERIZINAN BUDI SANTOSO (5 Data dengan berbagai variasi status) ---
        // 1. Selesai (Sudah di-ACC)
        Perizinan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_izin' => 'Sakit',
            'keterangan' => 'Mengalami demam dan radang flu berat, beristirahat 1 hari sesuai surat keterangan dokter.',
            'tanggal_izin' => Carbon::now()->subDays(6)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Izin disetujui. Jaga kesehatan dan lekas pulih.',
        ]);

        // 2. Selesai (Sudah di-ACC)
        Perizinan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_izin' => 'Izin Kuliah',
            'keterangan' => 'Menghadiri ujian tengah semester mata kuliah Rekayasa Perangkat Lunak secara luring.',
            'tanggal_izin' => Carbon::now()->subDays(3)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Izin disetujui. Selamat menempuh ujian.',
        ]);

        // 3. Pending (Belum di-ACC / Menunggu Persetujuan Mentor)
        Perizinan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_izin' => 'Keperluan Keluarga',
            'keterangan' => 'Menghadiri acara pernikahan saudara kandung di luar kota pada akhir pekan ini.',
            'tanggal_izin' => Carbon::now()->addDays(3)->toDateString(),
            'file_pendukung' => null,
            'status' => 'pending',
            'catatan_mentor' => null,
        ]);

        // 4. Diproses (Sedang dalam proses verifikasi)
        Perizinan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_izin' => 'Terlambat',
            'keterangan' => 'Terjebak penumpukan antrean KRL karena ada gangguan jalur persinyalan stasiun Manggarai.',
            'tanggal_izin' => Carbon::yesterday()->toDateString(),
            'file_pendukung' => null,
            'status' => 'diproses',
            'catatan_mentor' => 'Sedang diverifikasi oleh koordinator operasional.',
        ]);

        // 5. Ditolak (Pengajuan ditolak oleh mentor)
        Perizinan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_izin' => 'Izin Khusus',
            'keterangan' => 'Mengajukan izin libur mandiri untuk kegiatan non-akademik di luar kampus.',
            'tanggal_izin' => Carbon::now()->subDays(10)->toDateString(),
            'file_pendukung' => null,
            'status' => 'ditolak',
            'catatan_mentor' => 'Mohon maaf izin tidak dapat disetujui karena bertepatan dengan jadwal rilis sprint sistem.',
        ]);

        // --- BIMBINGAN BUDI SANTOSO (5 Data dengan berbagai variasi status) ---
        // 1. Selesai (completed)
        Bimbingan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'tanggal' => Carbon::now()->subDays(8)->toDateString(),
            'topik' => 'Review Arsitektur RESTful API, Autentikasi Sanctum, dan Struktur Response JSON',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/abc-budi-001',
        ]);

        // 2. Selesai (completed)
        Bimbingan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'topik' => 'Evaluasi Kinerja Query Database Eloquent & Relasi Model',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/abc-budi-002',
        ]);

        // 3. Disetujui (accepted / sudah di-ACC jadwalnya)
        Bimbingan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'tanggal' => Carbon::now()->addDays(1)->toDateString(),
            'topik' => 'Konsultasi Penanganan Token Expiration dan Background FCM Handler',
            'status' => 'accepted',
            'link_meet' => 'https://meet.google.com/abc-budi-003',
        ]);

        // 4. Menunggu (requested / belum di-ACC mentor)
        Bimbingan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'tanggal' => Carbon::now()->addDays(4)->toDateString(),
            'topik' => 'Bimbingan Rancangan Bab 4 Skripsi dan Hasil Pengujian UAT',
            'status' => 'requested',
            'link_meet' => null,
        ]);

        // 5. Ditolak / Dijadwalkan Ulang (rejected)
        Bimbingan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'tanggal' => Carbon::now()->subDays(5)->toDateString(),
            'topik' => 'Konsultasi Perubahan Schema Database Darurat',
            'status' => 'rejected',
            'link_meet' => null,
        ]);

        // --- ABSENSI BUDI SANTOSO (5 Data) ---
        Absensi::create([
            'peserta_magang_id' => $budi->id,
            'tanggal' => Carbon::now()->subDays(4)->toDateString(),
            'waktu_masuk' => '07:48:12',
            'waktu_keluar' => '17:02:10',
            'status' => 'hadir',
            'keterangan' => 'Hadir tepat waktu di kantor pusat WFO.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $budi->id,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'waktu_masuk' => null,
            'waktu_keluar' => null,
            'status' => 'izin',
            'keterangan' => 'Izin UTS mata kuliah RPL di kampus.',
            'latitude' => null,
            'longitude' => null,
        ]);
        Absensi::create([
            'peserta_magang_id' => $budi->id,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'waktu_masuk' => '08:22:40',
            'waktu_keluar' => '17:35:10',
            'status' => 'telat',
            'keterangan' => 'Terlambat karena kendala persinyalan KRL.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $budi->id,
            'tanggal' => Carbon::yesterday()->toDateString(),
            'waktu_masuk' => '07:55:00',
            'waktu_keluar' => '17:08:20',
            'status' => 'hadir',
            'keterangan' => 'Hadir WFO dan mengikuti standup meeting divisi.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $budi->id,
            'tanggal' => Carbon::today()->toDateString(),
            'waktu_masuk' => '07:51:30',
            'waktu_keluar' => null,
            'status' => 'hadir',
            'keterangan' => 'Clock-in presensi pagi berhasil.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);

        // --- LAPORAN HARIAN BUDI SANTOSO (5 Data) ---
        LaporanHarian::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'laporan' => 'Mengimplementasikan modul autentikasi REST API menggunakan Laravel Sanctum, menyelesaikan validasi register dan login peserta magang.',
            'komentar_mentor' => 'Bagus sekali, implementasi Sanctum sudah rapi dan aman.',
            'created_at' => Carbon::now()->subDays(4)->setTime(16, 45, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'laporan' => 'Melakukan slicing antarmuka (UI) Dashboard Peserta dan Profil pada aplikasi Flutter serta menghubungkannya dengan API endpoint.',
            'komentar_mentor' => 'Desain tampilan antarmuka sudah clean dan responsif di berbagai resolusi layar.',
            'created_at' => Carbon::now()->subDays(3)->setTime(17, 0, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'laporan' => 'Mengimplementasikan push notifikasi menggunakan Firebase Cloud Messaging (FCM) pada device Android dan testing notifikasi foreground.',
            'komentar_mentor' => 'Mantap, integrasi FCM berjalan baik. Lanjutkan pengujian pada kondisi terminated.',
            'created_at' => Carbon::now()->subDays(2)->setTime(17, 10, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'laporan' => 'Menyusun skema database dan membuat migrasi serta seeder untuk modul evaluasi bulanan dan perizinan.',
            'komentar_mentor' => null, // Belum dikomentari mentor
            'created_at' => Carbon::yesterday()->setTime(16, 30, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'laporan' => 'Melakukan pengujian fungsionalitas fitur upload file laporan dan bukti perizinan dengan batasan ukuran file 5MB.',
            'komentar_mentor' => null, // Belum dikomentari mentor
            'created_at' => Carbon::today()->setTime(14, 0, 0),
        ]);

        // --- PERSURATAN BUDI SANTOSO (5 Data) ---
        Persuratan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_surat' => 'Surat Keterangan Magang Aktif',
            'keperluan' => 'Kelengkapan berkas administrasi beasiswa akademik berprestasi di perguruan tinggi.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-keterangan-aktif-budi.pdf',
            'catatan_mentor' => 'Surat keterangan resmi telah diterbitkan dan ditandatangani. Silakan diunduh.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_surat' => 'Surat Izin Penelitian Skripsi',
            'keperluan' => 'Pengambilan data observasi dan wawancara studi kasus untuk penyusunan Bab 4 Skripsi.',
            'file_pendukung' => null,
            'status' => 'diproses',
            'link_dokumen' => null,
            'catatan_mentor' => 'Surat sedang diverifikasi oleh tata usaha dan menunggu persetujuan pimpinan divisi.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_surat' => 'Surat Pengantar Seminar Hasil Magang',
            'keperluan' => 'Lampiran berkas pendaftaran seminar laporan hasil magang di program studi kampus.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-pengantar-seminar-budi.pdf',
            'catatan_mentor' => 'Surat pengantar telah disetujui. Selamat mempersiapkan presentasi hasil magang.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_surat' => 'Surat Rekomendasi Magang Lanjutan',
            'keperluan' => 'Persyaratan pendaftaran program sertifikasi keahlian industri bidang IT Development.',
            'file_pendukung' => null,
            'status' => 'pending', // Belum di-ACC
            'link_dokumen' => null,
            'catatan_mentor' => null,
        ]);
        Persuratan::create([
            'peserta_magang_id' => $budi->id,
            'jenis_surat' => 'Surat Keterangan Selesai Magang',
            'keperluan' => 'Bukti telah menuntaskan seluruh periode magang untuk konversi 20 SKS MBKM.',
            'file_pendukung' => null,
            'status' => 'ditolak',
            'link_dokumen' => null,
            'catatan_mentor' => 'Periode magang Anda masih aktif hingga akhir semester. Pengajuan dapat dilakukan 2 minggu sebelum tanggal penutupan program.',
        ]);

        // --- EVALUASI BULANAN BUDI SANTOSO (5 Bulan) ---
        $bulan5 = $bulanList[(Carbon::now()->month - 5 + 12) % 12] . ' ' . (Carbon::now()->month <= 5 ? Carbon::now()->year - 1 : Carbon::now()->year);
        $bulan4 = $bulanList[(Carbon::now()->month - 4 + 12) % 12] . ' ' . (Carbon::now()->month <= 4 ? Carbon::now()->year - 1 : Carbon::now()->year);
        $bulan3 = $bulanList[(Carbon::now()->month - 3 + 12) % 12] . ' ' . (Carbon::now()->month <= 3 ? Carbon::now()->year - 1 : Carbon::now()->year);

        EvaluasiBulanan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'produktivitas' => 5,
            'komunikasi' => 5,
            'keahlian_teknis' => 5,
            'feedback' => 'Kinerja sangat memuaskan! Proaktif dalam sprint development, menguasai arsitektur backend Laravel, dan aktif berkontribusi saat evaluasi tim.',
            'bulan_tahun' => $bulanSekarang,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'produktivitas' => 5,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Disiplin waktu sangat tinggi dan selalu menyelesaikan tugas sebelum batas waktu deadline. Pemahaman query database sangat baik.',
            'bulan_tahun' => $bulanLalu,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Mampu menyelesaikan modul penugasan dan relasi Eloquent database dengan sangat baik dan minim bug.',
            'bulan_tahun' => $bulan3,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 3,
            'feedback' => 'Menunjukkan antusiasme tinggi saat onboarding sprint dan cepat beradaptasi dengan alur kerja git repository.',
            'bulan_tahun' => $bulan4,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $budi->id,
            'mentor_magang_id' => $mBudiId,
            'produktivitas' => 3,
            'komunikasi' => 4,
            'keahlian_teknis' => 3,
            'feedback' => 'Awal magang berjalan lancar. Terus tingkatkan kepatuhan terhadap standarisasi clean code perusahaan.',
            'bulan_tahun' => $bulan5,
        ]);


        // =========================================================================
        // DATA UNTUK PESERTA 2: ANWAR GARDEN (Divisi Digital Marketing - Mentor: Siti Nurhaliza)
        // =========================================================================
        $mAnwarId = $anwar->mentor_magang_id ?? $mentor2->id;

        // --- PENUGASAN ANWAR GARDEN (5 Data) ---
        // 1. Selesai (Sudah di-ACC)
        Penugasan::create([
            'mentor_magang_id' => $mAnwarId,
            'peserta_magang_id' => $anwar->id,
            'judul_tugas' => 'Riset Kata Kunci & Analisis Kompetitor SEO',
            'deskripsi' => 'Lakukan riset 50 target keywords bervolume tinggi untuk artikel blog kursus teknologi informasi.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(5)->toDateString(),
            'status_tugas' => 'Selesai',
            'catatan_peserta' => 'Daftar keyword dan mapping search intent telah disusun dalam spreadsheet.',
            'file_pengumpulan' => ['submissions/keyword_research_anwar.xlsx'],
            'catatan_mentor' => 'Analisis intent keyword sangat detail. Lanjutkan ke tahap penulisan outline.',
        ]);

        // 2. Ditinjau (Sudah dikumpulkan, belum di-ACC)
        Penugasan::create([
            'mentor_magang_id' => $mAnwarId,
            'peserta_magang_id' => $anwar->id,
            'judul_tugas' => 'Copywriting Iklan Meta Ads & Google Ads Promo Akhir Bulan',
            'deskripsi' => 'Buat 5 variasi headline dan primary text dengan formula AIDA untuk promosi diskon program magang.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(2)->toDateString(),
            'status_tugas' => 'Ditinjau',
            'catatan_peserta' => 'Draft copy telah selesai, sudah mencakup hook emosional dan call to action jelas.',
            'file_pengumpulan' => ['submissions/copywriting_ads_draft.pdf'],
            'catatan_mentor' => null,
        ]);

        // 3. Revisi (Diminta perbaikan oleh mentor)
        Penugasan::create([
            'mentor_magang_id' => $mAnwarId,
            'peserta_magang_id' => $anwar->id,
            'judul_tugas' => 'Optimasi Conversion Rate Landing Page Promosi',
            'deskripsi' => 'Analisis heatmaps dan perbaiki letak tombol CTA pada landing page pendaftaran peserta.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(1)->toDateString(),
            'status_tugas' => 'Revisi',
            'catatan_peserta' => 'Sudah dipindahkan tombol CTA di atas fold, namun warna button belum disepakati.',
            'file_pengumpulan' => ['submissions/cro_landing_page_review.pdf'],
            'catatan_mentor' => 'Gunakan warna kontras yang sesuai brand guide (oranye atau tosca) agar CTR meningkat.',
        ]);

        // 4. Aktif (Sedang berjalan normal)
        Penugasan::create([
            'mentor_magang_id' => $mAnwarId,
            'peserta_magang_id' => $anwar->id,
            'judul_tugas' => 'Penyusunan Kalender Konten Email Newsletter Mingguan',
            'deskripsi' => 'Rancang jadwal dan tema pengiriman email blast edukasi mingguan kepada 10.000 subscriber aktif.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(5)->toDateString(),
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // 5. Aktif - Telat / Terlewat Deadline
        Penugasan::create([
            'mentor_magang_id' => $mAnwarId,
            'peserta_magang_id' => $anwar->id,
            'judul_tugas' => 'Laporan Rekapitulasi Metrik Google Analytics 4 (GA4)',
            'deskripsi' => 'Tarik data bounce rate, average session duration, dan conversion goal periode bulan lalu.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(2)->toDateString(), // Telat
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // --- PERIZINAN ANWAR GARDEN (5 Data) ---
        Perizinan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_izin' => 'Izin Kuliah',
            'keterangan' => 'Menghadiri praktikum jaringan komputer dan evaluasi semester di kampus UGM.',
            'tanggal_izin' => Carbon::now()->subDays(5)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Diizinkan, pastikan tugas harian dikerjakan setelah praktikum.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_izin' => 'Sakit',
            'keterangan' => 'Mengalami sakit lambung dan migrain, istirahat di rumah.',
            'tanggal_izin' => Carbon::now()->subDays(2)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Izin disetujui, semoga lekas pulih.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_izin' => 'Keperluan Mendesak',
            'keterangan' => 'Mengurus administrasi perpanjangan berkas beasiswa di dekanat fakultas.',
            'tanggal_izin' => Carbon::now()->addDays(2)->toDateString(),
            'file_pendukung' => null,
            'status' => 'pending', // Belum di-ACC
            'catatan_mentor' => null,
        ]);
        Perizinan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_izin' => 'Terlambat',
            'keterangan' => 'Terjebak macet parah karena ada penutupan jalur bypass.',
            'tanggal_izin' => Carbon::now()->subDays(4)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Diterima, harap lebih berhati-hati.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_izin' => 'Izin Khusus',
            'keterangan' => 'Mengikuti study tour organisasi luar kampus selama 3 hari kerja.',
            'tanggal_izin' => Carbon::now()->subDays(12)->toDateString(),
            'file_pendukung' => null,
            'status' => 'ditolak',
            'catatan_mentor' => 'Izin ditolak karena durasi terlalu lama dan tidak terkait kurikulum wajib magang.',
        ]);

        // --- BIMBINGAN ANWAR GARDEN (5 Data) ---
        Bimbingan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'tanggal' => Carbon::now()->subDays(7)->toDateString(),
            'topik' => 'Evaluasi Matriks ROAS Kampanye Iklan Berbayar Meta Ads',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/dm-anwar-001',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'topik' => 'Review Strategi Content Pillar dan A/B Testing Banner',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/dm-anwar-002',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'tanggal' => Carbon::now()->addDays(2)->toDateString(),
            'topik' => 'Konsultasi Perencanaan Campaign Promo Hari Pahlawan & Black Friday',
            'status' => 'accepted', // Sudah di-ACC
            'link_meet' => 'https://meet.google.com/dm-anwar-003',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'tanggal' => Carbon::now()->addDays(5)->toDateString(),
            'topik' => 'Diskusi Analisis Funnel Konversi Lead Menjadi Pembeli',
            'status' => 'requested', // Belum di-ACC
            'link_meet' => null,
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'tanggal' => Carbon::now()->subDays(4)->toDateString(),
            'topik' => 'Konsultasi Anggaran Iklan Tambahan Diluar Budget Perusahaan',
            'status' => 'rejected', // Ditolak
            'link_meet' => null,
        ]);

        // --- ABSENSI ANWAR GARDEN (5 Data) ---
        Absensi::create([
            'peserta_magang_id' => $anwar->id,
            'tanggal' => Carbon::now()->subDays(4)->toDateString(),
            'waktu_masuk' => '07:50:00',
            'waktu_keluar' => '17:05:00',
            'status' => 'hadir',
            'keterangan' => 'Hadir WFO kantor divisi Digital Marketing.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $anwar->id,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'waktu_masuk' => '08:25:30',
            'waktu_keluar' => '17:30:00',
            'status' => 'telat',
            'keterangan' => 'Terlambat karena kendala kemacetan kendaraan.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $anwar->id,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'waktu_masuk' => null,
            'waktu_keluar' => null,
            'status' => 'sakit',
            'keterangan' => 'Sakit migrain dan istirahat.',
            'latitude' => null,
            'longitude' => null,
        ]);
        Absensi::create([
            'peserta_magang_id' => $anwar->id,
            'tanggal' => Carbon::yesterday()->toDateString(),
            'waktu_masuk' => '07:45:10',
            'waktu_keluar' => '17:00:20',
            'status' => 'hadir',
            'keterangan' => 'Hadir tepat waktu dan mengikuti morning briefing.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $anwar->id,
            'tanggal' => Carbon::today()->toDateString(),
            'waktu_masuk' => '07:58:00',
            'waktu_keluar' => null,
            'status' => 'hadir',
            'keterangan' => 'Presensi masuk pagi berhasil tercatat.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);

        // --- LAPORAN HARIAN ANWAR GARDEN (5 Data) ---
        LaporanHarian::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'laporan' => 'Melakukan analisis performa kampanye Facebook Ads minggu lalu dan mencatat ROAS pada dashboard pelaporan.',
            'komentar_mentor' => 'Bagus, pastikan campaign yang underperforming segera dipause.',
            'created_at' => Carbon::now()->subDays(4)->setTime(16, 50, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'laporan' => 'Menulis 3 artikel SEO bertema tips belajar programming untuk menaikkan organic traffic blog.',
            'komentar_mentor' => 'Struktur heading dan penempatan internal link sudah bagus.',
            'created_at' => Carbon::now()->subDays(3)->setTime(17, 0, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'laporan' => 'Melakukan riset kompetitor terkait strategi promosi diskon kursus online pada platform media sosial.',
            'komentar_mentor' => null,
            'created_at' => Carbon::now()->subDays(2)->setTime(16, 40, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'laporan' => 'Mengatur alur automation email welcome series untuk pengguna baru yang mendaftar pada aplikasi.',
            'komentar_mentor' => 'Sudah ditest dan berjalan dengan baik. Good job.',
            'created_at' => Carbon::yesterday()->setTime(17, 10, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'laporan' => 'Menyiapkan materi presentasi rekapitulasi data leads dan prospek calon pendaftar program magang.',
            'komentar_mentor' => null,
            'created_at' => Carbon::today()->setTime(14, 30, 0),
        ]);

        // --- PERSURATAN ANWAR GARDEN (5 Data) ---
        Persuratan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_surat' => 'Surat Keterangan Magang Aktif',
            'keperluan' => 'Permohonan dispensasi kuliah kerja nyata (KKN) di fakultas.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-aktif-anwar.pdf',
            'catatan_mentor' => 'Surat keterangan telah diterbitkan.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_surat' => 'Surat Izin Penelitian Skripsi',
            'keperluan' => 'Pengambilan data riset pemasaran digital untuk bahan skripsi.',
            'file_pendukung' => null,
            'status' => 'diproses',
            'link_dokumen' => null,
            'catatan_mentor' => 'Menunggu verifikasi bagian legal perusahaan.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_surat' => 'Surat Rekomendasi Magang',
            'keperluan' => 'Pendaftaran program sertifikasi Google Digital Garage.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-rekomendasi-anwar.pdf',
            'catatan_mentor' => 'Rekomendasi resmi telah ditandatangani.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_surat' => 'Surat Pengantar Sidang Magang',
            'keperluan' => 'Pendaftaran ujian laporan praktik kerja magang.',
            'file_pendukung' => null,
            'status' => 'pending', // Belum di-ACC
            'link_dokumen' => null,
            'catatan_mentor' => null,
        ]);
        Persuratan::create([
            'peserta_magang_id' => $anwar->id,
            'jenis_surat' => 'Surat Keterangan Selesai Magang',
            'keperluan' => 'Konversi nilai sks semester berjalan.',
            'file_pendukung' => null,
            'status' => 'ditolak',
            'link_dokumen' => null,
            'catatan_mentor' => 'Masa magang masih berjalan, belum memenuhi syarat administrasi.',
        ]);

        // --- EVALUASI BULANAN ANWAR GARDEN (5 Bulan) ---
        EvaluasiBulanan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 5,
            'feedback' => 'Kemampuan teknis analisis SEO dan copywriting sangat baik. Tingkatkan lagi inisiatif dalam diskusi kelompok kerja.',
            'bulan_tahun' => $bulanSekarang,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Menunjukkan progres kerja yang konsisten. Pemahaman tools digital marketing terus meningkat setiap minggunya.',
            'bulan_tahun' => $bulanLalu,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'produktivitas' => 4,
            'komunikasi' => 3,
            'keahlian_teknis' => 4,
            'feedback' => 'Mampu mengelola kampanye iklan Meta Ads dengan performa return on ad spend (ROAS) yang stabil.',
            'bulan_tahun' => $bulan3,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'produktivitas' => 3,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Riset kata kunci dan analisis kompetitor sudah tepat sasaran. Terus asah kemampuan copywriting.',
            'bulan_tahun' => $bulan4,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $anwar->id,
            'mentor_magang_id' => $mAnwarId,
            'produktivitas' => 3,
            'komunikasi' => 3,
            'keahlian_teknis' => 3,
            'feedback' => 'Orientasi awal divisi digital marketing berjalan baik dan mulai memahami tools analytics dasar.',
            'bulan_tahun' => $bulan5,
        ]);


        // =========================================================================
        // DATA UNTUK PESERTA 3: HAIKAL DZAKY (Divisi Content Creator - Mentor: Bambang Pamungkas)
        // =========================================================================
        $mHaikalId = $haikal->mentor_magang_id ?? $mentor3->id;

        // --- PENUGASAN HAIKAL DZAKY (5 Data) ---
        // 1. Selesai (Sudah di-ACC)
        Penugasan::create([
            'mentor_magang_id' => $mHaikalId,
            'peserta_magang_id' => $haikal->id,
            'judul_tugas' => 'Produksi Video Reels Edukasi Tips Karir Tech 60 Detik',
            'deskripsi' => 'Rancang naskah, take video, dan editing video vertikal dengan animasi subtitle dinamis untuk Instagram dan TikTok.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(6)->toDateString(),
            'status_tugas' => 'Selesai',
            'catatan_peserta' => 'Video telah selesai dirender resolusi 1080x1920 60fps dengan sound trending.',
            'file_pengumpulan' => ['submissions/video_reels_haikal.mp4'],
            'catatan_mentor' => 'Pacing video pas, transisi visual menarik, dan hook pembuka berhasil menarik perhatian penonton.',
        ]);

        // 2. Ditinjau (Sudah dikumpulkan, belum di-ACC)
        Penugasan::create([
            'mentor_magang_id' => $mHaikalId,
            'peserta_magang_id' => $haikal->id,
            'judul_tugas' => 'Desain Carousel Instagram "5 Kesalahan Fatal Peserta Magang"',
            'deskripsi' => 'Desain 7 slide microblog di Figma/Photoshop dengan palet warna brand perusahaan.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(2)->toDateString(),
            'status_tugas' => 'Ditinjau',
            'catatan_peserta' => 'Slide 1 sampai 7 telah disusun lengkap dengan sumber data riset.',
            'file_pengumpulan' => ['submissions/carousel_slide_haikal.pdf'],
            'catatan_mentor' => null,
        ]);

        // 3. Revisi (Diminta perbaikan mentor)
        Penugasan::create([
            'mentor_magang_id' => $mHaikalId,
            'peserta_magang_id' => $haikal->id,
            'judul_tugas' => 'Script Writing Naskah Podcast Tech Series Episode 3',
            'deskripsi' => 'Buat outline pertanyaan dan topik obrolan bersama software engineer senior berdurasi 30 menit.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(1)->toDateString(),
            'status_tugas' => 'Revisi',
            'catatan_peserta' => 'Draft naskah pertanyaan telah disusun sebanyak 15 poin.',
            'file_pengumpulan' => ['submissions/draft_naskah_podcast.docx'],
            'catatan_mentor' => 'Pertanyaan di segmen kedua terlalu teoritis, ubah fokus ke sharing pengalaman praktis di dunia kerja.',
        ]);

        // 4. Aktif (Sedang berjalan normal)
        Penugasan::create([
            'mentor_magang_id' => $mHaikalId,
            'peserta_magang_id' => $haikal->id,
            'judul_tugas' => 'Editing Video Highlight Webinar Nasional Bootcamp',
            'deskripsi' => 'Potong momen terbaik webinar 2 jam menjadi video rangkuman 3 menit untuk channel YouTube.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->addDays(6)->toDateString(),
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // 5. Aktif - Telat / Terlewat Deadline
        Penugasan::create([
            'mentor_magang_id' => $mHaikalId,
            'peserta_magang_id' => $haikal->id,
            'judul_tugas' => 'Riset Tren Audio & Visual TikTok Tech Mingguan',
            'deskripsi' => 'Kumpulkan 10 sound viral dan referensi transisi kamera terkini yang cocok untuk produk edukasi.',
            'foto_petunjuk' => null,
            'deadline' => Carbon::now()->subDays(2)->toDateString(), // Telat
            'status_tugas' => 'Aktif',
            'catatan_peserta' => null,
            'file_pengumpulan' => null,
            'catatan_mentor' => null,
        ]);

        // --- PERIZINAN HAIKAL DZAKY (5 Data) ---
        Perizinan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_izin' => 'Sakit',
            'keterangan' => 'Sakit flu dan batuk, beristirahat sesuai rekomendasi klinik kampus.',
            'tanggal_izin' => Carbon::now()->subDays(6)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Izin di-ACC, lekas sembuh.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_izin' => 'Izin Praktikum',
            'keterangan' => 'Menghadiri praktikum multimedia dan ujian kompetensi keahlian di lab kampus.',
            'tanggal_izin' => Carbon::now()->subDays(3)->toDateString(),
            'file_pendukung' => null,
            'status' => 'selesai',
            'catatan_mentor' => 'Izin disetujui, selamat praktikum.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_izin' => 'Keperluan Keluarga',
            'keterangan' => 'Mendampingi orang tua kontrol kesehatan di rumah sakit.',
            'tanggal_izin' => Carbon::now()->addDays(3)->toDateString(),
            'file_pendukung' => null,
            'status' => 'pending', // Belum di-ACC
            'catatan_mentor' => null,
        ]);
        Perizinan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_izin' => 'Terlambat',
            'keterangan' => 'Kendaraan mogok di perjalanan menuju kantor dan harus ke bengkel terlebih dahulu.',
            'tanggal_izin' => Carbon::yesterday()->toDateString(),
            'file_pendukung' => null,
            'status' => 'diproses',
            'catatan_mentor' => 'Sedang diverifikasi mentor pembimbing.',
        ]);
        Perizinan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_izin' => 'Izin Libur Mandiri',
            'keterangan' => 'Mengajukan libur tambahan setelah masa ujian selesai.',
            'tanggal_izin' => Carbon::now()->subDays(15)->toDateString(),
            'file_pendukung' => null,
            'status' => 'ditolak',
            'catatan_mentor' => 'Tidak memenuhi ketentuan regulasi izin operasional.',
        ]);

        // --- BIMBINGAN HAIKAL DZAKY (5 Data) ---
        Bimbingan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'tanggal' => Carbon::now()->subDays(9)->toDateString(),
            'topik' => 'Brainstorming Ide Visual Brand Identity & Tone of Voice Media Sosial',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/cc-haikal-001',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'tanggal' => Carbon::now()->subDays(4)->toDateString(),
            'topik' => 'Review Storyboard & Teknik Transisi Video CapCut / Premiere Pro',
            'status' => 'completed',
            'link_meet' => 'https://meet.google.com/cc-haikal-002',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'tanggal' => Carbon::now()->addDays(1)->toDateString(),
            'topik' => 'Teknik Penataan Audio Mixing dan Sound Design Podcast',
            'status' => 'accepted', // Sudah di-ACC
            'link_meet' => 'https://meet.google.com/cc-haikal-003',
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'tanggal' => Carbon::now()->addDays(4)->toDateString(),
            'topik' => 'Evaluasi Engagement Rate TikTok & Reels Bulanan',
            'status' => 'requested', // Belum di-ACC
            'link_meet' => null,
        ]);
        Bimbingan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'tanggal' => Carbon::now()->subDays(6)->toDateString(),
            'topik' => 'Konsultasi Pembelian Peralatan Lighting Eksternal',
            'status' => 'rejected', // Ditolak
            'link_meet' => null,
        ]);

        // --- ABSENSI HAIKAL DZAKY (5 Data) ---
        Absensi::create([
            'peserta_magang_id' => $haikal->id,
            'tanggal' => Carbon::now()->subDays(4)->toDateString(),
            'waktu_masuk' => '07:44:20',
            'waktu_keluar' => '17:01:00',
            'status' => 'hadir',
            'keterangan' => 'Hadir WFO studio content creation.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $haikal->id,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'waktu_masuk' => null,
            'waktu_keluar' => null,
            'status' => 'izin',
            'keterangan' => 'Izin praktikum multimedia di kampus.',
            'latitude' => null,
            'longitude' => null,
        ]);
        Absensi::create([
            'peserta_magang_id' => $haikal->id,
            'tanggal' => Carbon::now()->subDays(2)->toDateString(),
            'waktu_masuk' => '07:53:00',
            'waktu_keluar' => '17:15:30',
            'status' => 'hadir',
            'keterangan' => 'Hadir tepat waktu dan pengambilan footage video.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $haikal->id,
            'tanggal' => Carbon::yesterday()->toDateString(),
            'waktu_masuk' => '08:26:10',
            'waktu_keluar' => '17:35:00',
            'status' => 'telat',
            'keterangan' => 'Terlambat karena kendala kendaraan bermotor di jalan.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);
        Absensi::create([
            'peserta_magang_id' => $haikal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'waktu_masuk' => '07:49:15',
            'waktu_keluar' => null,
            'status' => 'hadir',
            'keterangan' => 'Presensi pagi WFO tercatat.',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
        ]);

        // --- LAPORAN HARIAN HAIKAL DZAKY (5 Data) ---
        LaporanHarian::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'laporan' => 'Mengambil footage video b-roll di area kantor untuk kebutuhan konten reels profil lingkungan kerja magang.',
            'komentar_mentor' => 'Sudut pengambilan gambar dan pencahayaan alami sudah sangat baik.',
            'created_at' => Carbon::now()->subDays(4)->setTime(16, 45, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'laporan' => 'Menyusun draft naskah dan alur cerita (storyboard) untuk video edukasi tips menghadapi wawancara kerja.',
            'komentar_mentor' => 'Alur cerita menarik dan relevan untuk mahasiswa tingkat akhir.',
            'created_at' => Carbon::now()->subDays(3)->setTime(17, 0, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'laporan' => 'Melakukan editing video reels dan menambahkan subtitle interaktif sesuai tren media sosial terkini.',
            'komentar_mentor' => null,
            'created_at' => Carbon::now()->subDays(2)->setTime(17, 15, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'laporan' => 'Mendesain feed Instagram carousel berisi 7 slide tips produktivitas kerja saat magang hybrid.',
            'komentar_mentor' => 'Tipografi slide rapi dan mudah dibaca.',
            'created_at' => Carbon::yesterday()->setTime(16, 30, 0),
        ]);
        LaporanHarian::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'laporan' => 'Menyiapkan peralatan rekaman mic clip-on dan kamera untuk agenda podcast mingguan besok.',
            'komentar_mentor' => null,
            'created_at' => Carbon::today()->setTime(14, 15, 0),
        ]);

        // --- PERSURATAN HAIKAL DZAKY (5 Data) ---
        Persuratan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_surat' => 'Surat Keterangan Magang Aktif',
            'keperluan' => 'Lampiran pengajuan beasiswa prestasi non-akademik di universitas.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-aktif-haikal.pdf',
            'catatan_mentor' => 'Dokumen resmi telah diterbitkan dan divalidasi.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_surat' => 'Surat Pengantar Seminar Hasil Magang',
            'keperluan' => 'Pendaftaran sidang presentasi portofolio kerja magang.',
            'file_pendukung' => null,
            'status' => 'selesai',
            'link_dokumen' => 'dokumen/surat-pengantar-haikal.pdf',
            'catatan_mentor' => 'Surat telah disetujui mentor.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_surat' => 'Surat Izin Penelitian Skripsi',
            'keperluan' => 'Wawancara studi strategi branding video edukasi di industri kreatif.',
            'file_pendukung' => null,
            'status' => 'diproses',
            'link_dokumen' => null,
            'catatan_mentor' => 'Sedang diverifikasi bagian administrasi umum.',
        ]);
        Persuratan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_surat' => 'Surat Rekomendasi Magang',
            'keperluan' => 'Pendaftaran program sertifikasi video editor Adobe Certified Professional.',
            'file_pendukung' => null,
            'status' => 'pending', // Belum di-ACC
            'link_dokumen' => null,
            'catatan_mentor' => null,
        ]);
        Persuratan::create([
            'peserta_magang_id' => $haikal->id,
            'jenis_surat' => 'Surat Keterangan Selesai Magang',
            'keperluan' => 'Bukti penyelesaian magang konversi sks kampus.',
            'file_pendukung' => null,
            'status' => 'ditolak',
            'link_dokumen' => null,
            'catatan_mentor' => 'Periode magang belum berakhir.',
        ]);

        // --- EVALUASI BULANAN HAIKAL DZAKY (5 Bulan) ---
        EvaluasiBulanan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'produktivitas' => 4,
            'komunikasi' => 5,
            'keahlian_teknis' => 4,
            'feedback' => 'Sangat komunikatif dan kolaboratif antar anggota tim. Kualitas visual dan audio pada video reels sangat memuaskan.',
            'bulan_tahun' => $bulanSekarang,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Kreatif dalam menemukan sudut pandang konten baru. Disiplin dalam mengikuti arahan revisi naskah podcast.',
            'bulan_tahun' => $bulanLalu,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'produktivitas' => 4,
            'komunikasi' => 4,
            'keahlian_teknis' => 3,
            'feedback' => 'Editing video reels konsisten dan selalu tepat waktu sesuai kalender publikasi media sosial.',
            'bulan_tahun' => $bulan3,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'produktivitas' => 3,
            'komunikasi' => 4,
            'keahlian_teknis' => 4,
            'feedback' => 'Mulai menguasai tone of voice brand perusahaan serta teknik transisi visual yang dinamis.',
            'bulan_tahun' => $bulan4,
        ]);
        EvaluasiBulanan::create([
            'peserta_magang_id' => $haikal->id,
            'mentor_magang_id' => $mHaikalId,
            'produktivitas' => 3,
            'komunikasi' => 3,
            'keahlian_teknis' => 3,
            'feedback' => 'Pengenalan alur kerja produksi konten studio dan setup perlengkapan video berjalan baik.',
            'bulan_tahun' => $bulan5,
        ]);


        // =========================================================================
        // NOTIFIKASI REALISTIS (UNTUK BUDI, ANWAR, HAIKAL, DAN MENTOR MEREKA)
        // =========================================================================
        // Notifikasi untuk Budi Santoso
        Notifikasi::create([
            'user_id' => $budi->user_id,
            'tipe' => 'tugas_baru',
            'judul' => 'Penugasan Baru',
            'pesan' => 'Mentor Andi Wijaya telah memberikan tugas: Implementasi Modul Penugasan & Upload Dokumen.',
            'is_read' => false,
            'data_terkait' => ['penugasan_id' => 4],
        ]);
        Notifikasi::create([
            'user_id' => $budi->user_id,
            'tipe' => 'update_tugas',
            'judul' => 'Tugas Perlu Revisi',
            'pesan' => 'Tugas "Integrasi GPS Geolocation Halaman Presensi" dikembalikan oleh mentor untuk perbaikan.',
            'is_read' => true,
            'data_terkait' => ['penugasan_id' => 3],
        ]);
        Notifikasi::create([
            'user_id' => $budi->user_id,
            'tipe' => 'update_izin',
            'judul' => 'Pengajuan Izin Disetujui',
            'pesan' => 'Pengajuan izin Kuliah Anda pada tanggal ' . Carbon::now()->subDays(3)->format('d-m-Y') . ' telah disetujui mentor.',
            'is_read' => true,
            'data_terkait' => ['perizinan_id' => 2],
        ]);
        Notifikasi::create([
            'user_id' => $budi->user_id,
            'tipe' => 'update_bimbingan',
            'judul' => 'Jadwal Bimbingan Disetujui',
            'pesan' => 'Jadwal bimbingan Anda mengenai Pembahasan Token FCM telah disetujui mentor.',
            'is_read' => false,
            'data_terkait' => ['bimbingan_id' => 3],
        ]);
        Notifikasi::create([
            'user_id' => $budi->user_id,
            'tipe' => 'evaluasi_baru',
            'judul' => 'Evaluasi Bulanan Terbit',
            'pesan' => 'Hasil evaluasi bulanan Anda untuk periode ' . $bulanSekarang . ' telah diterbitkan oleh mentor.',
            'is_read' => true,
            'data_terkait' => ['evaluasi_id' => 1],
        ]);

        // Notifikasi untuk Anwar garden
        Notifikasi::create([
            'user_id' => $anwar->user_id,
            'tipe' => 'tugas_baru',
            'judul' => 'Penugasan Baru',
            'pesan' => 'Mentor Siti Nurhaliza menugaskan: Riset Kata Kunci & Analisis Kompetitor SEO.',
            'is_read' => true,
            'data_terkait' => ['penugasan_id' => 6],
        ]);
        Notifikasi::create([
            'user_id' => $anwar->user_id,
            'tipe' => 'update_izin',
            'judul' => 'Status Izin Diperbarui',
            'pesan' => 'Pengajuan izin Sakit Anda telah disetujui (selesai) oleh mentor.',
            'is_read' => false,
            'data_terkait' => ['perizinan_id' => 7],
        ]);
        Notifikasi::create([
            'user_id' => $anwar->user_id,
            'tipe' => 'update_bimbingan',
            'judul' => 'Bimbingan Disetujui',
            'pesan' => 'Mentor telah menyetujui jadwal bimbingan mengenai Campaign Promo.',
            'is_read' => false,
            'data_terkait' => ['bimbingan_id' => 8],
        ]);

        // Notifikasi untuk Haikal Dzaky
        Notifikasi::create([
            'user_id' => $haikal->user_id,
            'tipe' => 'tugas_baru',
            'judul' => 'Penugasan Baru',
            'pesan' => 'Mentor Bambang Pamungkas memberikan tugas: Desain Carousel Instagram.',
            'is_read' => false,
            'data_terkait' => ['penugasan_id' => 12],
        ]);
        Notifikasi::create([
            'user_id' => $haikal->user_id,
            'tipe' => 'update_tugas',
            'judul' => 'Tugas Berhasil Disetujui (ACC)',
            'pesan' => 'Tugas Produksi Video Reels Edukasi telah disetujui oleh mentor.',
            'is_read' => true,
            'data_terkait' => ['penugasan_id' => 11],
        ]);

        // Notifikasi untuk Mentor (Andi Wijaya)
        Notifikasi::create([
            'user_id' => $mentor1->user_id,
            'tipe' => 'tugas_dikumpulkan',
            'judul' => 'Tugas Baru Dikumpulkan',
            'pesan' => 'Peserta Budi Santoso telah mengumpulkan tugas: Implementasi Push Notification Firebase (FCM).',
            'is_read' => false,
            'data_terkait' => ['penugasan_id' => 2],
        ]);
        Notifikasi::create([
            'user_id' => $mentor1->user_id,
            'tipe' => 'izin_baru',
            'judul' => 'Pengajuan Izin Baru',
            'pesan' => 'Peserta Budi Santoso mengajukan permohonan izin: Keperluan Keluarga.',
            'is_read' => false,
            'data_terkait' => ['perizinan_id' => 3],
        ]);
        Notifikasi::create([
            'user_id' => $mentor1->user_id,
            'tipe' => 'bimbingan_baru',
            'judul' => 'Permintaan Bimbingan Baru',
            'pesan' => 'Peserta Budi Santoso mengajukan jadwal bimbingan: Rancangan Bab 4 Skripsi.',
            'is_read' => false,
            'data_terkait' => ['bimbingan_id' => 4],
        ]);
    }
}

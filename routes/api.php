<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Resources\UserResource;

//login awal
Route::post('/login', [AuthController::class, 'login']);

//register peserta
Route::post('/register', [AuthController::class, 'register']);

//update token fcm
Route::middleware('auth:sanctum')->post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute Admin
    Route::get('/admin/peserta', [\App\Http\Controllers\Api\AdminController::class, 'getPeserta']);
    Route::get('/admin/pending-registrations', [\App\Http\Controllers\Api\AdminController::class, 'getPendingRegistrations']);
    Route::put('/admin/pending-registrations/{id}/approve', [\App\Http\Controllers\Api\AdminController::class, 'approveRegistration']);
    Route::delete('/admin/pending-registrations/{id}/reject', [\App\Http\Controllers\Api\AdminController::class, 'rejectRegistration']);
    Route::get('/admin/peserta/{id}', [\App\Http\Controllers\Api\AdminController::class, 'getDetailPeserta']);
    Route::put('/admin/peserta/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updatePeserta']);
    Route::put('/admin/peserta/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleStatusPeserta']);
    Route::get('/admin/mentor', [\App\Http\Controllers\Api\AdminController::class, 'getMentor']);
    Route::post('/admin/mentor', [\App\Http\Controllers\Api\AdminController::class, 'postMentor']);
    Route::get('/admin/mentor/{id}', [\App\Http\Controllers\Api\AdminController::class, 'getDetailMentor']);
    Route::put('/admin/mentor/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateMentor']);
    Route::get('/admin/divisi', [\App\Http\Controllers\Api\AdminController::class, 'getDivisi']);
    Route::post('/admin/divisi', [\App\Http\Controllers\Api\AdminController::class, 'postDivisi']);
    Route::put('/admin/divisi/{id}', [\App\Http\Controllers\Api\AdminController::class, 'putDivisi']);
    Route::delete('/admin/divisi/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteDivisi']);
    Route::get('/admin/kehadiran-hari-ini', [\App\Http\Controllers\Api\AdminController::class, 'getKehadiranHariIni']);
    Route::get('/admin/tugas', [\App\Http\Controllers\Api\AdminController::class, 'getTugas']);
    Route::get('/admin/laporan/{peserta_id}', [\App\Http\Controllers\Api\AdminController::class, 'getLaporanHarianByPeserta']);
    Route::get('/admin/bimbingan/{peserta_id}', [\App\Http\Controllers\Api\AdminController::class, 'getBimbinganByPeserta']);
    Route::get('/admin/persuratan', [\App\Http\Controllers\Api\PersuratanController::class, 'indexAdmin']);
    Route::get('/admin/perizinan', [\App\Http\Controllers\Api\PerizinanController::class, 'indexAdmin']);
    // ambil data login
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['peserta', 'mentor.divisi']);
        return new UserResource($user);
    });
    
    // Rute Absensi
    Route::post('/absensi', [AbsensiController::class, 'store']);

    // Rute Laporan Harian
    Route::post('/laporan', [\App\Http\Controllers\Api\LaporanHarianController::class, 'store']);

    // Rute Mentor
    Route::get('/mentor/peserta', [\App\Http\Controllers\Api\MentorController::class, 'getPesertaAbsensi']);
    Route::get('/mentor/peserta/{id}', [\App\Http\Controllers\Api\MentorController::class, 'getDetailPeserta']);
    Route::get('/mentor/laporan', [\App\Http\Controllers\Api\MentorController::class, 'getLaporanHarian']);
    Route::get('/mentor/laporan/{peserta_id}', [\App\Http\Controllers\Api\MentorController::class, 'getLaporanHarianByPeserta']);
    Route::put('/mentor/laporan/{id}/komentar', [\App\Http\Controllers\Api\MentorController::class, 'updateKomentarLaporan']);
    Route::post('/mentor/evaluasi', [\App\Http\Controllers\Api\MentorController::class, 'storeEvaluasiBulanan']);
    Route::get('/mentor/evaluasi', [\App\Http\Controllers\Api\MentorController::class, 'getRiwayatEvaluasi']);
    Route::get('/mentor/evaluasi/{peserta_id}', [\App\Http\Controllers\Api\MentorController::class, 'getRiwayatEvaluasi']);
    Route::get('/mentor/bimbingan', [\App\Http\Controllers\Api\MentorController::class, 'getBimbingan']);
    Route::put('/mentor/bimbingan/{id}', [\App\Http\Controllers\Api\MentorController::class, 'updateBimbinganStatus']);
    Route::get('/mentor/persuratan', [\App\Http\Controllers\Api\PersuratanController::class, 'indexMentor']);
    Route::post('/mentor/persuratan/{id}/status', [\App\Http\Controllers\Api\PersuratanController::class, 'updateStatus']);
    Route::get('/mentor/perizinan', [\App\Http\Controllers\Api\PerizinanController::class, 'indexMentor']);
    Route::post('/mentor/perizinan/{id}/status', [\App\Http\Controllers\Api\PerizinanController::class, 'updateStatus']);
    Route::get('/mentor/penugasan', [\App\Http\Controllers\Api\PenugasanController::class, 'indexMentor']);
    Route::post('/mentor/penugasan', [\App\Http\Controllers\Api\PenugasanController::class, 'store']);
    Route::post('/mentor/penugasan/{id}', [\App\Http\Controllers\Api\PenugasanController::class, 'update']);
    Route::put('/mentor/penugasan/{id}', [\App\Http\Controllers\Api\PenugasanController::class, 'update']);
    Route::put('/mentor/penugasan/{id}/status', [\App\Http\Controllers\Api\PenugasanController::class, 'updateStatus']);
    Route::put('/mentor/penugasan/{id}/review', [\App\Http\Controllers\Api\PenugasanController::class, 'reviewTugas']);
    Route::delete('/mentor/penugasan/{id}', [\App\Http\Controllers\Api\PenugasanController::class, 'destroy']);
    Route::get('/mentor/pengaturan-absen', [\App\Http\Controllers\Api\MentorController::class, 'getPengaturanAbsen']);
    Route::post('/mentor/pengaturan-absen', [\App\Http\Controllers\Api\MentorController::class, 'setPengaturanAbsen']);
    // Rute Peserta
    Route::get('/peserta/evaluasi', [\App\Http\Controllers\Api\PesertaController::class, 'getEvaluasiBulanan']);
    Route::post('/peserta/bimbingan', [\App\Http\Controllers\Api\PesertaController::class, 'storeBimbingan']);
    Route::get('/peserta/bimbingan', [\App\Http\Controllers\Api\PesertaController::class, 'getRiwayatBimbingan']);
    Route::post('/peserta/persuratan', [\App\Http\Controllers\Api\PersuratanController::class, 'store']);
    Route::get('/peserta/persuratan', [\App\Http\Controllers\Api\PersuratanController::class, 'index']);
    Route::post('/peserta/perizinan', [\App\Http\Controllers\Api\PerizinanController::class, 'store']);
    Route::get('/peserta/perizinan', [\App\Http\Controllers\Api\PerizinanController::class, 'index']);
    Route::get('/peserta/penugasan', [\App\Http\Controllers\Api\PenugasanController::class, 'indexPeserta']);
    Route::post('/peserta/penugasan/{id}/submit', [\App\Http\Controllers\Api\PenugasanController::class, 'submitTugas']);
    Route::put('/peserta/penugasan/{id}/status', [\App\Http\Controllers\Api\PenugasanController::class, 'updateStatus']);
    Route::get('/peserta/absensi', [AbsensiController::class, 'getRiwayatPeserta']);

    // Rute Notifikasi (Global untuk Mentor dan Peserta)
    Route::get('/notifikasi', [\App\Http\Controllers\Api\NotifikasiController::class, 'index']);
    Route::put('/notifikasi/{id}/read', [\App\Http\Controllers\Api\NotifikasiController::class, 'markAsRead']);

    // Rute Profil
    Route::post('/profile/update-data', [\App\Http\Controllers\Api\ProfileController::class, 'updateData']);
    Route::post('/profile/update-picture', [\App\Http\Controllers\Api\ProfileController::class, 'updatePicture']);
    Route::post('/profile/update-password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
});

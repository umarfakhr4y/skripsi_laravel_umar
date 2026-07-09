<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Resources\UserResource;

//login awal
Route::post('/login', [AuthController::class, 'login']);
//test

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

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
    Route::get('/mentor/laporan', [\App\Http\Controllers\Api\MentorController::class, 'getLaporanHarian']);
    Route::get('/mentor/laporan/{peserta_id}', [\App\Http\Controllers\Api\MentorController::class, 'getLaporanHarianByPeserta']);
});

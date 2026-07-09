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
});

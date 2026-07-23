<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peserta_magangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Hubungan ke table users
            $table->string('nim')->unique();
            $table->string('nama_lengkap');
            $table->string('universitas')->nullable();
            $table->string('prodi');
            $table->date('periode_masuk')->nullable();
            $table->date('periode_keluar')->nullable();
            $table->string('no_telpon')->nullable();
            $table->string('status')->default('Aktif')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_magangs');
    }
};

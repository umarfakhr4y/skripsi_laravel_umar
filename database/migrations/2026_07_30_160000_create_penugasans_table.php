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
        Schema::create('penugasans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_magang_id')->constrained('mentor_magangs')->onDelete('cascade');
            $table->foreignId('peserta_magang_id')->constrained('peserta_magangs')->onDelete('cascade');
            $table->string('judul_tugas');
            $table->text('deskripsi');
            $table->json('foto_petunjuk')->nullable();
            $table->date('deadline');
            $table->string('status_tugas')->default('Aktif'); // Aktif, Ditinjau, Selesai
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasans');
    }
};

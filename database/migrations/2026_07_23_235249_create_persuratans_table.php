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
        Schema::create('persuratans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_magang_id')->constrained('peserta_magangs')->onDelete('cascade');
            $table->string('jenis_surat');
            $table->text('keperluan');
            $table->enum('status', ['pending', 'diproses', 'selesai', 'ditolak'])->default('pending');
            $table->string('link_dokumen')->nullable();
            $table->text('catatan_mentor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persuratans');
    }
};

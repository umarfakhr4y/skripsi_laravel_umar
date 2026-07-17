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
        Schema::create('bimbingans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('peserta_magang_id');
            $table->unsignedBigInteger('mentor_magang_id');
            $table->date('tanggal');
            $table->text('topik');
            $table->string('status')->default('menunggu'); // menunggu, disetujui, ditolak, selesai
            $table->timestamps();

            $table->foreign('peserta_magang_id')->references('id')->on('peserta_magangs')->onDelete('cascade');
            $table->foreign('mentor_magang_id')->references('id')->on('mentor_magangs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bimbingans');
    }
};

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
        Schema::create('evaluasi_bulanans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('peserta_magang_id');
            $table->unsignedBigInteger('mentor_magang_id');
            $table->integer('produktivitas');
            $table->integer('komunikasi');
            $table->integer('keahlian_teknis');
            $table->text('feedback');
            $table->string('bulan_tahun'); // Format like 'APRIL 2024' or similar
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
        Schema::dropIfExists('evaluasi_bulanans');
    }
};

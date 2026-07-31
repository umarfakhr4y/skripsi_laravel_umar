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
        Schema::table('penugasans', function (Blueprint $table) {
            $table->text('catatan_peserta')->nullable()->after('status_tugas');
            $table->json('file_pengumpulan')->nullable()->after('catatan_peserta');
            $table->text('catatan_mentor')->nullable()->after('file_pengumpulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropColumn(['catatan_peserta', 'file_pengumpulan', 'catatan_mentor']);
        });
    }
};

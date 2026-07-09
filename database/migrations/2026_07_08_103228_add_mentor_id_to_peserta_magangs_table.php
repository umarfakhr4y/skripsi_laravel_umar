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
        Schema::table('peserta_magangs', function (Blueprint $table) {
            $table->foreignId('mentor_magang_id')->nullable()->after('prodi')->constrained('mentor_magangs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_magangs', function (Blueprint $table) {
            $table->dropForeign(['mentor_magang_id']);
            $table->dropColumn('mentor_magang_id');
        });
    }
};

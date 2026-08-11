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
        Schema::table('mentor_magangs', function (Blueprint $table) {
            $table->string('profile_picture')->nullable();
        });

        Schema::table('peserta_magangs', function (Blueprint $table) {
            $table->string('profile_picture')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_magangs', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });

        Schema::table('peserta_magangs', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });
    }
};

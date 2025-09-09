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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_anggota', 20)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('unit_kerja', 100)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nomor_anggota', 'nomor', 'nik', 'jabatan', 'unit_kerja','tanggal_masuk','status']);
        });
    }
};

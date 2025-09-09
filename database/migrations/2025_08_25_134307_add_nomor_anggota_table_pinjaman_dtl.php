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
        Schema::table('pinjaman_dtl', function (Blueprint $table) {
            $table->string('nomor_anggota')->after('id_pinjaman');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pinjaman_dtl', function (Blueprint $table) {
            $table->dropColumn('nomor_anggota');
        });
    }
};

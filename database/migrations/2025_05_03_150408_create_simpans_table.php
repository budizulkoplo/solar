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
        Schema::create('simpan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id')->nullable();
            $table->enum('jenis', ['pokok', 'wajib', 'sukarela'])->nullable();
            $table->decimal('jumlah', 12, 2)->nullable();
            $table->date('tanggal')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpan');
    }
};

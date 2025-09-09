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
        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id')->nullable();
            $table->decimal('jumlah', 12, 2)->nullable();
            $table->integer('tenor')->nullable();
            $table->decimal('bunga', 5, 2)->nullable();
            $table->date('tanggal_ambil')->nullable();
            $table->enum('status', ['aktif', 'lunas'])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};

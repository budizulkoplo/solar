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
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_invoice', 50)->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->unsignedBigInteger('anggota_id')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->enum('metode_bayar', ['tunai', 'potong_gaji', 'cicilan'])->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};

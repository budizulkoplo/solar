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
        Schema::create('kontribusi_shu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id')->nullable();
            $table->integer('tahun')->nullable();
            $table->decimal('total_kontribusi', 12, 2)->nullable();
            $table->decimal('total_belanja', 12, 2)->nullable();
            $table->decimal('total_shu', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontribusi_shu');
    }
};

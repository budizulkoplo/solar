<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ngaji', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->index();
            $table->string('pegawai_nama')->nullable();
            $table->string('surat');
            $table->unsignedInteger('ayat');
            $table->string('type', 20)->nullable()->default('rutin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngaji');
    }
};

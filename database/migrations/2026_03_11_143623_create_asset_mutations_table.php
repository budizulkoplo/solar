<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_mutations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('jenis',50)->nullable();
            $table->decimal('nilai',15,2)->nullable();
            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_mutations');
    }
};
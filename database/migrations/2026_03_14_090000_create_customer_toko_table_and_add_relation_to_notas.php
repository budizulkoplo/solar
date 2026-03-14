<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_toko')) {
            Schema::create('customer_toko', function (Blueprint $table) {
                $table->id();
                $table->string('kode_customer', 30)->unique();
                $table->string('nama_lengkap');
                $table->string('no_hp', 20)->nullable();
                $table->text('alamat')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('notas', function (Blueprint $table) {
            if (!Schema::hasColumn('notas', 'customer_toko_id')) {
                $table->unsignedBigInteger('customer_toko_id')->nullable()->after('customer_id');
                $table->foreign('customer_toko_id')->references('id')->on('customer_toko')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'customer_toko_id')) {
                $table->dropForeign(['customer_toko_id']);
                $table->dropColumn('customer_toko_id');
            }
        });

        Schema::dropIfExists('customer_toko');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pekerjaan_kontruksi', function (Blueprint $table) {
            if (!Schema::hasColumn('pekerjaan_kontruksi', 'idkodetransaksi')) {
                $table->unsignedBigInteger('idkodetransaksi')->nullable()->after('jenis_pekerjaan');
            }

            if (!Schema::hasColumn('pekerjaan_kontruksi', 'harga_satuan')) {
                $table->decimal('harga_satuan', 20, 2)->nullable()->after('jumlah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pekerjaan_kontruksi', function (Blueprint $table) {
            if (Schema::hasColumn('pekerjaan_kontruksi', 'harga_satuan')) {
                $table->dropColumn('harga_satuan');
            }

            if (Schema::hasColumn('pekerjaan_kontruksi', 'idkodetransaksi')) {
                $table->dropColumn('idkodetransaksi');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            if (!Schema::hasColumn('notas', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('vendor_id');
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            }

            if (!Schema::hasColumn('notas', 'keterangan_customer')) {
                $table->string('keterangan_customer')->nullable()->after('customer_id');
            }

            if (!Schema::hasColumn('notas', 'project_tujuan_id')) {
                $table->unsignedBigInteger('project_tujuan_id')->nullable()->after('idproject');
                $table->foreign('project_tujuan_id')->references('id')->on('projects')->nullOnDelete();
            }

            if (!Schema::hasColumn('notas', 'jenis_penjualan')) {
                $table->enum('jenis_penjualan', ['toko', 'project'])->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'jenis_penjualan')) {
                $table->dropColumn('jenis_penjualan');
            }

            if (Schema::hasColumn('notas', 'project_tujuan_id')) {
                $table->dropForeign(['project_tujuan_id']);
                $table->dropColumn('project_tujuan_id');
            }

            if (Schema::hasColumn('notas', 'keterangan_customer')) {
                $table->dropColumn('keterangan_customer');
            }

            if (Schema::hasColumn('notas', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_mutations', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_mutations', 'subjenis')) {
                $table->string('subjenis', 50)->nullable()->after('jenis');
            }

            if (!Schema::hasColumn('asset_mutations', 'pihak_terkait')) {
                $table->string('pihak_terkait', 255)->nullable()->after('nilai');
            }

            if (!Schema::hasColumn('asset_mutations', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable()->after('pihak_terkait');
            }

            if (!Schema::hasColumn('asset_mutations', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_mutations', function (Blueprint $table) {
            foreach (['tanggal_selesai', 'tanggal_mulai', 'pihak_terkait', 'subjenis'] as $column) {
                if (Schema::hasColumn('asset_mutations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

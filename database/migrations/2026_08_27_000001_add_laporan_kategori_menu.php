<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('menus')->where('link', 'laporan.kategori')->exists();
        if ($exists) {
            return;
        }

        $neraca = DB::table('menus')->where('link', 'laporan.neraca')->first();
        $parent = $neraca
            ?: DB::table('menus')->where('name', 'Laporan')->orderBy('id')->first();

        DB::table('menus')->insert([
            'name' => 'Laporan by Kategori',
            'link' => 'laporan.kategori',
            'parent_id' => $neraca->parent_id ?? ($parent->id ?? null),
            'role' => $neraca->role ?? ($parent->role ?? ';superadmin;admin;keuangan;direktur;manager;adminpt;'),
            'seq' => ($neraca->seq ?? ($parent->seq ?? 10)) + 1,
            'icon' => $neraca->icon ?? 'bi bi-tags',
            'module' => $neraca->module ?? ($parent->module ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('link', 'laporan.kategori')->delete();
    }
};

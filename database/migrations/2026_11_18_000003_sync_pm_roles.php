<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_LIST = ';superadmin;admin;adminproject;adminpt;direktur;hrd;it;keuangan;manager;marketing;pengurus;';

    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->where('module', 'pm')->update(['role' => self::ROLE_LIST]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->where('module', 'pm')->update([
            'role' => ';superadmin;admin;hrd;pengurus;keuangan;direktur;manager;adminpt;marketing;',
        ]);
    }
};

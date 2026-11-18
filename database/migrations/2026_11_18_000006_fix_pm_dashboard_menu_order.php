<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->where('module', 'pm')->where('link', 'pm.dashboard')->update(['seq' => 1]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.projects.index')->update(['seq' => 2]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.board.index')->update(['seq' => 3]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.projects.board')->update(['seq' => 3]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.tasks.index')->update(['seq' => 4]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->where('module', 'pm')->where('link', 'pm.projects.index')->update(['seq' => 1]);
        DB::table('menus')->where('module', 'pm')->whereIn('link', ['pm.board.index', 'pm.projects.board'])->update(['seq' => 2]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.tasks.index')->update(['seq' => 3]);
        DB::table('menus')->where('module', 'pm')->where('link', 'pm.dashboard')->delete();
    }
};

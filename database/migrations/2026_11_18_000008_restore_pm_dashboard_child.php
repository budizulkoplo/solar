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

        $parentId = DB::table('menus')
            ->where('name', 'Project Management')
            ->where('module', 'pm')
            ->whereNull('parent_id')
            ->value('id');

        if (!$parentId) {
            return;
        }

        DB::table('menus')->where('id', $parentId)->update(['link' => null]);

        DB::table('menus')->updateOrInsert(
            ['link' => 'pm.dashboard'],
            [
                'name' => 'Dashboard',
                'parent_id' => $parentId,
                'role' => self::ROLE_LIST,
                'seq' => 1,
                'icon' => 'bi bi-speedometer2',
                'module' => 'pm',
            ]
        );

        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.projects.index')->update(['seq' => 2]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.board.index')->update(['seq' => 3]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.projects.board')->update(['seq' => 3]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.tasks.index')->update(['seq' => 4]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        $parentId = DB::table('menus')
            ->where('name', 'Project Management')
            ->where('module', 'pm')
            ->whereNull('parent_id')
            ->value('id');

        DB::table('menus')->where('link', 'pm.dashboard')->where('parent_id', $parentId)->delete();
        DB::table('menus')->where('id', $parentId)->update(['link' => 'pm.dashboard']);
    }
};

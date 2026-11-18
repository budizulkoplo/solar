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

        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.dashboard')->delete();
        DB::table('menus')->where('id', $parentId)->update(['link' => null, 'seq' => 25]);

        $topMenus = [
            ['Dashboard', 'pm.dashboard', 'bi bi-speedometer2', 24],
            ['Contributors', 'pm.contributors', 'bi bi-people', 26],
            ['Leaderboard', 'pm.leaderboard', 'bi bi-trophy', 27],
        ];

        foreach ($topMenus as [$name, $link, $icon, $seq]) {
            DB::table('menus')->updateOrInsert(
                ['link' => $link],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'role' => self::ROLE_LIST,
                    'seq' => $seq,
                    'icon' => $icon,
                    'module' => 'pm',
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->whereIn('link', ['pm.dashboard', 'pm.contributors', 'pm.leaderboard'])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_LIST = ';superadmin;admin;adminproject;adminpt;direktur;hrd;it;keuangan;manager;marketing;pengurus;';

    public function up(): void
    {
        if (!Schema::hasTable('menus') || !Schema::hasColumn('menus', 'module')) {
            return;
        }

        $parentId = DB::table('menus')
            ->where('name', 'Project Management')
            ->where('module', 'pm')
            ->whereNull('parent_id')
            ->value('id');

        if (!$parentId) {
            $parentId = DB::table('menus')->insertGetId([
                'name' => 'Project Management',
                'link' => null,
                'parent_id' => null,
                'role' => self::ROLE_LIST,
                'seq' => 25,
                'icon' => 'bi bi-kanban',
                'module' => 'pm',
            ]);
        }

        $menus = [
            ['Project', 'pm.projects.index', 'bi bi-kanban', 1],
            ['Project Board', 'pm.projects.board', 'bi bi-columns-gap', 2],
            ['Tugas', 'pm.tasks.index', 'bi bi-check2-square', 3],
        ];

        foreach ($menus as [$name, $link, $icon, $seq]) {
            DB::table('menus')->updateOrInsert(
                ['link' => $link],
                [
                    'name' => $name,
                    'parent_id' => $parentId,
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

        $links = ['pm.projects.index', 'pm.projects.board', 'pm.tasks.index'];
        DB::table('menus')->whereIn('link', $links)->delete();
        DB::table('menus')->where('name', 'Project Management')->where('module', 'pm')->whereNull('parent_id')->delete();
    }
};

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

        $parentId = DB::table('menus')
            ->where('name', 'Project Management')
            ->where('module', 'pm')
            ->whereNull('parent_id')
            ->value('id');

        if (!$parentId) {
            return;
        }

        DB::table('menus')->where('id', $parentId)->update(['link' => 'pm.dashboard']);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.dashboard')->delete();
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.projects.index')->update(['seq' => 1]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.board.index')->update(['seq' => 2]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.projects.board')->update(['seq' => 2]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.tasks.index')->update(['seq' => 3]);
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

        if (!$parentId) {
            return;
        }

        DB::table('menus')->where('id', $parentId)->update(['link' => null]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.projects.index')->update(['seq' => 2]);
        DB::table('menus')->where('parent_id', $parentId)->whereIn('link', ['pm.board.index', 'pm.projects.board'])->update(['seq' => 3]);
        DB::table('menus')->where('parent_id', $parentId)->where('link', 'pm.tasks.index')->update(['seq' => 4]);
    }
};

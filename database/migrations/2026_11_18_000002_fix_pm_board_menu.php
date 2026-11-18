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

        DB::table('menus')
            ->where('module', 'pm')
            ->where('name', 'Project Board')
            ->update(['link' => 'pm.board.index']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('module', 'pm')
            ->where('name', 'Project Board')
            ->update(['link' => 'pm.projects.board']);
    }
};

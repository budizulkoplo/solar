<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->insert([
            'name' => 'Barang',
            'link' => 'barang.list',
            'parent_id'  => 6,
            'role' => ';superadmin;admin;',
            'seq' => 4,
            'icon' => 'bi bi-people',
        ]);
    }
}

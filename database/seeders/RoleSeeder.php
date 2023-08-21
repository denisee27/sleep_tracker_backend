<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        Role::truncate();
        $item = new Role();
        $item->name = 'Super Admin';
        $item->access = ['*'];
        $item->save();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}

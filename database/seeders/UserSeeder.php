<?php

namespace Database\Seeders;

use App\Models\JobPosition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        User::truncate();
        $item = new User();
        $item->role_id = Role::get()->pluck('id')->first();
        $item->nik = '112233';
        $item->name = 'Super Administrator';
        $item->email = 'admin@demo.com';
        $item->password = sha1(md5(sha1('admin123')) . 'Tr1@5M1TR4');
        $item->save();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}

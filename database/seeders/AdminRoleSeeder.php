<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("admin_roles")->insert([
            [
              'id' => 1,
              'name' => 'Super Admin',
              'manage_properties'=> 'yes',
              'manage_accounts'=> 'yes',
              'manage_admins'=> 'yes',
              'manage_estate_manager'=>'yes',
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("user_types")->insert([
            [
              'id' => 1,
              'name' => 'Landlord',
              'admin_management'=> 'yes',
              'user_management'=> 'yes',
              'complaint_management'=> 'yes',
              'estate_manager_id' => null,
            ],

            [
              'id' => 2,
              'name' => 'Agent',
              'admin_management'=> 'yes',
              'user_management'=> 'yes',
              'complaint_management'=> 'yes',
              'estate_manager_id' => null,
            ],
                
            [
              'id' => 3,
              'name' => 'Admin',
              'admin_management'=> 'no',
              'user_management'=> 'no',
              'complaint_management'=> 'no',
              'estate_manager_id' => null,
            ],
        ]);
    }
}

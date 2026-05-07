<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("admins")->insert([
            [
              'id' => 1,
              'uuid'=> (string) Str::uuid(),
              'name'=> 'Emeka David',
              'email'=> 'admin@ffsd.com',
              'email_verified_at' => now(),
              'role_id' => 1,
              'password'=> Hash::make('testingPassword'),
            ]
        ]);
    }
}

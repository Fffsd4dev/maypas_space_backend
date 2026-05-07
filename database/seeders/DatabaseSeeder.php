<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            AdminRoleSeeder::class,
            AdminSeeder::class,
            UserTypeSeeder::class,
            NumberSeeder::class,
            FeatureSeeder::class,
            PropertyTypeSeeder::class,
            ComplaintCategorySeeder::class,
            CountrySeeder::class,
        ]);
    }
}

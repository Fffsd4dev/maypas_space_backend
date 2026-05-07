<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('data/countries.json'));
        $countries = json_decode($json, true);

        foreach ($countries as $country) {
            DB::table('countries')->insert([
                'country'          => $country['country'] ?? null,
                'currency_name'    => $country['currency_name'] ?? null,
                'currency_code'    => $country['currency_code'] ?? null,
                'currency_symbol'  => $country['currency_symbol'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}

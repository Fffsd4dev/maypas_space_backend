<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Number;
class NumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add numbers 1 through 9
        foreach (range(1, 9) as $i) {
            Number::create(['number' => (string) $i]);
        }

        // Add 10+
        Number::create(['number' => '10+']);
    }
}

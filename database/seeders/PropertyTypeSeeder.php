<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Airbnb',
            'Commercial property',
            'Apartments',
            'House',
            'Land',
        ];

        foreach ($types as $type) {
            PropertyType::create(['property_type' => $type]);
        }
    }
}

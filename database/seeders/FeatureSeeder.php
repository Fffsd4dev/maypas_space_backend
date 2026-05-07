<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            'Boys quarter',
            'Free coffee',
            'excision',
            'Church nearby',
            'Child care',
            'Fast internet',
            'Big compound',
            '24 hours electricity',
            'Drainage system',
            'All rooms ensuite',
            'C of O',
            'elevator',
            'Front desk service',
            '24 hours electricity', // duplicate, intentionally kept as provided
            'CCTV cameras',
            'Free wifi',
            'Children play ground',
            'Swimming pool',
            'survey',
            'Ocean view',
            'Pop ceiling',
            'Street lights',
            'Office supplies',
            'Mosque nearby',
            'Supermarket nearby',
            'Governor’s consent',
            'Water treatment',
            'Jacuzzi',
            'Printing service',
            'Security doors',
            'Restaurant nearby',
            'Gym',
            'Parking space',
            'Security',
        ];

        foreach ($features as $feature) {
            Feature::create(['feature' => $feature]);
        }
    }
}

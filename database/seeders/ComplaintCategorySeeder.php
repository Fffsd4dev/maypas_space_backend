<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ComplaintCategory;

class ComplaintCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Plumbing',        'description' => 'Issues with water supply, leaks, or drainage'],
            ['name' => 'Electricity',     'description' => 'Faulty wiring, power outages, or broken sockets'],
            ['name' => 'Security',        'description' => 'Concerns about locks, gates, or general safety'],
            ['name' => 'Sanitation',      'description' => 'Waste disposal, cleaning, or pest problems'],
            ['name' => 'Structural',      'description' => 'Cracks, roofing, or general building structure issues'],
            ['name' => 'Noise',           'description' => 'Noise disturbance from neighbors or surroundings'],
            ['name' => 'Water Supply',    'description' => 'Shortage or irregular water supply'],
            ['name' => 'Other',           'description' => 'Any issue not covered by the listed categories'],
        ];

        foreach ($categories as $category) {
            ComplaintCategory::create($category);
        }
    }
}

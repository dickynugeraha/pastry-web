<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $categories = [
            ['name' => 'Kue Basah', 'slug' => 'kue-basah'],
            ['name' => 'Kue Kering', 'slug' => 'kue-kering'],
            ['name' => 'Roti', 'slug' => 'roti'],
            ['name' => 'Bolu', 'slug' => 'bolu'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

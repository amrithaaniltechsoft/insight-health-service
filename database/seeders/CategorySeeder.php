<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SubCategory::truncate();
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $categories = [
            'Pregnancy Scans',
            'Diagnostics',
            'Physiotherapy',
            'Blood Tests',
            'Other Diagnostics',
        ];

        foreach ($categories as $catName) {
            Category::create(['name' => $catName]);
        }
    }
}

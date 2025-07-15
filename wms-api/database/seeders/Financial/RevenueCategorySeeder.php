<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\RevenueCategory;
use Illuminate\Database\Seeder;

class RevenueCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $revenueCategories = [
            [
                'name' => 'Storage Revenue',
                'code' => 'STORAGE',
                'description' => 'Revenue from storage services',
                'is_active' => true,
            ],
            [
                'name' => 'Handling Revenue',
                'code' => 'HANDLING',
                'description' => 'Revenue from handling services',
                'is_active' => true,
            ],
            [
                'name' => 'Value-Added Services',
                'code' => 'VAS',
                'description' => 'Revenue from value-added services',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation',
                'code' => 'TRANSPORT',
                'description' => 'Revenue from transportation services',
                'is_active' => true,
            ],
            [
                'name' => 'Other Services',
                'code' => 'OTHER',
                'description' => 'Revenue from other miscellaneous services',
                'is_active' => true,
            ],
        ];

        foreach ($revenueCategories as $category) {
            RevenueCategory::create($category);
        }
    }
}
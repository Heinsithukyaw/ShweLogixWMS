<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\CostCategory;
use Illuminate\Database\Seeder;

class CostCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $costCategories = [
            [
                'name' => 'Labor',
                'code' => 'LABOR',
                'description' => 'All labor-related costs',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Facilities',
                'code' => 'FACILITIES',
                'description' => 'Building and facility-related costs',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Equipment',
                'code' => 'EQUIPMENT',
                'description' => 'Equipment-related costs',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Technology',
                'code' => 'TECHNOLOGY',
                'description' => 'Technology and software-related costs',
                'parent_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Administrative',
                'code' => 'ADMIN',
                'description' => 'Administrative and overhead costs',
                'parent_id' => null,
                'is_active' => true,
            ],
        ];

        foreach ($costCategories as $category) {
            CostCategory::create($category);
        }

        // Create subcategories
        $subCategories = [
            [
                'name' => 'Direct Labor',
                'code' => 'LABOR-DIRECT',
                'description' => 'Warehouse staff directly involved in operations',
                'parent_id' => 1, // Labor
                'is_active' => true,
            ],
            [
                'name' => 'Indirect Labor',
                'code' => 'LABOR-INDIRECT',
                'description' => 'Supervisory and support staff',
                'parent_id' => 1, // Labor
                'is_active' => true,
            ],
            [
                'name' => 'Rent',
                'code' => 'FACILITIES-RENT',
                'description' => 'Building rent or lease costs',
                'parent_id' => 2, // Facilities
                'is_active' => true,
            ],
            [
                'name' => 'Utilities',
                'code' => 'FACILITIES-UTILITIES',
                'description' => 'Electricity, water, gas, etc.',
                'parent_id' => 2, // Facilities
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'code' => 'FACILITIES-MAINT',
                'description' => 'Building maintenance and repairs',
                'parent_id' => 2, // Facilities
                'is_active' => true,
            ],
            [
                'name' => 'Forklifts',
                'code' => 'EQUIPMENT-FORKLIFTS',
                'description' => 'Forklift purchase, rental, and maintenance',
                'parent_id' => 3, // Equipment
                'is_active' => true,
            ],
            [
                'name' => 'Conveyors',
                'code' => 'EQUIPMENT-CONVEYORS',
                'description' => 'Conveyor systems and maintenance',
                'parent_id' => 3, // Equipment
                'is_active' => true,
            ],
            [
                'name' => 'Racking',
                'code' => 'EQUIPMENT-RACKING',
                'description' => 'Storage racking and shelving',
                'parent_id' => 3, // Equipment
                'is_active' => true,
            ],
            [
                'name' => 'WMS Software',
                'code' => 'TECH-WMS',
                'description' => 'Warehouse Management System costs',
                'parent_id' => 4, // Technology
                'is_active' => true,
            ],
            [
                'name' => 'Hardware',
                'code' => 'TECH-HARDWARE',
                'description' => 'Computers, scanners, and other hardware',
                'parent_id' => 4, // Technology
                'is_active' => true,
            ],
            [
                'name' => 'Network',
                'code' => 'TECH-NETWORK',
                'description' => 'Network infrastructure and services',
                'parent_id' => 4, // Technology
                'is_active' => true,
            ],
            [
                'name' => 'Management',
                'code' => 'ADMIN-MGMT',
                'description' => 'Management salaries and expenses',
                'parent_id' => 5, // Administrative
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'code' => 'ADMIN-INSURANCE',
                'description' => 'Business and liability insurance',
                'parent_id' => 5, // Administrative
                'is_active' => true,
            ],
            [
                'name' => 'Office Supplies',
                'code' => 'ADMIN-SUPPLIES',
                'description' => 'Office supplies and consumables',
                'parent_id' => 5, // Administrative
                'is_active' => true,
            ],
        ];

        foreach ($subCategories as $category) {
            CostCategory::create($category);
        }
    }
}
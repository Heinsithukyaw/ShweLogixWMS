<?php

namespace Database\Seeders;

use Database\Seeders\Financial\CostCategorySeeder;
use Database\Seeders\Financial\RevenueCategorySeeder;
use Database\Seeders\Metrics\DashboardSeeder;
use Database\Seeders\Metrics\MetricDefinitionSeeder;
use Illuminate\Database\Seeder;

class Phase1Seeder extends Seeder
{
    /**
     * Run the database seeds for Phase 1 implementation.
     */
    public function run(): void
    {
        $this->command->info('Seeding Phase 1 data...');

        // Financial Module Seeders
        $this->command->info('Seeding Financial module data...');
        $this->call([
            CostCategorySeeder::class,
            RevenueCategorySeeder::class,
        ]);

        // Metrics Module Seeders
        $this->command->info('Seeding Metrics module data...');
        $this->call([
            MetricDefinitionSeeder::class,
            DashboardSeeder::class,
        ]);

        $this->command->info('Phase 1 seeding completed successfully!');
    }
}
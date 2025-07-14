<?php

namespace Database\Seeders\Metrics;

use App\Models\Metrics\Dashboard;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dashboards = [
            [
                'name' => 'Executive Dashboard',
                'slug' => 'executive-dashboard',
                'description' => 'High-level KPIs and performance metrics for executives',
                'category' => 'executive',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Operational Dashboard',
                'slug' => 'operational-dashboard',
                'description' => 'Daily operational metrics for warehouse managers',
                'category' => 'operational',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Financial Dashboard',
                'slug' => 'financial-dashboard',
                'description' => 'Financial performance and cost analysis',
                'category' => 'financial',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Inbound Operations',
                'slug' => 'inbound-operations',
                'description' => 'Receiving and putaway performance metrics',
                'category' => 'operational',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Outbound Operations',
                'slug' => 'outbound-operations',
                'description' => 'Picking, packing, and shipping performance metrics',
                'category' => 'operational',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Inventory Management',
                'slug' => 'inventory-management',
                'description' => 'Inventory accuracy, turnover, and optimization metrics',
                'category' => 'operational',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($dashboards as $dashboard) {
            Dashboard::create($dashboard);
        }
    }
}
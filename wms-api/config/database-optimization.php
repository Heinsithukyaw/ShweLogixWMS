<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for optimizing database
    | performance in the ShweLogixWMS application.
    |
    */

    'query_optimization' => [
        /*
        |--------------------------------------------------------------------------
        | Query Caching
        |--------------------------------------------------------------------------
        |
        | Enable query result caching for frequently accessed data
        |
        */
        'cache_enabled' => env('DB_CACHE_ENABLED', true),
        'cache_ttl' => env('DB_CACHE_TTL', 3600), // 1 hour
        'cache_prefix' => env('DB_CACHE_PREFIX', 'wms_query_'),

        /*
        |--------------------------------------------------------------------------
        | Eager Loading Configuration
        |--------------------------------------------------------------------------
        |
        | Define default relationships to eager load for common queries
        |
        */
        'default_eager_loads' => [
            'sales_orders' => ['customer', 'warehouse', 'items.product'],
            'shipments' => ['carrier', 'warehouse', 'salesOrders.customer'],
            'pack_orders' => ['salesOrder.customer', 'packingStation', 'packer'],
            'load_plans' => ['vehicle', 'driver', 'warehouse', 'shipments'],
            'batch_picks' => ['warehouse', 'assignedPicker', 'orders'],
            'zone_picks' => ['warehouse', 'zone', 'pickers'],
            'cluster_picks' => ['warehouse', 'assignedPicker', 'orders']
        ],

        /*
        |--------------------------------------------------------------------------
        | Pagination Configuration
        |--------------------------------------------------------------------------
        |
        | Optimize pagination for large datasets
        |
        */
        'default_per_page' => 15,
        'max_per_page' => 100,
        'use_cursor_pagination' => true, // For better performance on large datasets
    ],

    'connection_optimization' => [
        /*
        |--------------------------------------------------------------------------
        | Connection Pool Settings
        |--------------------------------------------------------------------------
        |
        | Optimize database connection pooling
        |
        */
        'pool_size' => env('DB_POOL_SIZE', 10),
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
        'connection_timeout' => env('DB_CONNECTION_TIMEOUT', 30),
        'idle_timeout' => env('DB_IDLE_TIMEOUT', 300),

        /*
        |--------------------------------------------------------------------------
        | Read/Write Splitting
        |--------------------------------------------------------------------------
        |
        | Configure read/write database splitting for better performance
        |
        */
        'read_write_splitting' => env('DB_READ_WRITE_SPLITTING', false),
        'read_connections' => [
            'host' => env('DB_READ_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_READ_PORT', env('DB_PORT', '3306')),
        ],
    ],

    'indexing_strategy' => [
        /*
        |--------------------------------------------------------------------------
        | Critical Indexes
        |--------------------------------------------------------------------------
        |
        | Define critical indexes for optimal query performance
        |
        */
        'critical_indexes' => [
            // Sales Orders
            'sales_orders_performance' => [
                'table' => 'sales_orders',
                'columns' => ['warehouse_id', 'status', 'created_at'],
                'type' => 'composite'
            ],
            
            // Inventory Lookups
            'inventory_availability' => [
                'table' => 'product_inventory',
                'columns' => ['warehouse_id', 'product_id', 'quantity_available'],
                'type' => 'composite'
            ],
            
            // Pick Operations
            'pick_location_optimization' => [
                'table' => 'sales_order_items',
                'columns' => ['location_id', 'status'],
                'type' => 'composite'
            ],
            
            // Shipment Tracking
            'shipment_tracking' => [
                'table' => 'shipments',
                'columns' => ['tracking_number'],
                'type' => 'unique'
            ],
            
            // Load Planning
            'load_plan_scheduling' => [
                'table' => 'load_plans',
                'columns' => ['planned_departure_time', 'status'],
                'type' => 'composite'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Index Maintenance
        |--------------------------------------------------------------------------
        |
        | Settings for index maintenance and optimization
        |
        */
        'auto_analyze' => env('DB_AUTO_ANALYZE', true),
        'analyze_threshold' => 0.1, // Analyze when 10% of data changes
        'rebuild_threshold' => 0.3, // Rebuild when 30% fragmentation
    ],

    'query_patterns' => [
        /*
        |--------------------------------------------------------------------------
        | Common Query Patterns
        |--------------------------------------------------------------------------
        |
        | Optimize common query patterns used throughout the application
        |
        */
        'outbound_dashboard' => [
            'cache_key' => 'outbound_dashboard_stats',
            'ttl' => 300, // 5 minutes
            'queries' => [
                'pending_orders',
                'active_picks',
                'ready_shipments',
                'vehicle_utilization'
            ]
        ],

        'inventory_availability' => [
            'cache_key' => 'inventory_availability',
            'ttl' => 60, // 1 minute
            'queries' => [
                'available_quantities',
                'low_stock_alerts',
                'allocation_status'
            ]
        ],

        'pick_optimization' => [
            'cache_key' => 'pick_optimization',
            'ttl' => 900, // 15 minutes
            'queries' => [
                'location_distances',
                'pick_sequences',
                'zone_capacities'
            ]
        ]
    ],

    'performance_monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Query Performance Monitoring
        |--------------------------------------------------------------------------
        |
        | Monitor and log slow queries for optimization
        |
        */
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'log_slow_queries' => env('DB_LOG_SLOW_QUERIES', true),
        'explain_slow_queries' => env('DB_EXPLAIN_SLOW_QUERIES', true),

        /*
        |--------------------------------------------------------------------------
        | Performance Metrics
        |--------------------------------------------------------------------------
        |
        | Track database performance metrics
        |
        */
        'track_metrics' => env('DB_TRACK_METRICS', true),
        'metrics_retention' => env('DB_METRICS_RETENTION', 30), // days
        'alert_thresholds' => [
            'connection_usage' => 80, // percent
            'query_time' => 2000, // milliseconds
            'deadlock_rate' => 5, // per hour
        ]
    ],

    'batch_operations' => [
        /*
        |--------------------------------------------------------------------------
        | Bulk Operations Optimization
        |--------------------------------------------------------------------------
        |
        | Optimize bulk insert/update operations
        |
        */
        'batch_size' => env('DB_BATCH_SIZE', 1000),
        'use_transactions' => true,
        'chunk_size' => env('DB_CHUNK_SIZE', 500),
        'memory_limit' => env('DB_MEMORY_LIMIT', '256M'),

        /*
        |--------------------------------------------------------------------------
        | Bulk Import Settings
        |--------------------------------------------------------------------------
        |
        | Settings for bulk data imports
        |
        */
        'import_batch_size' => 5000,
        'disable_foreign_keys' => true, // During bulk imports
        'disable_indexes' => false, // Keep indexes during imports
    ],

    'maintenance' => [
        /*
        |--------------------------------------------------------------------------
        | Database Maintenance Schedule
        |--------------------------------------------------------------------------
        |
        | Automated database maintenance tasks
        |
        */
        'auto_maintenance' => env('DB_AUTO_MAINTENANCE', true),
        'maintenance_window' => [
            'start' => '02:00',
            'end' => '04:00',
            'timezone' => 'UTC'
        ],

        'tasks' => [
            'analyze_tables' => [
                'enabled' => true,
                'frequency' => 'daily',
                'tables' => [
                    'sales_orders',
                    'product_inventory',
                    'shipments',
                    'pack_orders',
                    'load_plans'
                ]
            ],
            
            'optimize_tables' => [
                'enabled' => true,
                'frequency' => 'weekly',
                'tables' => [
                    'sales_order_items',
                    'batch_pick_items',
                    'pack_order_items'
                ]
            ],
            
            'cleanup_old_data' => [
                'enabled' => true,
                'frequency' => 'monthly',
                'retention_periods' => [
                    'completed_orders' => 365, // days
                    'shipped_orders' => 730, // days
                    'analytics_data' => 90, // days
                    'log_entries' => 30 // days
                ]
            ]
        ]
    ],

    'replication' => [
        /*
        |--------------------------------------------------------------------------
        | Database Replication Settings
        |--------------------------------------------------------------------------
        |
        | Configure database replication for high availability
        |
        */
        'enabled' => env('DB_REPLICATION_ENABLED', false),
        'lag_threshold' => env('DB_REPLICATION_LAG_THRESHOLD', 5), // seconds
        'failover_timeout' => env('DB_FAILOVER_TIMEOUT', 30), // seconds
        
        'read_replicas' => [
            'analytics' => [
                'host' => env('DB_ANALYTICS_HOST'),
                'weight' => 30 // percentage of read queries
            ],
            'reporting' => [
                'host' => env('DB_REPORTING_HOST'),
                'weight' => 20
            ]
        ]
    ],

    'partitioning' => [
        /*
        |--------------------------------------------------------------------------
        | Table Partitioning Strategy
        |--------------------------------------------------------------------------
        |
        | Configure table partitioning for large tables
        |
        */
        'enabled' => env('DB_PARTITIONING_ENABLED', false),
        
        'partition_tables' => [
            'sales_orders' => [
                'type' => 'range',
                'column' => 'created_at',
                'interval' => 'monthly'
            ],
            
            'shipments' => [
                'type' => 'range',
                'column' => 'ship_date',
                'interval' => 'monthly'
            ],
            
            'analytics_data' => [
                'type' => 'range',
                'column' => 'date',
                'interval' => 'daily'
            ]
        ]
    ]
];
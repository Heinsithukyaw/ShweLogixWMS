<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for all external system integrations
    | including ERP, E-Commerce, Marketplace, TMS, and other systems.
    |
    */

    'erp' => [
        'sap' => [
            'enabled' => env('SAP_INTEGRATION_ENABLED', false),
            'endpoint' => env('SAP_ENDPOINT'),
            'username' => env('SAP_USERNAME'),
            'password' => env('SAP_PASSWORD'),
            'client' => env('SAP_CLIENT', '100'),
            'language' => env('SAP_LANGUAGE', 'EN'),
            'timeout' => env('SAP_TIMEOUT', 30),
            'retry_attempts' => env('SAP_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('SAP_RETRY_DELAY', 1000),
        ],
        
        'oracle' => [
            'enabled' => env('ORACLE_INTEGRATION_ENABLED', false),
            'endpoint' => env('ORACLE_ENDPOINT'),
            'instance_url' => env('ORACLE_INSTANCE_URL'),
            'client_id' => env('ORACLE_CLIENT_ID'),
            'client_secret' => env('ORACLE_CLIENT_SECRET'),
            'scope' => env('ORACLE_SCOPE', 'https://erp.oracle.com/scm/inventory'),
            'timeout' => env('ORACLE_TIMEOUT', 30),
            'retry_attempts' => env('ORACLE_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('ORACLE_RETRY_DELAY', 1000),
        ],
        
        'dynamics' => [
            'enabled' => env('DYNAMICS_INTEGRATION_ENABLED', false),
            'endpoint' => env('DYNAMICS_ENDPOINT'),
            'tenant_id' => env('DYNAMICS_TENANT_ID'),
            'client_id' => env('DYNAMICS_CLIENT_ID'),
            'client_secret' => env('DYNAMICS_CLIENT_SECRET'),
            'resource' => env('DYNAMICS_RESOURCE', 'https://dynamics.microsoft.com/'),
            'timeout' => env('DYNAMICS_TIMEOUT', 30),
            'retry_attempts' => env('DYNAMICS_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('DYNAMICS_RETRY_DELAY', 1000),
        ],
    ],

    'ecommerce' => [
        'shopify' => [
            'enabled' => env('SHOPIFY_INTEGRATION_ENABLED', false),
            'shop_domain' => env('SHOPIFY_SHOP_DOMAIN'),
            'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
            'api_version' => env('SHOPIFY_API_VERSION', '2023-10'),
            'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
            'timeout' => env('SHOPIFY_TIMEOUT', 30),
            'retry_attempts' => env('SHOPIFY_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('SHOPIFY_RETRY_DELAY', 1000),
            'inventory_buffer_percentage' => env('SHOPIFY_INVENTORY_BUFFER', 5),
        ],
        
        'magento' => [
            'enabled' => env('MAGENTO_INTEGRATION_ENABLED', false),
            'base_url' => env('MAGENTO_BASE_URL'),
            'admin_token' => env('MAGENTO_ADMIN_TOKEN'),
            'consumer_key' => env('MAGENTO_CONSUMER_KEY'),
            'consumer_secret' => env('MAGENTO_CONSUMER_SECRET'),
            'access_token' => env('MAGENTO_ACCESS_TOKEN'),
            'access_token_secret' => env('MAGENTO_ACCESS_TOKEN_SECRET'),
            'timeout' => env('MAGENTO_TIMEOUT', 30),
            'retry_attempts' => env('MAGENTO_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('MAGENTO_RETRY_DELAY', 1000),
        ],
        
        'woocommerce' => [
            'enabled' => env('WOOCOMMERCE_INTEGRATION_ENABLED', false),
            'base_url' => env('WOOCOMMERCE_BASE_URL'),
            'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
            'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
            'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),
            'timeout' => env('WOOCOMMERCE_TIMEOUT', 30),
            'retry_attempts' => env('WOOCOMMERCE_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('WOOCOMMERCE_RETRY_DELAY', 1000),
        ],
    ],

    'marketplace' => [
        'amazon' => [
            'enabled' => env('AMAZON_INTEGRATION_ENABLED', false),
            'marketplace_id' => env('AMAZON_MARKETPLACE_ID'),
            'seller_id' => env('AMAZON_SELLER_ID'),
            'access_key' => env('AMAZON_ACCESS_KEY'),
            'secret_key' => env('AMAZON_SECRET_KEY'),
            'role_arn' => env('AMAZON_ROLE_ARN'),
            'client_id' => env('AMAZON_CLIENT_ID'),
            'client_secret' => env('AMAZON_CLIENT_SECRET'),
            'refresh_token' => env('AMAZON_REFRESH_TOKEN'),
            'region' => env('AMAZON_REGION', 'us-east-1'),
            'endpoint' => env('AMAZON_ENDPOINT', 'https://sellingpartnerapi-na.amazon.com'),
            'timeout' => env('AMAZON_TIMEOUT', 30),
            'retry_attempts' => env('AMAZON_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('AMAZON_RETRY_DELAY', 1000),
        ],
        
        'ebay' => [
            'enabled' => env('EBAY_INTEGRATION_ENABLED', false),
            'app_id' => env('EBAY_APP_ID'),
            'dev_id' => env('EBAY_DEV_ID'),
            'cert_id' => env('EBAY_CERT_ID'),
            'user_token' => env('EBAY_USER_TOKEN'),
            'site_id' => env('EBAY_SITE_ID', '0'), // US
            'environment' => env('EBAY_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('EBAY_TIMEOUT', 30),
            'retry_attempts' => env('EBAY_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('EBAY_RETRY_DELAY', 1000),
        ],
        
        'walmart' => [
            'enabled' => env('WALMART_INTEGRATION_ENABLED', false),
            'client_id' => env('WALMART_CLIENT_ID'),
            'client_secret' => env('WALMART_CLIENT_SECRET'),
            'channel_type' => env('WALMART_CHANNEL_TYPE'),
            'environment' => env('WALMART_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('WALMART_TIMEOUT', 30),
            'retry_attempts' => env('WALMART_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('WALMART_RETRY_DELAY', 1000),
        ],
    ],

    'tms' => [
        'fedex' => [
            'enabled' => env('FEDEX_INTEGRATION_ENABLED', false),
            'account_number' => env('FEDEX_ACCOUNT_NUMBER'),
            'meter_number' => env('FEDEX_METER_NUMBER'),
            'key' => env('FEDEX_KEY'),
            'password' => env('FEDEX_PASSWORD'),
            'environment' => env('FEDEX_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('FEDEX_TIMEOUT', 30),
        ],
        
        'ups' => [
            'enabled' => env('UPS_INTEGRATION_ENABLED', false),
            'username' => env('UPS_USERNAME'),
            'password' => env('UPS_PASSWORD'),
            'access_license_number' => env('UPS_ACCESS_LICENSE_NUMBER'),
            'shipper_number' => env('UPS_SHIPPER_NUMBER'),
            'environment' => env('UPS_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('UPS_TIMEOUT', 30),
        ],
        
        'dhl' => [
            'enabled' => env('DHL_INTEGRATION_ENABLED', false),
            'site_id' => env('DHL_SITE_ID'),
            'password' => env('DHL_PASSWORD'),
            'account_number' => env('DHL_ACCOUNT_NUMBER'),
            'environment' => env('DHL_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('DHL_TIMEOUT', 30),
        ],
    ],

    'supplier' => [
        'portal' => [
            'enabled' => env('SUPPLIER_PORTAL_ENABLED', true),
            'base_url' => env('SUPPLIER_PORTAL_URL', env('APP_URL') . '/supplier'),
            'registration_enabled' => env('SUPPLIER_REGISTRATION_ENABLED', true),
            'approval_required' => env('SUPPLIER_APPROVAL_REQUIRED', true),
            'document_upload_enabled' => env('SUPPLIER_DOCUMENT_UPLOAD_ENABLED', true),
            'max_file_size' => env('SUPPLIER_MAX_FILE_SIZE', 10240), // KB
            'allowed_file_types' => env('SUPPLIER_ALLOWED_FILE_TYPES', 'pdf,doc,docx,xls,xlsx,jpg,png'),
        ],
        
        'edi' => [
            'enabled' => env('SUPPLIER_EDI_ENABLED', false),
            'ftp_host' => env('SUPPLIER_EDI_FTP_HOST'),
            'ftp_username' => env('SUPPLIER_EDI_FTP_USERNAME'),
            'ftp_password' => env('SUPPLIER_EDI_FTP_PASSWORD'),
            'ftp_port' => env('SUPPLIER_EDI_FTP_PORT', 21),
            'ftp_passive' => env('SUPPLIER_EDI_FTP_PASSIVE', true),
            'processing_schedule' => env('SUPPLIER_EDI_SCHEDULE', '*/15 * * * *'), // Every 15 minutes
        ],
    ],

    'iot' => [
        'sensors' => [
            'enabled' => env('IOT_SENSORS_ENABLED', false),
            'mqtt_broker' => env('IOT_MQTT_BROKER'),
            'mqtt_port' => env('IOT_MQTT_PORT', 1883),
            'mqtt_username' => env('IOT_MQTT_USERNAME'),
            'mqtt_password' => env('IOT_MQTT_PASSWORD'),
            'mqtt_client_id' => env('IOT_MQTT_CLIENT_ID', 'shwelogix_wms'),
            'temperature_threshold_min' => env('IOT_TEMP_MIN', 2),
            'temperature_threshold_max' => env('IOT_TEMP_MAX', 8),
            'humidity_threshold_min' => env('IOT_HUMIDITY_MIN', 45),
            'humidity_threshold_max' => env('IOT_HUMIDITY_MAX', 65),
        ],
        
        'rfid' => [
            'enabled' => env('IOT_RFID_ENABLED', false),
            'reader_ip' => env('IOT_RFID_READER_IP'),
            'reader_port' => env('IOT_RFID_READER_PORT', 8080),
            'reader_username' => env('IOT_RFID_READER_USERNAME'),
            'reader_password' => env('IOT_RFID_READER_PASSWORD'),
            'tag_format' => env('IOT_RFID_TAG_FORMAT', 'EPC'),
            'read_power' => env('IOT_RFID_READ_POWER', 30),
        ],
    ],

    'financial' => [
        'quickbooks' => [
            'enabled' => env('QUICKBOOKS_INTEGRATION_ENABLED', false),
            'client_id' => env('QUICKBOOKS_CLIENT_ID'),
            'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
            'redirect_uri' => env('QUICKBOOKS_REDIRECT_URI'),
            'scope' => env('QUICKBOOKS_SCOPE', 'com.intuit.quickbooks.accounting'),
            'environment' => env('QUICKBOOKS_ENVIRONMENT', 'sandbox'), // sandbox or production
            'timeout' => env('QUICKBOOKS_TIMEOUT', 30),
        ],
        
        'xero' => [
            'enabled' => env('XERO_INTEGRATION_ENABLED', false),
            'client_id' => env('XERO_CLIENT_ID'),
            'client_secret' => env('XERO_CLIENT_SECRET'),
            'redirect_uri' => env('XERO_REDIRECT_URI'),
            'scope' => env('XERO_SCOPE', 'accounting.transactions'),
            'timeout' => env('XERO_TIMEOUT', 30),
        ],
        
        'stripe' => [
            'enabled' => env('STRIPE_INTEGRATION_ENABLED', false),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'timeout' => env('STRIPE_TIMEOUT', 30),
        ],
    ],

    'crm' => [
        'salesforce' => [
            'enabled' => env('SALESFORCE_INTEGRATION_ENABLED', false),
            'client_id' => env('SALESFORCE_CLIENT_ID'),
            'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
            'username' => env('SALESFORCE_USERNAME'),
            'password' => env('SALESFORCE_PASSWORD'),
            'security_token' => env('SALESFORCE_SECURITY_TOKEN'),
            'environment' => env('SALESFORCE_ENVIRONMENT', 'sandbox'), // sandbox or production
            'api_version' => env('SALESFORCE_API_VERSION', 'v58.0'),
            'timeout' => env('SALESFORCE_TIMEOUT', 30),
        ],
        
        'hubspot' => [
            'enabled' => env('HUBSPOT_INTEGRATION_ENABLED', false),
            'api_key' => env('HUBSPOT_API_KEY'),
            'client_id' => env('HUBSPOT_CLIENT_ID'),
            'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
            'redirect_uri' => env('HUBSPOT_REDIRECT_URI'),
            'scope' => env('HUBSPOT_SCOPE', 'contacts'),
            'timeout' => env('HUBSPOT_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Integration Settings
    |--------------------------------------------------------------------------
    */
    
    'global' => [
        'default_timeout' => env('INTEGRATION_DEFAULT_TIMEOUT', 30),
        'default_retry_attempts' => env('INTEGRATION_DEFAULT_RETRY_ATTEMPTS', 3),
        'default_retry_delay' => env('INTEGRATION_DEFAULT_RETRY_DELAY', 1000),
        'log_requests' => env('INTEGRATION_LOG_REQUESTS', true),
        'log_responses' => env('INTEGRATION_LOG_RESPONSES', true),
        'cache_ttl' => env('INTEGRATION_CACHE_TTL', 3600),
        'rate_limit_enabled' => env('INTEGRATION_RATE_LIMIT_ENABLED', true),
        'rate_limit_requests' => env('INTEGRATION_RATE_LIMIT_REQUESTS', 100),
        'rate_limit_window' => env('INTEGRATION_RATE_LIMIT_WINDOW', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Synchronization Settings
    |--------------------------------------------------------------------------
    */
    
    'sync' => [
        'batch_size' => env('INTEGRATION_SYNC_BATCH_SIZE', 100),
        'max_execution_time' => env('INTEGRATION_SYNC_MAX_TIME', 300), // seconds
        'error_threshold' => env('INTEGRATION_SYNC_ERROR_THRESHOLD', 10), // percentage
        'retry_failed_after' => env('INTEGRATION_SYNC_RETRY_AFTER', 3600), // seconds
        'cleanup_logs_after' => env('INTEGRATION_CLEANUP_LOGS_AFTER', 30), // days
        'enable_real_time' => env('INTEGRATION_REAL_TIME_ENABLED', true),
        'enable_batch_sync' => env('INTEGRATION_BATCH_SYNC_ENABLED', true),
        'batch_sync_schedule' => env('INTEGRATION_BATCH_SYNC_SCHEDULE', '0 */6 * * *'), // Every 6 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    
    'webhooks' => [
        'enabled' => env('INTEGRATION_WEBHOOKS_ENABLED', true),
        'verify_signatures' => env('INTEGRATION_VERIFY_SIGNATURES', true),
        'max_payload_size' => env('INTEGRATION_MAX_PAYLOAD_SIZE', 1048576), // 1MB
        'timeout' => env('INTEGRATION_WEBHOOK_TIMEOUT', 10),
        'retry_attempts' => env('INTEGRATION_WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('INTEGRATION_WEBHOOK_RETRY_DELAY', 1000),
        'queue_webhooks' => env('INTEGRATION_QUEUE_WEBHOOKS', true),
        'webhook_queue' => env('INTEGRATION_WEBHOOK_QUEUE', 'webhooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'encrypt_credentials' => env('INTEGRATION_ENCRYPT_CREDENTIALS', true),
        'rotate_tokens' => env('INTEGRATION_ROTATE_TOKENS', true),
        'token_rotation_interval' => env('INTEGRATION_TOKEN_ROTATION_INTERVAL', 86400), // 24 hours
        'ip_whitelist' => env('INTEGRATION_IP_WHITELIST', ''),
        'user_agent' => env('INTEGRATION_USER_AGENT', 'ShweLogixWMS/1.0'),
        'ssl_verify' => env('INTEGRATION_SSL_VERIFY', true),
        'audit_enabled' => env('INTEGRATION_AUDIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    
    'monitoring' => [
        'enabled' => env('INTEGRATION_MONITORING_ENABLED', true),
        'health_check_interval' => env('INTEGRATION_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
        'performance_threshold' => env('INTEGRATION_PERFORMANCE_THRESHOLD', 5000), // milliseconds
        'error_rate_threshold' => env('INTEGRATION_ERROR_RATE_THRESHOLD', 5), // percentage
        'alert_channels' => env('INTEGRATION_ALERT_CHANNELS', 'email,slack'),
        'alert_recipients' => env('INTEGRATION_ALERT_RECIPIENTS', ''),
        'dashboard_enabled' => env('INTEGRATION_DASHBOARD_ENABLED', true),
        'metrics_retention' => env('INTEGRATION_METRICS_RETENTION', 90), // days
    ],
];
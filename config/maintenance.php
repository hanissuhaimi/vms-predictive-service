<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maintenance System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration values for the vehicle maintenance
    | prediction system, including reference data and business rules.
    |
    */

    // System Settings
    'system' => [
        'name' => 'Vehicle Maintenance Prediction System',
        'version' => '2.0.0',
        'timezone' => 'Asia/Kuala_Lumpur',
        'default_language' => 'english',
        'supported_languages' => ['english', 'malay'],
        'items_per_page' => 20,
        'max_export_records' => 1000,
    ],

    // ML Prediction Settings
    'prediction' => [
        'use_ml_service' => env('VMS_USE_AI', true),
        'python_executable' => env('PYTHON_EXECUTABLE', 'python'),
        'python_api_url' => env('PYTHON_API_URL', 'http://localhost:8000'),
        'ml_timeout_seconds' => 30,
        'fallback_to_rules' => true,
        'confidence_threshold' => 0.7,
    ],

    // Validation Rules
    'validation' => [
        'max_mileage' => 10000000,
        'min_mileage' => 0,
        'max_daily_mileage_increase' => 1000,
        'vehicle_number_regex' => '/^[A-Za-z0-9]+$/',
        'max_vehicle_number_length' => 20,
    ],

    // Business Rules
    'business_rules' => [
        'high_maintenance_threshold' => 8, // services per month
        'maintenance_overdue_days' => 90,
        'high_mileage_threshold' => 500000,
        'response_time_limits' => [
            'high_priority' => 1, // days
            'medium_priority' => 2,
            'low_priority' => 3,
        ],
    ],

    // File Upload Settings
    'uploads' => [
        'max_file_size' => '10M',
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'storage_disk' => 'local',
        'storage_path' => 'maintenance',
    ],

    // Export Settings
    'export' => [
        'formats' => ['json', 'excel', 'pdf'],
        'default_format' => 'json',
        'excel_chunk_size' => 1000,
        'pdf_page_size' => 'A4',
        'include_images' => false,
    ],

    // Cache Settings
    'cache' => [
        'enabled' => true,
        'ttl' => [
            'vehicle_stats' => 3600, // 1 hour
            'depot_info' => 86400, // 24 hours
            'reference_data' => 604800, // 7 days
            'predictions' => 1800, // 30 minutes
        ],
    ],
];
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maintenance Intervals (in kilometers)
    |--------------------------------------------------------------------------
    |
    | Standard maintenance intervals for different vehicle parts and services
    |
    */

    'intervals' => [
        'Engine Oil & Filter' => [
            'km' => 10000,
            'months' => 6,
            'priority' => 1,
            'keywords' => ['minyak enjin', 'engine oil', 'oil change', 'filter minyak', 'oil filter'],
            'warning_threshold' => 0.9, // Warning at 90% of interval
        ],
        'Air Filter' => [
            'km' => 15000,
            'months' => 12,
            'priority' => 2,
            'keywords' => ['air filter', 'filter udara', 'filter angin'],
            'warning_threshold' => 0.9,
        ],
        'Brake System' => [
            'km' => 20000,
            'months' => 12,
            'priority' => 1,
            'keywords' => ['brake', 'brek', 'brake pad', 'brake fluid', 'minyak brek'],
            'warning_threshold' => 0.8,
        ],
        'Tires & Wheels' => [
            'km' => 25000,
            'months' => 18,
            'priority' => 1,
            'keywords' => ['tayar', 'tire', 'tyre', 'tukar tayar', 'wheel'],
            'warning_threshold' => 0.85,
        ],
        'Transmission Service' => [
            'km' => 30000,
            'months' => 24,
            'priority' => 2,
            'keywords' => ['gearbox', 'transmission', 'gear oil', 'transmisi'],
            'warning_threshold' => 0.9,
        ],
        'Coolant System' => [
            'km' => 40000,
            'months' => 24,
            'priority' => 2,
            'keywords' => ['coolant', 'air radiator', 'cooling system', 'radiator'],
            'warning_threshold' => 0.9,
        ],
        'Major Service' => [
            'km' => 50000,
            'months' => 36,
            'priority' => 1,
            'keywords' => ['major service', 'overhaul', 'comprehensive check'],
            'warning_threshold' => 0.9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vehicle Type Modifiers
    |--------------------------------------------------------------------------
    |
    | Different vehicle types may have different maintenance intervals
    |
    */

    'vehicle_type_modifiers' => [
        'light_commercial' => 1.0,
        'heavy_commercial' => 0.8, // More frequent maintenance
        'courier' => 0.7, // High usage, more frequent maintenance
        'cargo' => 0.9,
        'passenger' => 1.1, // Less frequent maintenance
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Pattern Modifiers
    |--------------------------------------------------------------------------
    |
    | Adjust intervals based on vehicle usage patterns
    |
    */

    'usage_modifiers' => [
        'light' => 1.2, // Extend intervals
        'regular' => 1.0, // Standard intervals
        'commercial' => 0.9, // Slightly more frequent
        'heavy_commercial' => 0.8, // More frequent maintenance
    ],

    /*
    |--------------------------------------------------------------------------
    | Seasonal Adjustments
    |--------------------------------------------------------------------------
    |
    | Some services may need seasonal adjustments
    |
    */

    'seasonal' => [
        'air_conditioning' => [
            'months' => [3, 4, 5, 9, 10], // Before hot and after rainy seasons
            'description' => 'Pre-season AC check recommended'
        ],
        'brake_check' => [
            'months' => [11, 12, 1], // Rainy season
            'description' => 'Extra brake inspection during rainy season'
        ],
    ],
];
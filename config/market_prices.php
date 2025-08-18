<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Malaysian Market Prices for Vehicle Parts & Services
    |--------------------------------------------------------------------------
    |
    | These prices are estimates based on Malaysian market rates
    | for commercial vehicle maintenance.
    |
    */

    'parts_services' => [
        'Engine Oil & Filter' => [
            'min' => 80,
            'max' => 150,
            'unit' => 'RM',
            'time_min' => 30,
            'time_max' => 60,
            'category' => 'engine',
            'critical' => true,
            'description' => 'Engine oil change and filter replacement',
        ],
        'Air Filter' => [
            'min' => 30,
            'max' => 60,
            'unit' => 'RM',
            'time_min' => 15,
            'time_max' => 30,
            'category' => 'engine',
            'critical' => false,
            'description' => 'Air filter replacement',
        ],
        'Brake System' => [
            'min' => 150,
            'max' => 400,
            'unit' => 'RM',
            'time_min' => 60,
            'time_max' => 180,
            'category' => 'brakes',
            'critical' => true,
            'description' => 'Brake pads, fluid, and system service',
        ],
        'Brake Adjustment' => [
            'min' => 50,
            'max' => 120,
            'unit' => 'RM',
            'time_min' => 30,
            'time_max' => 60,
            'category' => 'brakes',
            'critical' => true,
            'description' => 'Brake adjustment and inspection',
        ],
        'Tires & Wheels' => [
            'min' => 180,
            'max' => 350,
            'unit' => 'RM',
            'time_min' => 45,
            'time_max' => 90,
            'category' => 'tires',
            'critical' => true,
            'description' => 'Tire replacement and wheel service',
        ],
        'Tire Repair' => [
            'min' => 30,
            'max' => 80,
            'unit' => 'RM',
            'time_min' => 15,
            'time_max' => 45,
            'category' => 'tires',
            'critical' => false,
            'description' => 'Tire puncture repair and patching',
        ],
        'Electrical & Lighting' => [
            'min' => 60,
            'max' => 200,
            'unit' => 'RM',
            'time_min' => 30,
            'time_max' => 120,
            'category' => 'electrical',
            'critical' => false,
            'description' => 'Electrical system and lighting repairs',
        ],
        'Air System' => [
            'min' => 100,
            'max' => 250,
            'unit' => 'RM',
            'time_min' => 45,
            'time_max' => 120,
            'category' => 'air',
            'critical' => false,
            'description' => 'Air conditioning and ventilation service',
        ],
        'Cooling System' => [
            'min' => 120,
            'max' => 300,
            'unit' => 'RM',
            'time_min' => 60,
            'time_max' => 150,
            'category' => 'cooling',
            'critical' => true,
            'description' => 'Radiator, coolant, and cooling system service',
        ],
        'Suspension & Absorber' => [
            'min' => 150,
            'max' => 400,
            'unit' => 'RM',
            'time_min' => 90,
            'time_max' => 180,
            'category' => 'suspension',
            'critical' => false,
            'description' => 'Suspension system and shock absorber service',
        ],
        'Gearbox Service' => [
            'min' => 200,
            'max' => 500,
            'unit' => 'RM',
            'time_min' => 120,
            'time_max' => 240,
            'category' => 'transmission',
            'critical' => false,
            'description' => 'Transmission and gearbox maintenance',
        ],
        'General Inspection' => [
            'min' => 50,
            'max' => 150,
            'unit' => 'RM',
            'time_min' => 30,
            'time_max' => 90,
            'category' => 'general',
            'critical' => false,
            'description' => 'General vehicle inspection and checkup',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Labor Rates
    |--------------------------------------------------------------------------
    */
    'labor_rates' => [
        'standard' => 50, // RM per hour
        'specialist' => 75, // RM per hour
        'emergency' => 100, // RM per hour
        'overtime' => 1.5, // multiplier
        'weekend' => 1.25, // multiplier
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'engine' => ['name' => 'Engine', 'color' => 'danger', 'icon' => 'fas fa-cog'],
        'brakes' => ['name' => 'Brakes', 'color' => 'warning', 'icon' => 'fas fa-stop-circle'],
        'tires' => ['name' => 'Tires', 'color' => 'info', 'icon' => 'fas fa-circle'],
        'electrical' => ['name' => 'Electrical', 'color' => 'primary', 'icon' => 'fas fa-bolt'],
        'air' => ['name' => 'Air System', 'color' => 'success', 'icon' => 'fas fa-wind'],
        'cooling' => ['name' => 'Cooling', 'color' => 'info', 'icon' => 'fas fa-thermometer'],
        'suspension' => ['name' => 'Suspension', 'color' => 'secondary', 'icon' => 'fas fa-car'],
        'transmission' => ['name' => 'Transmission', 'color' => 'warning', 'icon' => 'fas fa-cogs'],
        'general' => ['name' => 'General', 'color' => 'light', 'icon' => 'fas fa-tools'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Information
    |--------------------------------------------------------------------------
    */
    'last_updated' => '2024-12-19',
    'source' => 'Malaysian automotive service market research',
    'currency' => 'MYR',
    'notes' => 'Prices are estimates and may vary based on location, vehicle size, and service provider.',
];
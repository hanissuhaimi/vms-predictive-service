<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MR Types (Maintenance Request Types)
    |--------------------------------------------------------------------------
    |
    | Based on your MRType.txt file
    |
    */
    'mr_types' => [
        1 => [
            'id' => 1,
            'malay' => 'Senggaraan',
            'english' => 'Maintenance',
            'description' => 'General vehicle maintenance and repairs',
            'color' => 'primary',
            'icon' => 'fas fa-tools',
            'is_maintenance' => true,
        ],
        2 => [
            'id' => 2,
            'malay' => 'Cuci',
            'english' => 'Cleaning/Washing',
            'description' => 'Vehicle cleaning and washing services',
            'color' => 'info',
            'icon' => 'fas fa-tint',
            'is_maintenance' => false,
        ],
        3 => [
            'id' => 3,
            'malay' => 'Tayar',
            'english' => 'Tires',
            'description' => 'Tire-related services and repairs',
            'color' => 'warning',
            'icon' => 'fas fa-circle',
            'is_maintenance' => true,
        ],
        4 => [
            'id' => 4,
            'malay' => 'Rental',
            'english' => 'Rental',
            'description' => 'Vehicle rental services',
            'color' => 'secondary',
            'icon' => 'fas fa-key',
            'is_maintenance' => false,
        ],
        5 => [
            'id' => 5,
            'malay' => 'Operation',
            'english' => 'Operation',
            'description' => 'Operational services and tasks',
            'color' => 'success',
            'icon' => 'fas fa-cogs',
            'is_maintenance' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Codes
    |--------------------------------------------------------------------------
    |
    | Based on your ServiceRequest_Status.prn file
    |
    */
    'status_codes' => [
        0 => [
            'code' => 0,
            'name' => 'New',
            'description' => 'New service request created',
            'color' => 'warning',
            'icon' => 'fas fa-plus-circle',
            'is_active' => true,
            'is_closed' => false,
        ],
        1 => [
            'code' => 1,
            'name' => 'Approved by KB',
            'description' => 'Service request approved by Kepala Bengkel',
            'color' => 'info',
            'icon' => 'fas fa-check-circle',
            'is_active' => true,
            'is_closed' => false,
        ],
        2 => [
            'code' => 2,
            'name' => 'MO Created',
            'description' => 'Maintenance Order has been created',
            'color' => 'primary',
            'icon' => 'fas fa-file-alt',
            'is_active' => true,
            'is_closed' => false,
        ],
        3 => [
            'code' => 3,
            'name' => 'Closed',
            'description' => 'Service request completed and closed',
            'color' => 'success',
            'icon' => 'fas fa-check',
            'is_active' => false,
            'is_closed' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    |
    | Based on your Priority.txt file
    |
    */
    'priority_levels' => [
        1 => [
            'level' => 1,
            'name' => 'High',
            'description' => 'High priority - requires immediate attention',
            'color' => 'danger',
            'icon' => 'fas fa-exclamation-triangle',
            'response_days' => 1,
            'urgency_score' => 100,
        ],
        2 => [
            'level' => 2,
            'name' => 'Medium',
            'description' => 'Medium priority - standard response time',
            'color' => 'warning',
            'icon' => 'fas fa-clock',
            'response_days' => 2,
            'urgency_score' => 50,
        ],
        3 => [
            'level' => 3,
            'name' => 'Low',
            'description' => 'Low priority - routine maintenance',
            'color' => 'success',
            'icon' => 'fas fa-check-circle',
            'response_days' => 3,
            'urgency_score' => 25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tire Reasons
    |--------------------------------------------------------------------------
    |
    | Based on your MRType_Reason.txt file (for MR Type 3 - Tires)
    |
    */
    'tire_reasons' => [
        1 => 'BAHAGIAN DALAM PLY ROSAK',
        2 => 'BOTAK RATA',
        3 => 'BOTAK TENGAH',
        4 => 'BUNGA TERKIKIS',
        5 => 'CELUPAN TERKOPAK',
        6 => 'LAIN-LAIN',
        7 => 'MAKAN SEBELAH',
        8 => 'MAKAN TAK RATA',
        9 => 'MELETUP',
        10 => 'MELETUP DAN TERKOPAK',
        11 => 'MENGELEMBUNG BAHAGIAN TENGAH',
        12 => 'MENGELEMBUNG BAHAGIAN TEPI',
        13 => 'MEREKAH BAHAGIAN TENGAH',
        14 => 'MEREKAH BAHAGIAN TEPI',
        15 => 'NAMPAK "STEEL BELT"',
        16 => 'PECAH BAHAGIAN "SIDE WALL"',
        17 => 'TAMPAL TAYAR',
        18 => 'TAYAR PANCIT',
        19 => 'TERCUCUK BENDA TAJAM',
        20 => 'TUKAR TAYAR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Vehicle Status
    |--------------------------------------------------------------------------
    |
    | Vehicle status codes used in Vehicle_profile table
    |
    */
    'vehicle_status' => [
        1 => [
            'status' => 1,
            'name' => 'Active',
            'description' => 'Vehicle is active and operational',
            'color' => 'success',
            'icon' => 'fas fa-check-circle',
        ],
        2 => [
            'status' => 2,
            'name' => 'Inactive',
            'description' => 'Vehicle is temporarily inactive',
            'color' => 'secondary',
            'icon' => 'fas fa-pause-circle',
        ],
        3 => [
            'status' => 3,
            'name' => 'Under Maintenance',
            'description' => 'Vehicle is currently under maintenance',
            'color' => 'warning',
            'icon' => 'fas fa-wrench',
        ],
        9 => [
            'status' => 9,
            'name' => 'Decommissioned',
            'description' => 'Vehicle has been decommissioned',
            'color' => 'danger',
            'icon' => 'fas fa-times-circle',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Roles
    |--------------------------------------------------------------------------
    |
    | User role codes used in Users table
    |
    */
    'user_roles' => [
        1 => [
            'code' => 1,
            'name' => 'Administrator',
            'permissions' => ['create', 'read', 'update', 'delete', 'approve', 'admin'],
            'color' => 'danger',
            'icon' => 'fas fa-user-shield',
        ],
        2 => [
            'code' => 2,
            'name' => 'Manager',
            'permissions' => ['create', 'read', 'update', 'approve'],
            'color' => 'primary',
            'icon' => 'fas fa-user-tie',
        ],
        3 => [
            'code' => 3,
            'name' => 'Supervisor',
            'permissions' => ['create', 'read', 'update'],
            'color' => 'info',
            'icon' => 'fas fa-user-check',
        ],
        4 => [
            'code' => 4,
            'name' => 'Technician',
            'permissions' => ['read', 'update'],
            'color' => 'warning',
            'icon' => 'fas fa-user-cog',
        ],
        5 => [
            'code' => 5,
            'name' => 'Driver',
            'permissions' => ['read'],
            'color' => 'success',
            'icon' => 'fas fa-user',
        ],
        6 => [
            'code' => 6,
            'name' => 'Clerk',
            'permissions' => ['create', 'read'],
            'color' => 'secondary',
            'icon' => 'fas fa-user-edit',
        ],
    ],
];
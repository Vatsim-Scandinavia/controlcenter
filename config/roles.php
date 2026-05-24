<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    |
    | Define all available roles here instead of in the database.
    | The string key is the identifier used in the system.
    |
    */
    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'System-wide administrator',
            'scope' => 'global', // 'global', 'area', or 'both'
        ],
        'moderator' => [
            'name' => 'Moderator',
            'description' => 'Area moderator',
            'scope' => 'both',
        ],
        'mentor' => [
            'name' => 'Mentor',
            'description' => 'Training mentor',
            'scope' => 'area',
        ],
        'buddy' => [
            'name' => 'Buddy',
            'description' => 'Training buddy',
            'scope' => 'area',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Matrix
    |--------------------------------------------------------------------------
    |
    | Map specific granular permissions to the assigned roles.
    |
    */
    'matrix' => [
        // Training permissions
        'view-training' => ['admin', 'moderator', 'mentor', 'buddy'],
        'create-training' => ['admin', 'moderator', 'mentor'],
        'update-training' => ['admin', 'moderator'],
        'delete-training' => ['admin'],

        // Area management
        'manage-area' => ['admin'],

        // System
        'view-system-health' => ['admin'],

        // Users & Access
        'manage-users' => ['admin', 'moderator'],
        'view-user-access' => ['admin', 'moderator'],

        // Infrastructure
        'manage-positions' => ['admin', 'moderator'],
        'manage-endorsements' => ['admin', 'moderator'],
        'manage-visiting-endorsements' => ['admin'],
        'manage-examiner-endorsements' => ['admin'],

        // Reports
        'view-management-reports' => ['admin', 'moderator'],
        'view-training-statistics' => ['admin', 'moderator'],
        'view-training-reports' => ['admin', 'moderator'],
        'view-training-activities' => ['admin', 'moderator'],
        'view-training-statistics' => ['admin', 'moderator'],
        'view-training-activities' => ['admin', 'moderator'],
        'view-mentor-reports' => ['admin', 'moderator', 'mentor'],

        // Bookings
        'bypass-booking-restrictions' => ['admin', 'moderator', 'mentor'],
    ],
];

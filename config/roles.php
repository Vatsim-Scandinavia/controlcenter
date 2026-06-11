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
            'description' => 'System-wide administrator, assignable only via the user:makeadmin CLI command',
            'scope' => 'global',
        ],
        'director' => [
            'name' => 'Director',
            'description' => 'Director of an area or the whole organisation',
            'scope' => 'both',
        ],
        'moderator' => [
            'name' => 'Moderator',
            'description' => 'Area moderator',
            'scope' => 'both',
        ],
        'nav-editor' => [
            'name' => 'Navigational Editor',
            'description' => 'Editor of navigational and operationally relevant sector data',
            'scope' => 'area',
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
        'view-training' => ['admin', 'director', 'moderator', 'mentor', 'buddy'],
        'create-training' => ['admin', 'director', 'moderator', 'mentor'],
        'update-training' => ['admin', 'director', 'moderator'],
        'delete-training' => ['admin', 'director'],

        // Area management
        'manage-area' => ['admin'],

        // System
        'view-system-health' => ['admin'],

        // Users & Access
        'manage-users' => ['admin', 'director', 'moderator'],
        'view-user-access' => ['admin', 'director', 'moderator'],

        // Operations
        'manage-positions' => ['admin', 'director', 'moderator', 'nav-editor'],

        // Endorsements
        'manage-endorsements' => ['admin', 'director', 'moderator'],
        'manage-visiting-endorsements' => ['admin', 'director'],
        'manage-examiner-endorsements' => ['admin', 'director'],

        // Reports
        'view-management-reports' => ['admin', 'director', 'moderator'],
        'view-training-activities' => ['admin', 'director', 'moderator'],
        'view-training-statistics' => ['admin', 'director', 'moderator'],
        'view-mentor-reports' => ['admin', 'director', 'moderator', 'mentor'],

        // Feedback
        'view-correlated-feedback' => ['admin', 'director', 'moderator'],
        'view-uncorrelated-feedback' => ['admin', 'director', 'moderator'],

        // Bookings
        'bypass-booking-restrictions' => ['admin', 'director', 'moderator', 'mentor'],

        // Alerts
        'receive-inactivity-alerts' => ['admin', 'director', 'moderator'],

        // Workmail
        'use-workmail' => ['admin', 'director', 'moderator'],
    ],
];

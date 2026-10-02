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
    | Each role has a `scope` (where it may be HELD: global|area|both) and an
    | optional `grant_scope` (where grant-authority must be HELD to grant/revoke
    | it: 'area' [default] accepts area-or-global authority, 'global' requires an
    | area-less assignment even for an area-scoped grant).
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
            'grant_scope' => 'global', // only global directors may grant the director role
        ],
        'moderator' => [
            'name' => 'Moderator',
            'description' => 'Area moderator',
            'scope' => 'both',
        ],
        'training-staff' => [
            'name' => 'Training Staff',
            'description' => 'Training staff, e.g. leads, coordinators, with access to all training-related data',
            'scope' => 'area',
        ],
        'staff' => [
            'name' => 'Staff',
            'description' => 'Generic staff member with view-only access to positions',
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
    | Permission Catalogue
    |--------------------------------------------------------------------------
    |
    | The flat, authoritative list of every concrete permission. This drives
    | gate registration and the expansion of wildcard patterns in the matrix.
    | A permission must appear here to exist.
    |
    */
    'permissions' => [
        // Training
        'training.view',
        'training.create',
        'training.update',
        'training.delete',
        'training.mentor',
        'training.mentor-dashboard.view',
        'training.ratings.manage',
        'training.reports.view',
        'training.reports.create',
        'training.reports.update',
        'training.reports.delete',
        'training.reports.one-time-link',
        'training.attachments.view-hidden',
        'training.activities.view',
        'training.statistics.view',
        'training.notifications.receive',

        // Examinations
        'examinations.manage',
        'examinations.create',

        // Endorsements
        'endorsements.solo.manage',
        'endorsements.solo.delete',
        'endorsements.visiting.manage',
        'endorsements.visiting.delete',
        'endorsements.examiner.manage',
        'endorsements.examiner.delete',

        // FIR operations
        'fir.positions.view',
        'fir.positions.manage',
        'fir.management.reports.view',

        // Users
        'users.manage',
        'users.access.view',
        'users.email.view',
        'users.workmail.use',

        // Tasks
        'tasks.manage',
        'tasks.suggested-recipient',

        // Files
        'files.manage',
        'files.upload',

        // Feedback
        'feedback.correlated.view',
        'feedback.uncorrelated.view',
        'feedback.update',

        // Bookings
        'bookings.bypass-restrictions',
        'bookings.manage',
        'bookings.sweatbox.use',
        'bookings.sweatbox.manage',

        // Notifications
        'notifications.inactivity.receive',
        'notifications.templates.manage',

        // System
        'system.health.view',
        'system.settings.manage',
        'system.votes.manage',
        'system.activity-log.view',

        // Role management — one grant-authority permission per grantable role.
        // Governs both granting and revoking that role (both go through UserPolicy::updateRole).
        'roles.director.manage',
        'roles.moderator.manage',
        'roles.training-staff.manage',
        'roles.staff.manage',
        'roles.nav-editor.manage',
        'roles.mentor.manage',
        'roles.buddy.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Matrix
    |--------------------------------------------------------------------------
    |
    | Maps each role to the permission patterns it grants. Patterns support
    | dot-wildcards: '*' matches exactly one segment, '**' matches one or more,
    | and a leading '!' negates (deny always wins over allow).
    |
    */
    'matrix' => [
        'admin' => [
            '**',
            '!training.reports.one-time-link',
            '!training.attachments.view-hidden',
        ],
        'director' => [
            '**',
            '!system.**',
            '!examinations.create',
            '!training.reports.one-time-link',
            '!training.attachments.view-hidden',
            'roles.*.manage',
        ],
        'moderator' => [
            'training.**',
            '!training.delete',
            '!training.ratings.manage',
            '!training.reports.one-time-link',
            '!training.attachments.view-hidden',
            'examinations.manage',
            'endorsements.solo.*',
            'fir.positions.manage',
            'fir.management.reports.view',
            'bookings.**',
            'feedback.**',
            'files.**',
            'notifications.**',
            'tasks.**',
            'users.**',
            'roles.mentor.manage',
            'roles.buddy.manage',
        ],
        'training-staff' => [
            'training.**',
            '!training.delete',
            '!training.ratings.manage',
            '!training.reports.one-time-link',
            '!training.attachments.view-hidden',
            'endorsements.solo.*',
            'examinations.manage',
            'fir.management.reports.view',
            'bookings.**',
            'feedback.**',
            'files.**',
            'notifications.**',
            'tasks.**',
            'users.**',
            'roles.mentor.manage',
            'roles.buddy.manage',
        ],
        'staff' => [
            'fir.positions.view',
        ],
        'nav-editor' => [
            'fir.positions.*',
        ],
        'mentor' => [
            'training.view',
            'training.create',
            'training.mentor',
            'training.mentor-dashboard.view',
            'training.reports.one-time-link',
            'training.attachments.view-hidden',
            'tasks.manage',
            'files.upload',
            'bookings.bypass-restrictions',
            'bookings.sweatbox.use',
        ],
        'buddy' => [
            'training.view',
            'training.reports.one-time-link',
        ],
    ],
];

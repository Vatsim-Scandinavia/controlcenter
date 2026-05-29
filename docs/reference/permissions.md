---
icon: material/file-tree
---

# Roles and Permissions Reference

The catalogue of roles, permissions, and configuration knobs that ship with Control Center. For the conceptual picture and a worked example, see [Roles and Permissions](../concepts/permissions.md).

## Default Roles

| Role | Scope | Description |
| --- | --- | --- |
| `admin` | `global` | System-wide administrator. Bypasses area checks (via the per-policy `before` hook) and is expected to be listed against every permission you want unrestricted. |
| `moderator` | `both` | Area moderator. Manages users, reports, positions, and endorsements within the assigned area, or system-wide if assigned globally. |
| `nav-editor` | `area` | Navigational editor. May edit operationally relevant sector data such as positions within the assigned area. |
| `mentor` | `area` | Training mentor. Can manage and view training within the assigned area. |
| `buddy` | `area` | Training buddy. Limited training visibility within the assigned area. |

### Role Scope

The `scope` field on a role restricts where assignments are allowed:

- `global` — only system-wide assignments (no `area_id`).
- `area` — only area-scoped assignments (`area_id` required).
- `both` — either; an area-less assignment behaves as system-wide.

## Default Permission Matrix

The matrix in `config/roles.php`:

```php
'matrix' => [
    // Training
    'view-training' => ['admin', 'moderator', 'mentor', 'buddy'],
    'create-training' => ['admin', 'moderator', 'mentor'],
    'update-training' => ['admin', 'moderator'],
    'delete-training' => ['admin'],

    // Area & system
    'manage-area' => ['admin'],
    'view-system-health' => ['admin'],

    // Users & access
    'manage-users' => ['admin', 'moderator'],
    'view-user-access' => ['admin', 'moderator'],

    // Operations
    'manage-positions' => ['admin', 'moderator', 'nav-editor'],

    // Endorsements
    'manage-endorsements' => ['admin', 'moderator'],
    'manage-visiting-endorsements' => ['admin'],
    'manage-examiner-endorsements' => ['admin'],

    // Reports
    'view-management-reports' => ['admin', 'moderator'],
    'view-training-activities' => ['admin', 'moderator'],
    'view-training-statistics' => ['admin', 'moderator'],
    'view-mentor-reports' => ['admin', 'moderator', 'mentor'],

    // Bookings
    'bypass-booking-restrictions' => ['admin', 'moderator', 'mentor'],
],
```

A permission that is not listed in the matrix grants no role — `admin` included. The "administrators can do anything" behaviour is a per-policy convention (a `before` hook), not a property of the matrix.

## Customising Roles and Permissions

`config/roles.php` is the single source of truth.

- **Rewire** an existing permission by changing the role list for that key in the `matrix` block. Example: drop `mentor` from `bypass-booking-restrictions` to tighten booking enforcement.
- **Add** a new role by adding an entry under `roles` with a `name`, `description`, and `scope`, then add it to whichever permissions it should hold in the `matrix`.
- **Remove** a role you don't use by deleting it from `roles`, dropping it from any permission lists in the `matrix`, and clearing its assignments from the `role_user` table.

After changing the file, clear the config cache so the new mapping is picked up:

```sh
php artisan optimize:clear
```

## Storage: the `role_user` Table

User role assignments live in the `role_user` table.

| Column | Type | Notes |
| --- | --- | --- |
| `user_id` | unsigned bigint | The assignee. |
| `role` | string | Must match a key in `config/roles.php` for the assignment to grant anything. |
| `area_id` | unsigned int (nullable) | `null` for global assignments. |
| `created_at`, `updated_at` | timestamps | |

A unique constraint covers `(user_id, role, area_id)`.

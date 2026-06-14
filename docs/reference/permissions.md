---
icon: material/file-tree
---

# Roles and Permissions Reference

The catalogue of roles, permissions, and configuration knobs that ship with Control Center. For the conceptual picture and a worked example, see [Roles and Permissions](../concepts/permissions.md).

## Default Roles

| Role | Scope | Description |
| --- | --- | --- |
| `admin` | `global` | System-wide administrator. Assignable **only** via the `user:makeadmin` CLI command — never through the UI. Bypasses area checks (via the per-policy `before` hook) and holds every permission except those explicitly negated in its matrix entry. |
| `director` | `both` | Director of an area, or of the whole organisation when assigned globally. Holds every permission except the `system.**` namespace (e.g. `system.health.view`, `system.settings.manage`). Only global admins and global directors may grant or revoke it. |
| `moderator` | `both` | Area moderator. Manages users, reports, positions, and endorsements within the assigned area, or system-wide if assigned globally. |
| `nav-editor` | `area` | Navigational editor. May edit operationally relevant sector data such as positions within the assigned area. |
| `mentor` | `area` | Training mentor. Can manage and view training within the assigned area. |
| `buddy` | `area` | Training buddy. Limited training visibility within the assigned area. |

!!! note "Removing an admin"
    There is currently no CLI command to revoke the `admin` role; removal requires
    deleting the row from the `role_user` table directly.
    <!-- TODO: replace with `user:removeadmin` once available. -->

### Role Scope

The `scope` field on a role restricts where assignments are allowed:

- `global` — only system-wide assignments (no `area_id`).
- `area` — only area-scoped assignments (`area_id` required).
- `both` — either; an area-less assignment behaves as system-wide.

## Permission Catalogue and Matrix

`config/roles.php` holds three blocks:

- `roles` — the role definitions above.
- `permissions` — the flat catalogue of every dot-namespaced permission that exists.
- `matrix` — maps each role to the permission **patterns** it grants.

Patterns support dot-wildcards:

- `*` matches exactly one segment — `fir.positions.*` covers `fir.positions.manage` but not `fir.positions.foo.bar`.
- `**` matches one or more segments — `training.**` covers `training.view` and `training.reports.view`.
- A leading `!` negates a pattern; deny always wins. This is how `director` gets everything except `system.**`.

A permission granted by no role, or absent from the catalogue, grants nothing — `admin` included. The "administrators can do anything" behaviour remains a per-policy `before` hook, not a property of the matrix.

## Customising Roles and Permissions

`config/roles.php` is the single source of truth.

- **Rewire** a role by editing its pattern list in the `matrix` block. Example: drop `bookings.sweatbox.use` from `mentor` to remove their sweatbox access.
- **Add** a new permission by adding it to the `permissions` catalogue and granting it to roles via patterns in the `matrix`.
- **Add** a new role by adding an entry under `roles`, then granting it permissions in the `matrix`.
- **Remove** a role by deleting it from `roles` and `matrix`, and clearing its assignments from the `role_user` table.

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

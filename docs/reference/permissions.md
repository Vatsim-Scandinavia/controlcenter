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
| `training-staff` | `area` | Training staff. Edit access to training-related matters within the assigned area: training and reports (excluding delete and rating management), examinations, and solo endorsements. A training-only subset of `moderator`. |
| `nav-editor` | `area` | Navigational editor. May edit operationally relevant sector data such as positions within the assigned area. |
| `event-staff` | `global` | Event staff. Can create bulk bookings for any position, bypass rating and endorsement restrictions, and manage other users' bookings. Booking permissions apply organisation-wide. |
| `mentor` | `area` | Training mentor. Can manage and view training within the assigned area. |
| `buddy` | `area` | Training buddy. Limited training visibility within the assigned area. |
| `staff` | `both` | Generic staff member. Can view positions but not edit them. |

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

A permission granted by no role, or absent from the catalogue, grants nothing — `admin` included. The "administrators can do anything" behaviour is a per-policy `before` hook rather than a matrix rule; see [How a check resolves](../concepts/permissions.md#how-a-check-resolves) for what that means.

## Customising Roles and Permissions

!!! warning "Edit `config/roles.php` with care"
    This file controls who can do what across the whole application, and there is no
    safety net in the UI to catch a mistake. A stray pattern can quietly lock people
    out of an area, or hand out access you never intended. A few things to keep in mind:

    - A permission that is missing from the `permissions` catalogue grants nothing to
      anyone, `admin` included. Removing an entry is not the same as leaving it in place.
    - Deleting a role only stops it granting access. Any rows still pointing at it in
      `role_user` are left behind and grant nothing, so clean them up too.
    - Changes do not take effect until you clear the config cache (see below).

    Try changes on a staging instance first, keep the file under version control so you
    can roll back, and double-check you have not removed your own access before you
    deploy.

`config/roles.php` is the single source of truth.

- **Rewire** a role by editing its pattern list in the `matrix` block. Example: drop `bookings.sweatbox.use` from `mentor` to remove their sweatbox access.
- **Add** a new permission by adding it to the `permissions` catalogue and granting it to roles via patterns in the `matrix`.
- **Add** a new role by adding an entry under `roles`, then granting it permissions in the `matrix`.
- **Remove** a role by deleting it from `roles` and `matrix`, and clearing its assignments from the `role_user` table.

After changing the file, clear the config cache so the new mapping is picked up:

```sh
php artisan optimize:clear
```

## Assigning Event Staff

The `event-staff` role grants `bookings.manage` and `bookings.bypass-restrictions`.
It does not grant user management, training administration, or sweatbox permissions.
It is global because the booking system checks these permissions without an area.

1. Deploy the updated `config/roles.php`, then run `php artisan optimize:clear`
   on the application server. No database migration is needed.
2. Sign in as an administrator or a **global** director. These roles can grant
   Event Staff through `roles.event-staff.manage`.
3. Open **Users → Member Overview** (or **Other Users**), find the person, and
   open their profile.
4. In **Access**, click **Add role**, select **Event Staff**, select **Global**,
   and click **Grant**. Repeat for each person.
5. The person can open `/booking/bulk`, enter the date and start/end times,
   supply comma-separated position callsigns, choose a tag, and click
   **Add Bookings**.

To remove access, open the same profile's **Access** panel, remove **Event Staff**
under **Global**, and confirm. Other roles held by the person may still grant
booking access.

## Storage: the `role_user` Table

User role assignments live in the `role_user` table.

| Column | Type | Notes |
| --- | --- | --- |
| `user_id` | unsigned bigint | The assignee. |
| `role` | string | Must match a key in `config/roles.php` for the assignment to grant anything. |
| `area_id` | unsigned int (nullable) | `null` for global assignments. |
| `created_at`, `updated_at` | timestamps | |

A unique constraint covers `(user_id, role, area_id)`.

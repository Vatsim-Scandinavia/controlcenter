---
icon: material/cog-refresh
---

--8<-- "exec-in-container.md"

## All versions

Updating between minor versions only requires you to run migration and clear caches.
Remember to [run the theme build](setup/theme.md) again if you have a custom theme.

Once done, you must migrate the database changes and clear the cache:

```sh
php artisan migrate
php artisan optimize:clear
```

## Upgrading to 7.0.0

This release contains breaking changes to the theme system.

### StatSim Activity Chart

You might've noticed that the ATC activity numbers on users' profiles have started generating errors.
This is due to a change in the StatSim API offering which we didn't get around to fixing before the API was deprecated and decomissioned. 

The new API requires the use of a dedicated API key, which has a corresponding [required new environment variable for authenticating to StatSim](configuration/index.md#vatsim).

### Access Management: CLI-only Admin and the new Director role

The `admin` role is now strictly system-wide and can no longer be granted, scoped
to an area, or revoked through the web UI. It is assigned exclusively via:

```sh
php artisan user:makeadmin
```

A new `director` [role takes over the "full access to an area" use-case](reference/permissions.md).
It can be assigned per area or globally through the user access page, and holds every admin permission except the system-level ones (`manage-area`, `view-system-health`).
Only global admins and global directors can grant or revoke directorships.

#### Manual step after upgrading

Members of the previous administrator group are migrated as **global admins** and
retain full access. After upgrading, review who actually needs system-wide admin
rights:

1. Grant `director` (per-area or global) through the UI to those who only need
   area- or organisation-level management access.
2. Remove their admin assignment from the `role_user` table directly
   (`DELETE FROM role_user WHERE user_id = <cid> AND role = 'admin';`).
   There is no CLI command for revoking admin yet.
   <!-- TODO: replace with `user:removeadmin` once available. -->

Until this review is done, previously migrated admins keep unrestricted access.

### Theme System Migration

The theme system has been completely redesigned to support light/dark themes and user preferences.

#### Breaking Changes

1. **Environment Variables Removed**: All `VITE_THEME_*` color variables have been removed from `.env` file
2. **Theme Files**: Themes are now defined in separate SCSS files under `resources/sass/themes/`
3. **User Preference**: Theme selection is now a per-user setting stored in the database

#### Migration Steps

1. **Remove old environment variables** from your `.env` file:
   - Remove any `VITE_THEME_*` color variables (these are no longer used)

2. **Run the database migration**:
   ```sh
   php artisan migrate
   ```
   This adds the `setting_theme` column to the `users` table.

3. **Rebuild frontend assets**:
   ```sh
   npm run build
   ```

4. **Clear caches**:
   ```sh
   php artisan optimize:clear
   ```

5. **Clear browser cache** and test the new theme system

#### For Custom Theme Users

If you had customized colors in your `.env` file:

1. Create a custom theme file in `resources/sass/themes/_custom.scss`
2. Copy the structure from `_light.scss` or `_dark.scss`
3. Update your color variables to match your previous customizations
4. Import your theme in `resources/sass/app.scss`
5. See [User Theme Guide](user-themes.md) and [Theme Setup](setup/theme.md) for detailed instructions

For detailed information on using themes as an end-user, see the [User Theme Guide](user-themes.md).  
For customizing themes as an operator, see [Theme Setup](setup/theme.md)

### Permissions and Roles Refactor

The permission and group system has been rebuilt around a configurable role matrix. See [Permissions and Roles](concepts/permissions.md) for the new model.

#### Breaking Changes

1. **Database tables replaced**: The `groups` and `permissions` tables are dropped. User assignments now live in a single `role_user` table that stores the role name as a string and an optional `area_id` (null for global roles).
2. **Permissions are no longer stored in the database**: They are defined in `config/roles.php` as a *matrix* that maps each permission to the list of roles that hold it. Editing roles or permissions now means editing that file (and clearing config cache), not the database.
3. **Admins are now strictly global**: Any `area_id` previously attached to an admin assignment is discarded by the migration. An admin assignment without a target area applies system-wide.
4. **Custom groups are not migrated**: Only the four standard groups (Administrator, Moderator, Mentor, Buddy) are converted to roles. Any custom group rows you added directly to the `groups` or `permissions` tables will be dropped along with the tables. Note them down before upgrading and re-create them as roles in `config/roles.php` afterwards.
5. **The `nav-editor` role is new in this version**: It ships in `config/roles.php` (see [Permissions and Roles](concepts/permissions.md)) but is not derived from any legacy group, so no users will be auto-assigned to it during the migration. Grant it explicitly to whoever needs to edit navigational data within an area.

#### Migration Steps

1. **Audit any custom groups** you may have added outside the four defaults; capture their members so you can re-grant access after the upgrade.
2. **Run the database migration**:
   ```sh
   php artisan migrate
   ```
   This creates `role_user`, copies the four standard groups across, and drops the old tables.
3. **Review `config/roles.php`** and adjust the matrix if your division wants different permissions per role, or wants to add roles beyond the defaults.
4. **Clear caches**:
   ```sh
   php artisan optimize:clear
   ```

## Upgrading to 6.0.0

This release contains breaking changes and requires you to backup your data before upgrading.

- To fix the incorrect setting of training tags on bookings, the `positions` has now a `required_facility_rating_id` column which replaces previous boolean `mae` column.
    - Note down your old mae values before you run the migration to avoid data loss.
    - For normal GCAP Rated positions (S1-C1) you don't need to do anything.
    - For positions that require a facility endorsement (Tier 1, Tier 2 and Special Center), you need to fill the id of the row in your `ratings` table that corresponds to the facility rating.

## Upgrading to v5.1.0

Please follow these steps if you wish to use the new VATEUD Core integration. May be skipped otherwise.

- Make sure you've configured all your T1/T2 endorsements in [rating table](setup/division.md#ratings).
- Manually sync any existing solos, mentors and examiners in Core prior to enabling the integration.
- Update your environment file with the new variables found in the example file.
- This will be run daily by the scheduler, but run this manually first time:
    - Run the artisan command `php artisan sync:roster` to sync the roster.
    - Run the artisan command `php artisan sync:endorsements <your cid>` to add existing T1/T2 endorsements.

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

This release changes how access is managed and rebuilds the theme system, so it
needs a few manual steps beyond the usual migrate and cache clear. Work through the
sections below in order.

### ATC activity chart (StatSim)

The ATC activity numbers on user profiles started erroring out. The old StatSim API
was deprecated and shut down before we could update the integration, which is what
you were seeing.

The replacement API needs its own API key. Add the new
[StatSim environment variable](configuration/index.md#vatsim) and the chart will
work again.

### Admin is now CLI-only, with a new Director role

The `admin` role is now strictly system-wide. You can no longer grant it, scope it
to an area, or revoke it from the web UI. Assign it from the command line instead:

```sh
php artisan user:makeadmin
```

The new `director` role covers the "full access to an area" case that admin used to
fill. You grant it per area or globally from a user's access page, and it holds
every admin permission except the system-level ones (`manage-area`,
`view-system-health`). Only global admins and global directors can grant or revoke
it. See [Roles and Permissions](reference/permissions.md) for the full picture.

#### After upgrading, review who is an admin

Everyone in the old administrator group becomes a global admin during the
migration, so they keep full access. Once you have upgraded, check who really needs
system-wide rights:

1. Grant `director` (per area or global) to anyone who only needs area or
   organisation level management.
2. Remove their admin assignment from the `role_user` table by hand. There is no
   command for revoking admin yet.
   ```sql
   DELETE FROM role_user WHERE user_id = <cid> AND role = 'admin';
   ```
   <!-- TODO: replace with `user:removeadmin` once available. -->

Until you do this, the migrated admins keep unrestricted access.

### Theme system

Themes were redesigned to support light and dark modes and a per-user preference.
The colors you used to set in `.env` have moved into SCSS files.

What changed:

- The `VITE_THEME_*` color variables in `.env` are gone.
- Themes now live in SCSS files under `resources/sass/themes/`.
- Each user picks their own theme, and the choice is stored in the database.

To upgrade:

1. Remove any `VITE_THEME_*` variables from your `.env` file.
2. Run the migration, which adds the `setting_theme` column to the `users` table:
   ```sh
   php artisan migrate
   ```
3. Rebuild the frontend assets:
   ```sh
   npm run build
   ```
4. Clear the caches:
   ```sh
   php artisan optimize:clear
   ```
5. Clear your browser cache and check that theme switching works.

If you had custom colors in `.env`, move them into a custom theme file. In short:
copy `_custom.scss.example` to `_custom.scss`, port your colors across using
`_light.scss` and `_dark.scss` as a reference, and rebuild. See
[Themes](setup/theme.md) for the full walkthrough.

### Permissions and roles

The old groups and permissions system has been rebuilt around a role matrix you
configure in `config/roles.php`. See [Roles and Permissions](concepts/permissions.md)
for how the new model works.

What changed:

- The `groups` and `permissions` tables are dropped. Assignments now live in one
  `role_user` table that stores the role name and an optional `area_id` (null means
  global).
- Permissions are no longer in the database. They are defined in `config/roles.php`
  as a matrix that maps each permission to the roles that hold it. Changing access
  now means editing that file and clearing the config cache, not updating the
  database.
- Admins are now strictly global. Any `area_id` on an old admin assignment is
  dropped during the migration.
- Custom groups are not migrated. Only the four standard groups (Administrator,
  Moderator, Mentor, Buddy) become roles. Any custom group or permission rows you
  added by hand are dropped with the old tables, so note them down first and
  recreate them as roles afterwards.
- The `nav-editor` role is new and is not derived from any old group, so nobody is
  assigned to it automatically. Grant it to whoever needs to edit navigational data
  in an area.

To upgrade:

1. Note down any custom groups you added beyond the four defaults, along with their
   members, so you can re-grant access afterwards.
2. Run the migration, which creates `role_user`, copies the four standard groups
   across, and drops the old tables:
   ```sh
   php artisan migrate
   ```
3. Review `config/roles.php` and adjust the matrix if your division wants different
   permissions per role or extra roles.
4. Clear the caches:
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

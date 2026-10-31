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

This release changes how access is managed, rebuilds the theme system, and switches
the ATC activity chart to a new API. Follow the upgrade steps once, then work
through the post-upgrade tasks. A single `php artisan migrate` applies every
database change in this release, so you only run it once.

### Upgrade steps

1. If you added any custom groups beyond the four defaults (Administrator,
   Moderator, Mentor, Buddy), note down their members now. The migration drops them,
   and you recreate them as roles afterwards.
2. Update your environment file:
    - Add the new [StatSim API key variable](configuration/index.md#vatsim). The
      activity chart stays broken until this is set.
    - Remove any `VITE_THEME_*` color variables. They are no longer used.
3. Run the database migration. This one command applies everything in the release,
   including the new `setting_theme` column on `users` and the new `role_user` table.
   It also copies the four standard groups across and drops the old `groups` and
   `permissions` tables:
   ```sh
   php artisan migrate
   ```
4. Rebuild the frontend assets, but only if you run without a container and maintain
   a custom theme. The published container image already ships with assets built,
   and you should never build inside a running container. See
   [Themes](setup/theme.md) for why:
   ```sh
   npm run build
   ```
5. Clear the caches:
   ```sh
   php artisan optimize:clear
   ```
6. Clear your browser cache, then load the app and confirm theme switching works and
   the ATC activity chart renders on a profile.

### Post-upgrade tasks

These need your judgement, so do them after the steps above.

#### Review who is a global admin

Everyone in the old administrator group becomes a global admin during the migration,
so they keep full access until you review it. Admins can no longer be changed from
the UI, so this is a manual database step.

!!! warning "Back up first, and keep at least one admin"
    This edits the `role_user` table directly, with no undo. Back the table up before
    you delete anything, remove only the rows you mean to, and make sure at least one
    admin remains. Do not delete your own only admin row.

1. Grant `director` (per area or global) from a user's access page to anyone who only
   needs area or organisation level management.
2. List the current admin assignments and confirm which rows you mean to remove.
   `user_id` is the person's VATSIM CID:
   ```sql
   SELECT * FROM role_user WHERE role = 'admin';
   ```
3. Remove the assignments you no longer want:
   ```sql
   DELETE FROM role_user WHERE user_id = <cid> AND role = 'admin';
   ```
   There is no command for revoking admin yet.
   <!-- TODO: replace with `user:removeadmin` once available. -->

#### Review `config/roles.php`

Adjust the matrix if your division wants different permissions per role, or extra
roles beyond the defaults. Recreate any custom groups you noted down in step 1 as
roles here. Run `php artisan optimize:clear` again after editing the file.

### What changed

#### Admin is now CLI-only, with a new Director role

The `admin` role is now strictly system-wide. You can no longer grant it, scope it
to an area, or revoke it from the web UI. Assign it from the command line instead:

```sh
php artisan user:makeadmin
```

The new `director` role covers the "full access to an area" case that admin used to
fill. It holds every admin permission except the system-level ones (`manage-area`,
`view-system-health`), and only global admins and global directors can grant or
revoke it. See [Roles and Permissions](reference/permissions.md) for the full picture.

#### Theme system

Themes were redesigned to support light and dark modes and a per-user preference.
The colors you used to set with `VITE_THEME_*` in `.env` have moved into SCSS files
under `resources/sass/themes/`, and each user now picks their own theme, stored in
the database.

If you had custom colors in `.env`, move them into a custom theme file: copy
`_custom.scss.example` to `_custom.scss`, port your colors across using `_light.scss`
and `_dark.scss` as a reference, and rebuild. See [Themes](setup/theme.md) for the
full walkthrough, including how to bake a custom theme into a container image.

#### Permissions and roles

The old groups and permissions system has been rebuilt around a role matrix you
configure in `config/roles.php`. Assignments now live in one `role_user` table that
stores the role name and an optional `area_id` (null means global), and permissions
are defined in `config/roles.php` rather than the database. A few things to know:

- Admins are now strictly global. Any `area_id` on an old admin assignment is dropped
  during the migration.
- Only the four standard groups (Administrator, Moderator, Mentor, Buddy) are
  migrated. Any custom group or permission rows you added by hand are dropped with
  the old tables, which is why you note them down before upgrading and recreate them
  as roles afterwards.
- The `nav-editor` role is new and is not derived from any old group, so nobody is
  assigned to it automatically. Grant it to whoever needs to edit navigational data
  in an area.

See [Roles and Permissions](concepts/permissions.md) for how the new model works.

#### ATC activity chart (StatSim)

The old StatSim API was deprecated and shut down, which is why the ATC activity
numbers on profiles started erroring. The replacement API needs its own API key,
added in step 2 above.

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

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

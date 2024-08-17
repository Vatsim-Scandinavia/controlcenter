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

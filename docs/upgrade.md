---
icon: material/cog-refresh
---

Updating between minor versions only requires you to run migration and clear caches.
Remember to [run the theme build](setup/theme.md) again if you have a custom theme.

Once done, you must migrate the database changes and clear the cache:

```sh
php artisan migrate
php artisan optimize:clear
```

--8<-- "exec-in-container.md"

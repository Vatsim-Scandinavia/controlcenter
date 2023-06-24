# Upgrading

## Upgrading from 3.x to 4.0

These are the steps required to migrate from 3.x to 4.0, please make sure you've a backup before starting this process.

### Breaking change ⚠️

Handover is longer required as data source, but you can still use it if you want to. Though all user data is now stored in Control Center and Handover simply serves as an authenticator and provides updated user data from VATSIM.

Please note that users need to login again after this update, as it'll create a new refresh token Control Center needs to pull newest user data from Handover now as the data source has been moved. E.g. a new rating or division change will not be automatically pulled from Handover until the user has logged in again. After this it'll pull data as normally again without requiring a login.

### Steps

#### Docker

We now offer a Docker container that can be used to run Control Center, this is the recommended hosting now that ensures correct environement. If you still prefer to run it without Docker, this is still possible by cloning the repo and building the project manually.

*Note: The included docker-compose.yaml is only for development purposes and should not be used in production.*

1. Pull the `ghcr.io/vatsim-scandinavia/control-center:v4.0.0` Docker image
2. Configure the environment variables as described in the [CONFIGURE.md](CONFIGURE.md)
3. Start the container
4. Run `php artisan key:generate` inside the container,
5. Setup a crontab outside the container to run `* * * * * docker exec --user www-data -i control-center php artisan schedule:run >/dev/null` every minute. This patches into the container and runs the required cronjobs.
6. Bind the 8080 (HTTP) and/or 8443 (HTTPS) port to your reverse proxy or similar.

#### Data Migration

1. Do not erase the `DB_HANDOVER_` database config from your .env this is required to perform the migration.
2. Run the migration with `php artisan migrate` inside the container, this will copy over the required data fields from Handover so CC can run on it's own
3. If the migration was successful you may now remove the `DB_HANDOVER_*` environment settings as it'll no longer be used.
4. If you don't want to use Handover at all anymore, you can at this point change the OAUTH environment settings to the VATSIM OAuth settings.

#### OAuth

The modified OAuth solution can now accept both Handover, VATSIM Connect or any other provider. The only requirement is that if you don't use VATSIM Connect, you need to map the user data to the correct fields in the response of your provider.

**If you want to use VATSIM Connect**: No mapping needed\
**If you want to use Handover**: Set the `OAUTH_MAPPING_*` environment variables listed in [the configuration manual](CONFIGURE.md#oauth)

### You're done!
You should now have a running and working instance of Control Center v4 🎉

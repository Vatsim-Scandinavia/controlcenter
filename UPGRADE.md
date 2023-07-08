# Upgrading

## Upgrading from version 3.4 to 4.0

To have the smoothest upgrade experience, we highly recommend that you've upgraded to v3.4.0 before upgrading to v4.0.x. This way it'll be easier to isolate issues that might happen on the way. 

Make sure you have a manual backup of the database before upgrading.

### Breaking change ⚠️

Handover is longer required as data source, but you can still use it if you want to. Though all user data is now stored in Control Center and Handover simply serves as an authenticator and provides updated user data from VATSIM.

Please note that users need to login again after this update, as it'll create a new refresh token Control Center needs to pull newest user data from Handover now as the data source has been moved. E.g. a new rating or division change will not be automatically pulled from Handover until the user has logged in again. After this it'll pull data as normally again without requiring a login.

### Steps

#### Docker

We now offer a Docker container that can be used to run Control Center, this is the recommended hosting now that ensures correct environement. If you still prefer to run it without Docker, this is still possible by cloning the repo and building the project manually.

In the instructions where we use `docker exec` we assume your container is named `control-center`. If you have named differently, please replace this.

1. Pull the `ghcr.io/vatsim-scandinavia/control-center:v4.0.3` Docker image
2. Configure the environment variables as described in the [CONFIGURE.md](CONFIGURE.md)
3. Start the container
4. To ensure that users will not need to log in after each time you re-deploy or upgrade the container, you need to create and store an application key in your environment and setup a shared volume. 
   ```sh
   docker exec -it control-center php artisan key:get
   docker volume create controlcenter_sessions
   ```
   Copy the key and set it as the `APP_KEY` environment variable in your Docker configuration and bind the volume when creating the container with `controlcenter_sessions:/app/storage/framework/sessions`.
5. To keep uploaded files between deployments, you need to bind this to a host folder, such as `/YOUR/HOST/LOCATION:/app/storage/app/public/files`, and set correct permissions of this folder with
   ```sh 
   docker exec -it control-center chown -R www-data:www-data /app/storage/app/public/files
   ```
6. Setup a crontab outside the container to run `* * * * * docker exec --user www-data -i control-center php artisan schedule:run >/dev/null` every minute. This patches into the container and runs the required cronjobs.
7. Bind the 8080 (HTTP) and/or 8443 (HTTPS) port to your reverse proxy or similar.

#### Data Migration

1. Make sure that `DB_HANDOVER_` database config is present in your environment file or docker configuration. Do not remove this as it's required for the migration.
2. Run the migration, this will copy over the required data fields from Handover so CC can run on it's own
   ```sh
   docker exec -it control-center php artisan migrate
   ```
3. If the migration was successful you may now remove the `DB_HANDOVER_*` environment settings as it'll no longer be used.
4. If you don't want to use Handover at all anymore, you can at this point change the OAUTH environment settings to the VATSIM OAuth settings.

#### OAuth

The modified OAuth solution can now accept both Handover, VATSIM Connect or any other provider. The only requirement is that if you don't use VATSIM Connect, you need to map the user data to the correct fields in the response of your provider.

**If you want to use VATSIM Connect**: No mapping needed\
**If you want to use Handover**: Set the `OAUTH_MAPPING_*` environment variables listed in [the configuration manual](CONFIGURE.md#oauth)

### You're done!
You should now have a running and working instance of Control Center v4 🎉

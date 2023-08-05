## Control Center

Training Management System created by [Daniel L.](https://github.com/blt950) (1352906), [Gustav K.](https://github.com/gustavkauman) (1262761) and others from Web Department at VATSIM Scandinavia. Running using `Laravel 9` in a pre-built Docker container.

📝 The project is open source and contains some restirctions. Read the [LICENSE](LICENSE) for details.\
👁️ Remember to watch this repository to get notified of our patches and updates!

![Picture of Control Center dashboard](https://github.com/Vatsim-Scandinavia/controlcenter/assets/2505044/e115c1d0-d7e5-41cb-8fd6-0a787f06c0ea)

## Prerequisites

### Docker (Recommended)
- A Docker environment to deploy containers. We recommend [Portainer](https://www.portainer.io/).
- MySQL database to store data.
- Preferably a reverse proxy setup if you plan to host more than one website on the same server.

In the instructions where we use `docker exec`, we assume your container is named `control-center`. If you have named it differently, please replace this.

### Manual (Unsupported)
If you don't want to use Docker, you need:
- An environment that can host PHP websites, such as Apache, Ngnix or similar.
- MySQL database to store data.
- Comply with [Laravel 9 Requirements](https://laravel.com/docs/9.x/deployment#server-requirements)
- Manually build the composer, npm and setting up cron jobs and clearing all caches on updates.

## Setup and install

*Upgrading from to v4 from v3? Read the [UPGRADE.md](UPGRADE.md) instead for details.*

To setup your Docker instance simply follow these steps:
1. Pull the `ghcr.io/vatsim-scandinavia/control-center:v4` Docker image
2. Setup your MySQL database (not included in Docker image)
3. Configure the environment variables as described in the [CONFIGURE.md](CONFIGURE.md#environment)
4. Start the container in the background.
5. Setup the database.
   ```sh
   docker exec -it control-center php artisan migrate
   ```
6. To ensure that users will not need to log in after each time you re-deploy or upgrade the container, you need to create and store an application key in your environment and setup a shared volume. 
   ```sh
   docker exec -it control-center php artisan key:get
   docker volume create controlcenter_sessions
   ```
   Copy the key and set it as the `APP_KEY` environment variable in your Docker configuration and bind the volume when creating the container with `controlcenter_sessions:/app/storage/framework/sessions`.
7. To keep uploaded files between deployments, you need to bind this to a host folder, such as `/YOUR/HOST/LOCATION:/app/storage/app/public/files`, and set correct permissions of this folder.
   ```sh 
   docker exec -it control-center chown -R www-data:www-data /app/storage/app/public/files
   ```
8. Setup a crontab _outside_ the container to run `* * * * * docker exec --user www-data -i control-center php artisan schedule:run >/dev/null` every minute. This patches into the container and runs the required cronjobs.
9. Bind the 8080 (HTTP) and/or 8443 (HTTPS) port to your reverse proxy or similar.

## Configuring

To have Control Center reflect your division correctly, you need to do some tweaks.

- Give your user admin access
   ```sh
   docker exec -it control-center php artisan user:makeadmin
   ```
- You can now access `Administration -> Settings` in the menu to tweak the most basic settings for your division.
- You are also required to configure logic and datasets in the MySQL database as described in [CONFIGURE.md](CONFIGURE.md#database) with examples

## Using the API

Control Center has an API that you can use to fetch useful data from the database. Read more in the [API documentation](API.md).

## Present automations
There's quite a few automations in Control Center that are running through the cron-jobs. They're as follows:

- All trainings with status In Queue or Pre-Training are given a continued interest request each month, and a reminder after a week. Failing to reply within two weeks closes the request automatically.
- ATC Active is flag given based on ATC activity. Refreshes daily with data from VATSIM Data API. It counts the hours from today's date and backwards according to the length of qualification period.
- Daily member cleanup, if a member leaves the division, their training will be automatically closed. Same for mentors. Does not apply to visitors.
- Other misc cleanups

## Contributing, conventions and intro to Laravel

Do you want to help us with improving Control Center? Curious about whether we use testing? Stylistic choices?\
[Read the `CONTRIBUTE.md`](CONTRIBUTE.md) for details.

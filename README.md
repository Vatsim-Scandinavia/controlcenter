## Control Center
Training Management System created by [Daniel L.](https://github.com/blt950) (1352906), [Gustav K.](https://github.com/gustavkauman) (1262761) and others from Web Department at VATSIM Scandinavia. Running using `Laravel 9` in a pre-packed Docker container.

📝 The project is open source and contains some restirctions. Read the [LICENSE](LICENSE) for details.\
👁️ Remember to watch this repository to get notified of our patches and updates!

![Picture of Control Center dashboard](https://user-images.githubusercontent.com/2505044/169692486-50ca8cb6-54a4-41a7-a18d-13a329234d30.png)

## Prerequisites

### Docker (Recommended)
- A Docker environment to deploy containers.
- MySQL database (or MariaDB) to store data.
- Preferably a reverse proxy setup if you plan to host more than one website on the same server.

### Manual (Unsupported)
If you don't want to use Docker, you need:
- An environment that can host PHP websites, such as Apache, Ngnix or similar.
- Comply with [Laravel 9 Requirements](https://laravel.com/docs/9.x/deployment#server-requirements)

*Remember to build the composer, npm and setting up cron jobs as well.*

## Setup and install

*Upgrading from to v4 from v3? Read the [UPGRADE.md](UPGRADE.md) instead for details.*

To setup your Docker instance simply follow these steps:
1. Pull the `ghcr.io/vatsim-scandinavia/control-center:v4` Docker image
2. Setup your MySQL database (not included in Docker image)
3. Configure the environment variables as described in the [CONFIGURE.md](CONFIGURE.md)
4. Run the container
5. Run `php artisan generate:key` inside the container

## Configuring

To have Control Center reflect your division correctly, you need to do some tweaks.
- Give your user admin access by manipulating the database table `permissions`. Set your `group_id` to `1`. Area need to be specified but can be any.
- You can now access `Administration -> Settings` in the menu to tweak the most basic settings for your division.
- You are also required to configure logic and datasets in the MySQL database as described in [CONFIGURE.md](CONFIGURE.md) with examples

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

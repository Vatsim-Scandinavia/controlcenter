## Control Center
Training Management System originally created by [Daniel L.](https://github.com/blt950) (1352906), [Gustav K.](https://github.com/gustavkauman) (1262761) and others from Web Department at VATSIM Scandinavia. Running using `Laravel 8`, based on `SB Admin 2` boostrap theme.

The project is open source and contains some restirctions. Read the [LICENSE.md](LICENSE.md) for details.

**Remember to watch this repository to get notified of our patches and updates!*

## Prerequisites
- An environment that can host PHP websites, such as Apache, Ngnix or similar.
- [Laravel 8 Requirements](https://laravel.com/docs/8.x/deployment#server-requirements)
- Using [Handover](https://github.com/Vatsim-Scandinavia/handover) as user data source

## Setup and install
Just clone this repository and you're almost ready. First, make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) in your environment.

1. Run `./deploy init` to setup the required files
2. Configure the .env file accordingly, everything from top down to and including e-mail should be configured, rest is optional.
3. Run `npm run dev` in development environment or `npm run dev` in production to build front-end assets
4. Run `php artisan serve` to host the page at `localhost:8000` in development environment.

## Configuring
To have Control Center reflect your division correctly, you need to do some tweaks.
- Once you've made your user admin by manipulating the database, you can access `Administration -> Settings` in menu to tweak the most basic settings for your division.
- [Setup Cron in your environment](https://laravel.com/docs/8.x/scheduling#running-the-scheduler)
- You are also required to configure logic and datasets in the MySQL database as described in [CONFIGURE.md](CONFIGURE.md) with examples

## Deployment

To deploy in development environment use `./deploy dev`, in production use `./deploy`. This will automatically put the site in maintenance mode while it's deploying and open back up when finished.

## Using the API
There's an Control Center API that you can use to
- GET, POST, PATCH and DELETE bookings
- GET mentors and their area
- GET list over visiting controllers

To call the API you'll need to [create a bearer token](https://laravel.com/docs/8.x/passport#retrieving-tokens) and use this to access the API URLs you find in `routes/api.php`.

## Present automation
There's quite a few automations in Control Center that are running through the Cron-jobs. They're as follows:
- All trainings with status In Queue or Pre-Training are given a continued interest request each month, and a reminder after a week. Failing to reply within two weeks closes the request automatically.
- ATC Active is flag given based on ATC activity. Refreshes daily with data from VATSIM Data API. It counts the hours from today's date and backwards according to the length of qualification period.
- Daily member cleanup, if a member leaves the division, their training will be automatically closed. Same for mentors. Does not apply to visitors.
- Other misc cleanups

## Contribution and conventions
Contributions are much appreciated to help everyone move this service forward with fixes and functionalities. We recommend you to fork this repository here on GitHub so you can easily create pull requests back to the main project.

In order to keep a collaborative project in the same style and understandable, it's important to follow some conventions:

##### GitHub Branches
We name branches with `topic/name-here` including fixes and features, for instance `topic/new-api` or `topic/mentor-mail-fix`

##### Models/SQL
* MySQL tables are named in plural e.g `training_reports`, not `training_report`
* Models are named in singular e.g. `Training`, not `Trainings`
* Models names don't have any specific suffix or prefix
* Models are per Laravel 8 located in root of `App/Models` folder.

##### Controllers
* Controllers are suffixed with `Controller`, for instance `TrainingController`
* Controllers are named in singular e.g. `TrainingController`, not `TrainingsController`
* The controllers should mainly consist of the methods of "7 restful controller actions" [Check out this video](https://laracasts.com/series/laravel-6-from-scratch/episodes/21?autoplay=true)

##### Other
* We name our views with blade suffix for clarity, like `header.blade.php`
* For more in-depth conventions which we try to follow, check out [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices/blob/master/README.md#contents)
* We tab with 4 spaces for increased readability
* The service language is UK English

## Introduction to Laravel

##### Basics
This project has a prerequisite that you're familiar with PHP and Object-oriented programming. If you're unfamiliar with Laravel, check out these resources:

* [Laravel Documentation](https://laravel.com/docs)
* [Free and high-quality Laravel 6.0 tutorial](https://laracasts.com/series/laravel-6-from-scratch)
* [Laravel essentials in 45 min](https://www.youtube.com/watch?v=ubfxi21M1vQ)

##### Workspace
Everyone has their own setup for workspace, but if you're new to Laravel and this project, we recommend to check out our setup:

1. Use [Visual Studio Code](https://code.visualstudio.com/) as editor.
2. Install the [following plugins](https://medium.com/@rohan_krishna/how-to-setup-visual-studio-code-for-laravel-php-276643c3013c) in VS Code.
3. Optionally install [this icon pack](https://marketplace.visualstudio.com/items?itemName=PKief.material-icon-theme) for some smooth icons.
4. Learn tips and tricks for PHP and Laravel in VS Code in [these Laracasts](https://laracasts.com/series/visual-studio-code-for-php-developers/)

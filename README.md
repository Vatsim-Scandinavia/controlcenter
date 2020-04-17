## SANTA
Scandinavian Training System. Created using `Laravel 6`, based on `SB Admin 2` boostrap theme.

## Setup and install
Just clone this repository and you're almost ready. First make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) on your computer.

1. Duplicate `.env.example` file into `.env` and make sure you're running correct mysql settings
2. In the project folder, run `composer install` to install PHP dependecies and `npm install` (requires Node.js) to run Front-end dependecies.
3. Create app key `php artisan key:generate`
4. Migrate the database with `php artisan migrate`
5. Run `php artisan serve` to host the page at `localhost:8000`

## Laravel

##### Basics
This project has a prerequesite that you're familiar with PHP and Object-oriented programming. If you're unfamiliar with Laravel, check out these resources:

* Laravel Documentation: https://laravel.com/docs
* Free and high-quality Laravel 6.0 tutorial: https://laracasts.com/series/laravel-6-from-scratch
* Laravel essentials in 45 min: https://www.youtube.com/watch?v=ubfxi21M1vQ

##### Workspace
Everyone has their own setup for workspace, but if you're new to Laravel and this project, we recommend to check out our setup:

1. Use [Visual Studio Code](https://code.visualstudio.com/) as edior.
2. Install the [following plugins](https://medium.com/@rohan_krishna/how-to-setup-visual-studio-code-for-laravel-php-276643c3013c) in VS Code.
3. Optinally install [this icon pack](https://marketplace.visualstudio.com/items?itemName=PKief.material-icon-theme) for some smooth icons.
4. Learn tips and tricks for PHP and Laravel in VS Code in [these Laracasts](https://laracasts.com/series/visual-studio-code-for-php-developers/)

## Conventions
In order to keep a collaborative project in the same style and understandable, it's important to follow some conventions:

##### Models/SQL
* MySQL tables are named in plural e.g `training_reports`, not `training_report`
* Models are named in singular e.g. `Training`, not `Trainings`
* Models names don't have any specific suffix or prefix
* Models are per Laravel 6.0 located in root of `app/` folder.

##### Controllers
* Controllers are suffixed with `Controller`, for instance `TrainingController`
* Controllers are named in singular e.g. `TrainingController`, not `TrainingsController`
* The controllers should mainly consist of the methods of "7 restful controller actions" [Check out this video](https://laracasts.com/series/laravel-6-from-scratch/episodes/21?autoplay=true)

##### Other
* We name our views with blade suffix for clearity, like `header.blade.php`
* For more in-depth conventions which we try to follow, check out [Laravel best practices](https://www.laravelbestpractices.com)
* We tab with 4 spaces for increased readability

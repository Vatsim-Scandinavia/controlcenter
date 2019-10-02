## SANTA
Scandinavian Training System. Created using `Laravel 6.0`, based on `SB Admin 2` boostrap theme.

## Setup and install
Just clone this repository and you're almost ready.

1. Copy `.env.example` file into `.env` and make sure you're running correct mysql settings
2. Run `composer update` to install PHP dependecies and `npm install` to run Front-end dependecies.
3. Create app key `php artisan key:generate`
4. Run `php artisan serve` to host the page at `localhost:8000`

## Prefered Workspace
It's an advantage we use the same workspace in our development, here is how we've set it up:

* Visual Studio Code as editor
* The following plugins installed: https://medium.com/@rohan_krishna/how-to-setup-visual-studio-code-for-laravel-php-276643c3013c
* Optionally install this icon pack: https://marketplace.visualstudio.com/items?itemName=PKief.material-icon-theme


## Laravel basics
If you're unfamiliar with Laravel, check out these resources:

* Laravel Documentation: https://laravel.com/docs
* Laravel essentials in 45 min: https://www.youtube.com/watch?v=ubfxi21M1vQ


## Conventions
In order to keep a collaborative project in the same style and understandable, it's important to follow some conventions:

* MySQL tables are named in plural e.g `trainings`, not `training`
* Controllers are suffixed with `Controller`, for instance `UserController`
* The controllers should mainly consist of the methods of "7 restful controller actions" [Check out this video](https://laracasts.com/series/laravel-6-from-scratch/episodes/21?autoplay=true)
* Models don't have any specific suffix or prefix
* We name our views with blade suffix for clearity, like `header.blade.php`
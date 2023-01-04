## Contribution and conventions
Contributions are much appreciated to help everyone move this service forward with fixes and functionalities. We recommend you to fork this repository here on GitHub so you can easily create pull requests back to the main project.

In order to keep a collaborative project in the same style and understandable, it's important to follow some conventions:

### GitHub Branches
We name branches with `topic/name-here` including fixes and features, for instance `topic/new-api` or `topic/mentor-mail-fix`

### Testing

We strive to create tests for the features we do. This helps reduce the risk of us breaking the feature in the future.
It also helps us to keep the code cleaner. Imagine how you'd like to use your own feature if it were provided as a library to yourself. From that, try to create some accompanying tests, whether they're unit or feature tests.

#### Getting started

To get you started these instructions overlap a little with typical Laravel project setup.
For additional details, see Laravel documentation on setting up a new project.

```shell
# 0. Install the dependencies
composer install

# 1. Setup a local environment variables file
cp -n .env.example .env

# 2. Generate and set a Laravel application key in your environment variables
php artisan key:generate

# 3. Create a local database
php artisan migrate --database sqlite-testing
```

You should now be able to run the tests locally. To the next section.

#### Running the tests

Once you've got your setup locally, running the tests is a brief affair.

```shell
php artisan test
```

### Models/SQL
* MySQL tables are named in plural e.g `training_reports`, not `training_report`
* Models are named in singular e.g. `Training`, not `Trainings`
* Models names don't have any specific suffix or prefix
* Models are per Laravel 8 located in root of `App/Models` folder.

### Controllers
* Controllers are suffixed with `Controller`, for instance `TrainingController`
* Controllers are named in singular e.g. `TrainingController`, not `TrainingsController`
* The controllers should mainly consist of the methods of "7 restful controller actions" [Check out this video](https://laracasts.com/series/laravel-6-from-scratch/episodes/21?autoplay=true)

### Other
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

---
icon: material/file-code
---

# Contributing

Interested in hacking on Control Center? Adding a new, favourite feature? Say no more.

## Running the development environment { data-toc-label="Development environment" }

To develop Control Center, we recommend running the docker container with the included `docker-compose.yaml` file inside `.devcontainer` folder.
Note that the dev-container setup is a work in-progress, therefore we recommend using the Docker compose file. This also binds the whole application folder into the container so you can edit the files locally and they'll be updated in the container.

### Setup inside container

1. Run `composer install` to install all dependencies
2. Run `npm install` to install all dependencies
3. Run `npm run build` to compile the assets
4. Run `php artisan migrate` to setup the database

If you need test data, you can also seed the database with `php artisan db:seed`.

If you encounter permissions errors you might want to `chown -R www-data:www-data /app` and `chmod -R o+w /app` to ensure the webserver can write to the storage folder.

### Run unit tests

#### First run

On first run you might need to setup the testing sqlite database first.
Run the command `php artisan migrate --database sqlite-testing` to setup the database.

#### Test

To run the PHP unit tests use:

```sh
php artisan test
```

To create new tests, you can use the helper, which by default creates feature tests rather than unit tests:

```sh
php artisan make:test
```

The tests run with the local SQLite test database, not your development database.

#### Quicker feedback during development

[Install the `pre-commit` project](https://pre-commit.com/#install) locally and you'll be able to take advantage of our pre-commit hooks.
They help you keep formatting consistent and avoid mistakes that'll be caught by the continous integration tests.

!!! tip
    This isn't required, but recommended to get an improved feedback loop while developing.

### Run formatting

To run the formatting script that ensures consistent Laravel PHP code use `./vendor/bin/pint`.

## Conventions

Contributions are much appreciated to help everyone evolve this service with fixes and functionalities. We recommend you to fork this repository here on GitHub so you can easily create pull requests back to the main project.

To keep a collaborative project in the same style and understandable, it's important to follow some conventions:

### GitHub Branches

We name branches with `feat/name-here`, `fix/name-here` or `misc/name-here`, for instance `feat/new-api` or `fix/mentor-mail`

### Models/SQL

* MySQL tables are named in plural e.g `training_reports`, not `training_report`
* Models are named in singular e.g. `Training`, not `Trainings`
* Models names don't have any specific suffix or prefix
* Models are located in root of `App/Models` folder.

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

### Basics

This project has a prerequisite that you're familiar with PHP and Object-oriented programming. If you're unfamiliar with Laravel, check out these resources:

* [Laravel Documentation](https://laravel.com/docs)
* [Free and high-quality Laravel 8 tutorial](https://laracasts.com/series/laravel-8-from-scratch)
* [Laravel essentials in 45 min](https://www.youtube.com/watch?v=ubfxi21M1vQ)

### Workspace

Everyone has their own setup for workspace, but if you're new to Laravel and this project, check out our recommendations:

1. Use [Visual Studio Code](https://code.visualstudio.com/) as editor.
2. Install the [following plugins](https://medium.com/@rohan_krishna/how-to-setup-visual-studio-code-for-laravel-php-276643c3013c) in VS Code.
3. Optionally install [this icon pack](https://marketplace.visualstudio.com/items?itemName=PKief.material-icon-theme) for some smooth icons.
4. Learn tips and tricks for PHP and Laravel in VS Code in [these Laracasts](https://laracasts.com/series/visual-studio-code-for-php-developers/)

## Contributing to the documentation { data-toc-label="Documentation" }

To get started with the documentation locally you will need [Python] and [PDM] installed.
The docs are generated and pushed to the `gh-pages` branch.

To build and preview the documentation locally, here's a quick start set of instructions:

```sh title="Getting started with docs tools"
cd docs/  # (1)!
pdm install  # (2)!
```

1. Enter the documentation folder.
2. Install the dependencies for the documentation setup.

You'll now need to run two different commands, one in the background and one in the foreground.
Open two terminals in the same `docs/` folder:

```sh title="Viewing the documentation locally"
pdm run docs:serve
```

This will start a local web server, allowing you to view the current documentation via [`localhost:8000`](http://localhost:8000/dev/), together with the documentation you build locally.

Proceed to the next step after you've made changes to the documentation.

```sh title="Building the documentation locally"
pdm run docs:build
```

Re-run `pdm run mike deploy dev` whenever you've made any changes to the documentation.
The changes will only be visible in `/dev/`.

You can also create multiple versions in parallel by replacing `dev` with something else.

  [Python]: https://www.python.org/
  [PDM]: https://github.com/pdm-project/pdm

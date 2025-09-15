---
icon: material/file-code
---

# Contributing

Interested in contributing to Control Center? Adding a new, favourite feature? Say no more.

## Running the development environment { data-toc-label="Development environment" }

This part will explain how to create a development instance of Control Center in a docker container. First you can choose between `docker-compose.dev.yaml` or `docker-compose.dev.full.yaml`. The difference is that the full version includes a MySQL database and Redis. Secondly both of the files bind the whole application to your project folder, so you can edit the files locally and they'll be updated in the container.

### Setup the container

1. Run `docker compose -f yourchosenfile.yaml up -d` from your host system
2. Wait a bit while the docker image is built for your system
3. Enter your container and run `composer install ` to install all dependencies
4. Install npm by running `bash container/install-npm.sh` as it's not included by default
5. Run `npm install` to install all dependencies
6. Run `npm run build` to compile the assets
7. Run `php artisan migrate` to setup the database

If you need test data, you can also seed the database with `php artisan db:seed`.

If you encounter permissions errors you might want to `chown -R www-data:www-data /app` and `chmod -R o+w /app` to ensure the webserver can write to the storage folder. We recommend doing all file changes inside the container to minimize permission issues.

### Tooling

If you'd like better editor integration, you can generate helper definitions for Laravel.

```sh
php artisan ide-helper:generate
```

### Caching

This application uses the OPCache to cache the compiled PHP code. The default setting is for production use, which means that the cache is not cleared automatically. To clear the cache, you need to restart the container if you change a file.

For development, change `validate_timestamps` to `1` in the `/usr/local/etc/php/php.ini` file to make sure that the cache is cleared automatically when a file is changed.

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

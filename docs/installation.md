# Installation

<!-- There's a few fancy additions to the headers with HTML -->
<!-- markdownlint-disable no-inline-html -->

Control Center is built using [Laravel 10][laravel] and supports PHP 8.1+.

## Install Control Center { data-toc-label="Install" }

### from container <small>recommended :material-check-circle:</small>  { #from-container data-toc-label="from container" }

- A Docker environment to deploy containers. We recommend [Portainer](https://www.portainer.io/).
- A MySQL compatible database to store data.
- Preferably a reverse proxy setup if you plan to host more than one website on the same server.

In the instructions where we use `docker exec`, we assume your container is named `control-center`. If you have named it differently, please replace this.

Open up a terminal and pull the latest image with:

<!-- TODO: Add a common variable with the latest version? Make release-please update it? -->

```shell
docker pull ghcr.io/vatsim-scandinavia/control-center:v4
```

If you're not familiar with Docker, don't worry. We'll walk you through the initial setup.

### from source <small>not recommended</small> { #from-source data-toc-label="from source" }

If you don't want to use Docker, you need:

- An environment that can host PHP websites, such as Apache, Nginx or similar.
- A MySQL compatible database to store data.
- Comply with [Laravel 10 Requirements][laravel-requirements].

In addition, you must handle the following additional tasks:

- Manually build the composer and npm project.
- Configure cron jobs.
- Manually clear all all caches on updates.

## Next steps

With either a container-based installation or the source code, you can now configure the [essential parts of the Control Center instance](configuration/index.md).

  [laravel]: https://laravel.com
  [laravel-requirements]: https://laravel.com/docs/10.x/deployment#server-requirements

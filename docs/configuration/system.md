# Configure the instance

??? tip "Upgrading from v3.4 to v4.0?"
    Read the [UPGRADE.md](https://github.com/Vatsim-Scandinavia/controlcenter/blob/v4.0.7/UPGRADE.md) in v4.0 documentation for details about breaking changes and migration notes.

??? info "Using a container"
    These instructions assume you're using a container. Although most steps are the same if you're running from source, some passages or instructions might not make as much sense.

We've assumed that you've [fetched Control Center and installed it](../installation.md). If you haven't, [now is the time](../installation.md).


--8<-- "exec-in-container.md"

## 1. Initial database setup { data-toc-label="Database" }

??? info "Database not included"
    A MySQL-compatible database is not included in the container or in the source code. You must run your own database, whether separately, locally or with container orchestrators such as Docker Compose.

First make sure that you've started Control Center in the background or you are in the folder with the source.

Run the migration script:

=== "docker"

    ```sh
    docker exec -it control-center php artisan migrate
    ```

=== "docker compose"

    ```sh
    docker compose exec control-center php artisan migrate
    ```

=== "source"

    ```sh
    php artisan migrate
    ```

## 2. Create application key { data-toc-label="Application key" }

To secure your application, as well as prevent users from needing to log in between restarts, you must create and expose an application key to your Control Center instance.

=== "docker"

    ```sh
    docker exec -it control-center php artisan key:get
    ```

=== "docker compose"

    ```sh
    docker compose exec control-center php artisan key:get
    ```

    Session storage needs to be configured in your `docker-compose.yml` by adding a volume.

=== "source"

    ```sh
    php artisan key:get
    ```

    Note that sessions will be stored locally in the source folder.
    To keep sessions across different installations, it is recommended to either mount or symlink them to a different location.

Copy the key and set it as the `APP_KEY` environment variable in your environment variable configuration.

!!! critical "Sensitive information"
    The application key is a secret string which must be kept confidential.

## 3. Mount sessions & file storage { data-toc-label="Persistent files" }

Unless otherwise configured, Control Center stores sessions and uploaded files locally in text files. These must be available across restarts for Control Center to remember previously logged-in users.

### Sessions

=== "docker"

    ```sh
    docker volume create controlcenter_sessions
    ```

    While possible, it is much easier to bind the volume to the container by recreating it.
    The container must be recreated to bind the volume when creating the container with `controlcenter_sessions:/app/storage/framework/sessions`.

    ```sh
    docker run [other arguments] --volume controlcenter_sessions:/app/storage/framework/sessions control-center
    ```

=== "docker compose"

    Add an *additional* volume to Control Center in your `docker-compose.yml` to configure session storage. It's possible to choose between a new volume and a volume mount to the host filesystem.

    ```yaml
    # ...
    services:
      control-center:
       # other control-center settings not included
        volumes:
          - controlcenter_sessions:/app/storage/framework/sessions
    # ...
    volumes:
      controlcenter_sessions:
        driver: local
    ```

=== "source"

    Installations from source will by default store sessions locally in `storage/framework/sessions`.
    To keep sessions across different installations, it is recommended to either mount or symlink them to a different location.

### Uploaded files

To keep uploaded files between deployments, you need to keep the upload folder and its files available to Control Center across deployments.

=== "docker"

    Bind the uploaded files folder directly to a location on the host filesystem. Again, while possible, it is much easier to bind the volume to the container by recreating it:

    ```sh
    docker run [other arguments] --volume /YOUR/HOST/LOCATION:/app/storage/app/public/files control-center
    ```

=== "docker compose"

    Add an *additional* volume mount to Control Center in your `docker-compose.yml`.

    ```yaml
    volumes:
      - /YOUR/SAFE/HOST/LOCATION:/app/storage/app/public/files
    ```

    Ensure correct file permissions after bringing the Compose stack down and up again:

    ```sh
    docker compose exec control-center chown -R www-data:www-data /app/storage/app/public/files
    ```

=== "source"

    Installations from source will by default store uploaded files locally in `storage/app/public/files`. To keep uploaded files safe, it is *strongly* recommended to either mount or symlink them to a different location with sufficient backups.

    Ensure the file permissions on your system are correct. For example, if you're running with Apache2 on Ubuntu you might need to change the ownership of the `storage/app/public/files` to `www-data:www-data`.


## 4. Background jobs { data-toc-label="Background jobs" }

For [background jobs to run](../background-jobs.md) there needs to be something that *makes* them run. Control Center is written in PHP and does not come with its own standalone task worker or supervisor.

You must configure a crontab or create a SystemD timer to run the scheduler every minute.

!!! tip "SystemD and crontab"
    We recommend you use SystemD, but a simple crontab (with the schedule `* * * * *`) is also possible.

### Create the SystemD units

Create two units to run Control Center's scheduled tasks. The service is responsible for starting our background jobs, and can be run on-demand, whereas the timer is responsible for starting the service.

```yaml title="/etc/systemd/control-center-tasks.service"
[Unit]
Description=Process Control Center background jobs

[Service]
Type=oneshot
WorkingDirectory=/PATH/TO/CONTROL/CENTER # (1)!
ExecStart=COMMAND # (2)!
```

1. This path should either point to the folder where you have your Docker configuration or your source installation.
2. Insert the relevant command from the **`COMMAND`** tabs underneath.

```yaml title="/etc/systemd/control-center-tasks.timer"
[Unit]
Description=Run Control Center's task scheduler every minute

[Timer]
OnCalendar=*-*-* *:*:00
Persistent=true

[Install]
WantedBy=timers.target
```


=== "docker"

    ```sh title="COMMAND"
    docker exec --user www-data -i control-center php artisan schedule:run
    ```

    This executes the task scheduler inside the running container.

=== "docker compose"

    You can either edit the service to be executed in the folder with your `docker-compose.yml` by editing `WorkingDirectory` above, or you can identify the name of the container and use `docker` directly.

    ```sh title="COMMAND"
    docker exec --user www-data -i control-center php artisan schedule:run
    ```

=== "source"


    You must correctly specify `WorkingDirectory` in `control-center-tasks.service` and identify the path to your PHP installation.

    Here we have assumed you can use `/usr/bin/env` to run your PHP installation:

    ```sh title="COMMAND"
    /usr/bin/env php artisan schedule:run
    ```

### Enable the service timer

Start the service to verify that your service is working. To start the service:

```sh
systemctl start control-center-tasks.service
```

Whether it succeeds or fails, on a healthy system you can read the logs by using `journalctl`:

```sh
journalctl -u control-center-tasks.service
```

Great! You've now tested that background jobs *can* run. Let's enable the timer so it's run every minute.

```sh
systemctl enable --now control-center-tasks.timer
```

Note that we enabled the `.timer` (not the service!) and told SystemD to start it *now*[^systemctl-status].

  [^systemctl-status]: To check the status of the timer (or the service) you can use `systemctl status`:

    ```sh
    systemctl status control-center-tasks.timer
    ```

Great, let's continue on.

## 5. Reverse proxy { data-toc-label="Reverse proxy" }

While not required, Control Center expects that you run a web server or reverse proxy in front of it. For the time being, this is where we recommend you terminate SSL/TLS connections between your instance and your users.

The process for setting up a reverse proxy depends on the software you use and your needs.

Bind the ports `8080` (HTTP) and/or `8443` (HTTPS) port to your reverse proxy.

## 6. Promote a user to admin { data-toc-label="Administrator" }

You're almost there! The only step that remains is to promote the first administrator. You can add multiple administrators, and you can add them at any time.

Find your own VATSIM CID, and have it ready:

=== "docker"

    ```sh
    docker exec -it control-center php artisan user:makeadmin
    ```

=== "docker compose"

    ```sh
    docker compose exec control-center php artisan user:makeadmin
    ```

=== "source"

    ```sh
    php artisan user:makeadmin
    ```

You'll be asked to provide a CID and an area that the user is an administrator in. Assuming you change the areas to ones of your own, selecting the first area means you won't need to repeat this process right after customising your subdivision's areas.


## Next steps

You're done! You can now access *Administration* -> *Settings* from the navigation menu to change the essential division preferences.

You have

- :material-check: setup a database for CC
- :material-check: secured your application instance
- :material-check: secured your users sessions and data
- :material-check: sorted scheduled tasks
- :material-check: considered or added a reverse proxy
- :material-check: a great starting-point to get your division on-board!

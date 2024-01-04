---
title: Quick start
---

# Getting Started with Control Center

Welcome aboard the Control Center express! Whether you're a veteran virtual ATC, experienced mentor, a training director or just spreading your wings, we're excited to help you launch Control Center effortlessly. Let's dive into setting it up using `docker compose`—it's quick, simple, and maybe even fun!

In this getting started guide, we'll do the following.

1. **Install Docker Compose**: First things first, a quick install if you don't have it.

2. **Add the Compose File**: Grab the `docker-compose.yml` file we've prepared.

3. **Run Control Center**: Start Control Center locally and log in.

4. **Be the Admin**: Elevate your demonstration user to administrative rights.

5. **Clear for takeoff**: Voilà! You're now looking at your very own Control Center dashboard.

## Run a local demo version

Before we take off, you'll need Docker and Docker Compose on your system. If they aren't already part of your cockpit, head over to the [Docker website](https://www.docker.com/) and grab the installations. It's a straightforward process that paves the way for a smooth flight.

??? note "Other container runtimes"
    Alternatively, you might be able to use the Docker CLI together with the Compose plugin with other container runtimes. Some of those runtimes include Lima and Podman, both of which can run on macOS or Linux distributions.

### Add the Docker Compose file

The heart of our quick setup is the `docker-compose.yml` file. This little gem contains all the necessary settings to get Control Center airborne. Here's the file you need:

```yaml title="docker-compose.yml"
---
# WARNING: This configuration is only for local demonstration purposes!
version: '3.3'
services:
  control-center:
    image: ghcr.io/vatsim-scandinavia/control-center:v4
    depends_on:
      db-ready:
        condition: service_completed_successfully
    ports:
      - '8080:80'
    environment:
      - DB_HOST=db
      - DB_USERNAME=root
      - DB_PASSWORD=root
      - DB_DATABASE=test
      - OAUTH_ID=443
      - OAUTH_SECRET=FdFHEWwYU2QBNUd9x5O6SlY2mFdnxv2AqitfD9pD # (1)!
      - OAUTH_URL="https://auth-dev.vatsim.net"
    command: >-
      sh -c "php artisan migrate --force; exec apache2-foreground"
  db:
    image: docker.io/library/mariadb:11
    ports:
      - 3306:3306
    environment:
      MARIADB_DATABASE: test
      MARIADB_ROOT_PASSWORD: root
  db-ready:
    image: atkrad/wait4x
    command: mysql "root:root@tcp(db)/test" -t 10s -i 500ms
```

1.  This uses a pre-existing OAuth2 configuration publicly available at VATSIM's [development and testing OAuth server][auth-dev].

    **If you can't log in**, first check that you haven't accessed the service via an IP address or a different port.
    If you still can't log in, you might need to log in to the [authentication server][auth-dev] and verify that the client ID and secret are correct.

Save this file in your preferred directory; it's your ticket to a hassle-free launch.

  [auth-dev]: https://auth-dev.vatsim.net

### Launching Control Center

With your `docker-compose.yml` file ready, open your terminal and steer to the directory where the file is docked. Then, initiate the launch sequence with:

```shell
docker compose up
```

This command lifts Control Center into the digital stratosphere, setting up everything you need in a matter of minutes.

!!! info
    If you're running an older version of Docker or another runtime, replace `docker compose` with `docker-compose`.

### Accessing Your Dashboard

Your Control Center dashboard is now just a click away. Fire up your web browser and navigate to [`http://localhost:8080`](http://localhost:8080).

Log in to the testing authentication portal using the CID **`10000010`** and password **`10000010`**. You'll (hopefully!) be lining up on the dashboard.
The dashboard will greet you, ready to manage your control ~~zone~~ center.

??? note "New to Control Center?"
    If you're just starting out, don't fret. Control Center is designed to be intuitive and user-friendly. We've got plenty of guides and resources to help you navigate this exciting journey.

### Elevating to Admin Status

Congratulations on successfully logging into your Control Center! As a first-time user, you'll initially have standard user permissions. To fully harness the power of Control Center and manage your virtual airspace effectively, you'll need to elevate your account to admin status. This process is simple and requires just one command.

Open the terminal in the same folder where you ran `docker compose` and execute the following command.

```shell
docker compose exec control-center php artisan user:makeadmin
```

1. **Confirm the Change**: The script will ask for a VATSIM CID. Enter the demo CID `10000010`.

2. **Select an Area**: Select one of the pre-defined areas. The script will confirm that your account has been elevated to admin status.

3. **Enjoy Full Access**: Log back into the Control Center. You should now have full administrative access, allowing you to manage settings, users, and all aspects of the virtual air traffic control environment.

??? note "Need Help?"
    If you encounter any issues while elevating your account to admin, don't hesitate to reach out to us on Discord. We're here to make your journey with Control Center smoother and enjoyable.

By becoming an admin, you unlock the full potential of Control Center, giving you the power to shape and manage your virtual training just the way you want. Ready to take control? Let's fly high!

## Ready for the Next Step?

You've successfully started Control Center using Docker Compose! But wait, there's more. Head over to [our installation guide](installation.md) to explore detailed setup options, configurations, and to [dive deeper into the capabilities of Control Center](concepts/index.md). Your adventure in virtual air traffic management is just beginning, and we're here to guide you every step of the way.

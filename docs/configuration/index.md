# Initial Configuration

<!-- As this document is divided into different sections, there will be duplicate headers -->
<!-- markdownlint-disable no-duplicate-heading -->

To get Control Center running and have it reflect your division as you wish, there are several environment variables you must customise.

## Setting up the environment { #environment }

You may start the container with `docker-compose.yaml` from the root folder, but fill it out with the required variables first.

## Environment variables

The full list of environment variables and defaults lives in [Reference: Environment Variables](../reference/environment-variables.md).
The table section is included below for convenience.

### Configure core settings

--8<-- "reference/environment-variables.md:env-vars-basics"

### Configure database

--8<-- "reference/environment-variables.md:env-vars-database"

### Configure authentication

--8<-- "reference/environment-variables.md:env-vars-authentication"

### Configure VATSIM integration { #vatsim }

--8<-- "reference/environment-variables.md:env-vars-vatsim"

### Configure mail & notifications

--8<-- "reference/environment-variables.md:env-vars-mail"

### Configure reverse proxy

--8<-- "reference/environment-variables.md:env-vars-proxying"

## Optional: Configuring alternative OAuth providers { #authentication data-toc-label="Optional: Alternative OAuth" }

Control Center supports both VATSIM Connect, [Handover](https://github.com/Vatsim-Scandinavia/handover) and other OAuth providers to authenticate and fetch user data. If you're looking for a centrailised login system check out our [Handover](https://github.com/Vatsim-Scandinavia/handover) service, or use VATSIM Connect.

If you want to use a custom Oauth provider, see [how to configure authentication in-full](../setup/authentication.md).

## Optional: Extras

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_DEBUG | false | Toggle debug mode of your Control Center |
| APP_TRACKING_SCRIPT | null | Input javascript here with your tracking script, e.g. Google Analytics |
| DEBUGBAR_ENABLED | false | Toggle debug bar of your Control Center |
| SESSION_LIFETIME | 120 | Session lifetime in minutes, forces a new login when passed |
| SENTRY_LARAVEL_DSN | null | The Sentry DSN |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 | The Sentry sample rate |

## Next steps

With a configured instance of Control Center, it is time to [complete the installation on the system Control Center is running](./system.md).

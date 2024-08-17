# Initial Configuration

<!-- As this document is divided into different sections, there will be duplicate headers -->
<!-- markdownlint-disable no-duplicate-heading -->

To get Control Center running and have it reflect your division as you wish, there are several environment variables you must customise.

## Setting up the environment { #environment }

You may start the container with `docker-compose.yaml` from the root folder, but fill it out with the required variables first.

## Environment variables

Tables with all the variables, default value and explanation. Override the environment variable to change the value if the default value does not fit your needs.

### Basics

Settings related to URLs, subdivision names and other core application settings.

| Variable               | Default value      | Explanation                                                |
|------------------------|--------------------|------------------------------------------------------------|
| `APP_NAME`             | Control Center     | What you want to call the software                         |
| `APP_OWNER_NAME`       | Subdivision Name   | Full name of your subdivision such as `VATSIM Scandinavia` |
| `APP_OWNER_NAME_SHORT` | SCA                | Short name of choice for your vACC e.g. `VATSCA`/`ESTVACC` |
| `APP_OWNER_CODE`       | SCA                | 3-4 letter name identifying your vACC within VATSIM APIs   |
| `APP_URL`              | `http://localhost` | URL to your Control Center without slash at the end        |
| `APP_ENV`              | production         | Environment of your Control Center                         |

### Database

!!! important
    The database settings must be valid to get access to Control Center.

Settings related to configuring the database connection.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| `DB_CONNECTION` | mysql | Database connection type |
| `DB_HOST` | localhost | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_DATABASE` | control-center | Database name |
| `DB_USERNAME` | root | Database username |
| `DB_PASSWORD` | root | Database password |
| `DB_TABLE_PREFIX` | null | Database table prefix |

### Authentication (VATSIM/Handover)

!!! important
    The authentication settings must be valid to get access to Control Center.

Settings related to configuring OAuth-based authentication adhering to the fields supported by VATSIM and [Handover](../integrations/handover.md).

| Variable | Default value | Explanation |
| ------- | --- | --- |
| `OAUTH_URL` | `https://auth.vatsim.net` | OAuth URL of VATSIM |
| `OAUTH_ID` | null | OAuth ID of your subdivision |
| `OAUTH_SECRET` | null | OAuth secret of your subdivision |

!!! tip "Other OAuth providers"
    It's possible to setup your [own OAuth provider with Control Center](../setup/authentication.md).

### VATSIM

!!! important
    The VATSIM settings must be valid for the membership tasks and bookings to function.

Settings related to the [VATSIM integration](../integrations/vatsim.md).

| Variable                   | Default value                         | Explanation                                                                                            |
|----------------------------|---------------------------------------|--------------------------------------------------------------------------------------------------------|
| `VATSIM_CORE_API_TOKEN`    | null                                  | API token (v2) to VATSIM Core API                                                                      |
| `VATSIM_BOOKING_API_URL`   | `https://atc-bookings.vatsim.net/api` | URL to VATSIM ATC Bookings API                                                                         |
| `VATSIM_BOOKING_API_TOKEN` | null                                  | API token to VATSIM ATC Bookings API                                                                   |

### Mail

Settings related to mail notifications, an important aspect of [supporting training](../concepts/training.md).

| Variable | Default value | Explanation |
| ------- | --- | --- |
| `MAIL_MAILER` | smtp | Mailer type |
| `MAIL_HOST` | smtp.mailgun.org | Mail host |
| `MAIL_PORT` | 587 | Mail port |
| `MAIL_USERNAME` | null | Mail username |
| `MAIL_PASSWORD` | null | Mail password |
| `MAIL_ENCRYPTION` | null | Mail encryption |
| `MAIL_FROM_NAME` | Control Center | Mail from name |
| `MAIL_FROM_ADDRESS` | `noreply@yourvacc.com` | Mail from address |

### Proxying

Settings related to reverse proxies in front of Control Center.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| `TRUSTED_PROXIES` | null | Comma-separated list of trusted proxy addresses or '*' for all |

## Configuring authentication with OAuth { data-toc-label="Configure authentication" }

Control Center supports both VATSIM Connect, [Handover](https://github.com/Vatsim-Scandinavia/handover) and other OAuth providers to authenticate and fetch user data. If you're looking for a centrailised login system check out our [Handover](https://github.com/Vatsim-Scandinavia/handover) service, or use VATSIM Connect.

> **Note:**
> No explicit configuration is required for VATSIM Connect or Handover.

If you use your own custom Oauth provider, you need to configure the following variables.

### Environment variables

| Variable | Your OAuth provider array path | Explanation |
| ------- | --- | --- |
| OAUTH_MAPPING_CID | data-id | OAuth mapping of VATSIM CID |
| OAUTH_MAPPING_EMAIL | data-email | OAuth mapping of VATSIM e-mail |
| OAUTH_MAPPING_FIRSTNAME | data-first_name | OAuth mapping of VATSIM first name |
| OAUTH_MAPPING_LASTNAME | data-last_name | OAuth mapping of VATSIM last name |
| OAUTH_MAPPING_RATING | data-vatsim_details-controller_rating-id | OAuth mapping of VATSIM rating |
| OAUTH_MAPPING_RATING_SHORT | data-vatsim_details-controller_rating-short | OAuth mapping of VATSIM rating short |
| OAUTH_MAPPING_RATING_LONG | data-vatsim_details-controller_rating-long | OAuth mapping of VATSIM rating long |
| OAUTH_MAPPING_REGION | data-vatsim_details-region | OAuth mapping of VATSIM region |
| OAUTH_MAPPING_DIVISION | data-vatsim_details-division | OAuth mapping of VATSIM division |
| OAUTH_MAPPING_SUBDIVISION | data-vatsim_details-subdivision | OAuth mapping of VATSIM subdivision |

## Optional: Extras

| Variable | Default value | Explanation |
| ------- | --- | ---
| APP_DEBUG | false | Toggle debug mode of your Control Center |
| APP_TRACKING_SCRIPT | null | Input javascript here with your tracking script, e.g. Google Analytics |
| DEBUGBAR_ENABLED | false | Toggle debug bar of your Control Center |
| SESSION_LIFETIME | 120 | Session lifetime in minutes, forces a new login when passed |
| SENTRY_LARAVEL_DSN | null | The Sentry DSN |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 | The Sentry sample rate |

## Next steps

With a configured instance of Control Center, it is time to [complete the installation on the system Control Center is running](./system.md).

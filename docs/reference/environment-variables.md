---
icon: material/cog
---

# Environment Variables

This page documents the supported environment variables and their defaults.

## Basics

<!-- --8<-- [start:env-vars-basics] -->
Settings related to URLs, subdivision names and other core application settings.

| Variable               | Default value      | Explanation                                                  |
| ---------------------- | ------------------ | ------------------------------------------------------------ |
| `APP_NAME`             | Control Center     | What you want to call the software                           |
| `APP_OWNER_NAME`       | (sub)division Name | Full name of your (sub)division such as `VATSIM Scandinavia` |
| `APP_OWNER_NAME_SHORT` | SCA                | Short name of choice for your vACC e.g. `VATSCA`/`ESTVACC`   |
| `APP_OWNER_CODE`       | SCA                | 3-4 letter name identifying your vACC within VATSIM APIs     |
| `APP_MODE`             | subdivision        | Select correct logic mode `subdivision` or `division`        |
| `APP_URL`              | `http://localhost` | URL to your Control Center without slash at the end          |
| `APP_ENV`              | production         | Environment of your Control Center                           |
<!-- --8<-- [end:env-vars-basics] -->

## Database

<!-- --8<-- [start:env-vars-database] -->
!!! important
    The database settings must be valid to get access to Control Center.

Settings related to configuring the database connection.

| Variable          | Default value  | Explanation              |
| ----------------- | -------------- | ------------------------ |
| `DB_CONNECTION`   | mysql          | Database connection type |
| `DB_HOST`         | localhost      | Database host            |
| `DB_PORT`         | 3306           | Database port            |
| `DB_DATABASE`     | control-center | Database name            |
| `DB_USERNAME`     | root           | Database username        |
| `DB_PASSWORD`     | root           | Database password        |
| `DB_TABLE_PREFIX` | null           | Database table prefix    |
<!-- --8<-- [end:env-vars-database] -->

## Authentication

<!-- --8<-- [start:env-vars-authentication] -->
!!! important
    The authentication settings must be valid to get access to Control Center.

Settings related to configuring OAuth-based authentication adhering to the fields supported by VATSIM and [Handover](../integrations/handover.md).

| Variable       | Default value             | Explanation                      |
| -------------- | ------------------------- | -------------------------------- |
| `OAUTH_URL`    | `https://auth.vatsim.net` | OAuth URL of VATSIM              |
| `OAUTH_ID`     | null                      | OAuth ID of your subdivision     |
| `OAUTH_SECRET` | null                      | OAuth secret of your subdivision |
<!-- --8<-- [end:env-vars-authentication] -->

### Custom OAuth provider

<!-- --8<-- [start:env-vars-oauth-mapping] -->
When you use a custom OAuth provider, configure mapping variables so Control Center can map provider fields to expected VATSIM-style attributes.

| Variable                     | Your OAuth provider array path              | Explanation                          |
| ---------------------------- | ------------------------------------------- | ------------------------------------ |
| `OAUTH_MAPPING_CID`          | data-id                                     | OAuth mapping of VATSIM CID          |
| `OAUTH_MAPPING_EMAIL`        | data-email                                  | OAuth mapping of VATSIM e-mail       |
| `OAUTH_MAPPING_FIRSTNAME`    | data-first_name                             | OAuth mapping of VATSIM first name   |
| `OAUTH_MAPPING_LASTNAME`     | data-last_name                              | OAuth mapping of VATSIM last name    |
| `OAUTH_MAPPING_RATING`       | data-vatsim_details-controller_rating-id    | OAuth mapping of VATSIM rating       |
| `OAUTH_MAPPING_RATING_SHORT` | data-vatsim_details-controller_rating-short | OAuth mapping of VATSIM rating short |
| `OAUTH_MAPPING_RATING_LONG`  | data-vatsim_details-controller_rating-long  | OAuth mapping of VATSIM rating long  |
| `OAUTH_MAPPING_REGION`       | data-vatsim_details-region                  | OAuth mapping of VATSIM region       |
| `OAUTH_MAPPING_DIVISION`     | data-vatsim_details-division                | OAuth mapping of VATSIM division     |
| `OAUTH_MAPPING_SUBDIVISION`  | data-vatsim_details-subdivision             | OAuth mapping of VATSIM subdivision  |
<!-- --8<-- [end:env-vars-oauth-mapping] -->

## VATSIM

<!-- --8<-- [start:env-vars-vatsim] -->
!!! important
    The VATSIM settings must be valid for the membership tasks and bookings to function.

Settings related to the [VATSIM integration](../integrations/vatsim.md).

| Variable                   | Default value                         | Explanation                                                                          |
| -------------------------- | ------------------------------------- | ------------------------------------------------------------------------------------ |
| `VATSIM_CORE_API_TOKEN`    | null                                  | API token (v2) to VATSIM Core API                                                    |
| `VATSIM_BOOKING_API_URL`   | `https://atc-bookings.vatsim.net/api` | URL to VATSIM ATC Bookings API                                                       |
| `VATSIM_BOOKING_API_TOKEN` | null                                  | API token to VATSIM ATC Bookings API                                                 |
| `STATSIM_API_URL`          | `https://api.statsim.net/`            | URL to StatSim statistics API (used for ATC activity charts)                         |
| `STATSIM_API_KEY`          | null                                  | API key for StatSim statistics API authentication (required for ATC activity charts) |
<!-- --8<-- [end:env-vars-vatsim] -->

## Mail

<!-- --8<-- [start:env-vars-mail] -->
Settings related to mail notifications, an important aspect of [supporting training](../concepts/training.md).

| Variable            | Default value          | Explanation       |
| ------------------- | ---------------------- | ----------------- |
| `MAIL_MAILER`       | smtp                   | Mailer type       |
| `MAIL_HOST`         | smtp.mailgun.org       | Mail host         |
| `MAIL_PORT`         | 587                    | Mail port         |
| `MAIL_USERNAME`     | null                   | Mail username     |
| `MAIL_PASSWORD`     | null                   | Mail password     |
| `MAIL_ENCRYPTION`   | null                   | Mail encryption   |
| `MAIL_FROM_NAME`    | Control Center         | Mail from name    |
| `MAIL_FROM_ADDRESS` | `noreply@yourvacc.com` | Mail from address |
<!-- --8<-- [end:env-vars-mail] -->

## Proxying

<!-- --8<-- [start:env-vars-proxying] -->
Settings related to reverse proxies in front of Control Center.

| Variable          | Default value | Explanation                                                    |
| ----------------- | ------------- | -------------------------------------------------------------- |
| `TRUSTED_PROXIES` | null          | Comma-separated list of trusted proxy addresses or `*` for all |
<!-- --8<-- [end:env-vars-proxying] -->

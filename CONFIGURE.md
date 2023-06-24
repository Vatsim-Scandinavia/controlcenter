# Configuring

Table of Contents
- [Environment](#environment)
  - [Required](#required)
  - [Optional: Theming](#optional-theming)
  - [Optional: Extras](#optional-extras)
- [Database](#database)

## Environment

Here is a list over all environment variables you may tweak.

### Required

Table with all the variables, default value and explanation. Override the environment variable to change the value if the default value does not fit your needs.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_NAME | Control Center | Name of your subdivision |
| APP_OWNER | Subdivision Name | Name of your subdivision |
| APP_OWNER_SHORT | SCA | Short name of your subdivision |
| APP_URL | http://localhost | URL to your Control Center |
| APP_ENV | production | Environment of your Control Center |
| DB_CONNECTION | mysql | Database connection type |
| DB_HOST | localhost | Database host |
| DB_PORT | 3306 | Database port |
| DB_DATABASE | control-center | Database name |
| DB_USERNAME | root | Database username |
| DB_PASSWORD | root | Database password |
| DB_TABLE_PREFIX | null | Database table prefix |
| OAUTH_URL | https://auth.vatsim.net | OAuth URL of VATSIM |
| OAUTH_ID | null | OAuth ID of your subdivision |
| OAUTH_SECRET | null | OAuth secret of your subdivision |
| VATSIM_BOOKING_API_URL | null | URL to VATSIM Booking API |
| VATSIM_BOOKING_API_TOKEN | null | Token to VATSIM Booking API |
| MAIL_MAILER | smtp | Mailer type |
| MAIL_HOST | smtp.mailgun.org | Mail host |
| MAIL_PORT | 587 | Mail port |
| MAIL_USERNAME | null | Mail username |
| MAIL_PASSWORD | null | Mail password |
| MAIL_ENCRYPTION | null | Mail encryption |
| MAIL_FROM_NAME | Control Center | Mail from name |
| MAIL_FROM_ADDRESS | noreply@yourvacc.com | Mail from address |

#### OAuth

Control Center supports both VATSIM Connect and other OAuth providers to authenticate and fetch user data. If you're looking for a centrailised login system check out our [Handover](https://github.com/Vatsim-Scandinavia/handover) service. Otherwise you may use VATSIM Connect.

If you use Handover, please use the following values:

| Variable | [Handover](https://github.com/Vatsim-Scandinavia/handover) value | Explanation |
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

### Optional: Theming

To change the logo to yours, bind your logo image files to `public/images/logos` and change the following variables:

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_LOGO | vatsca.svg | The logo of your subdivision, located in `public/images/logos` |
| APP_LOGO_MAIL | vatsca-email.png | The logo of your subdivision, located in `public/images/logos` |

To change the colors of your Control Center, change the following variables and run `npm run prod` in the container to rebuild.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| BOOTSTRAP_COLOR_PRIMARY | #1a475f | Primary color of your theme |
| BOOTSTRAP_COLOR_SECONDARY | #484b4c | Secondary color of your theme |
| BOOTSTRAP_COLOR_TERTIARY | #011328 | Tertiary color of your theme |
| BOOTSTRAP_COLOR_INFO | #17a2b8 | Info color of your theme |
| BOOTSTRAP_COLOR_SUCCESS | #41826e | Success color of your theme |
| BOOTSTRAP_COLOR_WARNING | #ff9800 | Warning color of your theme |
| BOOTSTRAP_COLOR_DANGER | #b63f3f | Danger color of your theme |
| BOOTSTRAP_BORDER_RADIUS | 2px | Border radius of your theme |


### Optional: Extras

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_DEBUG | false | Toggle debug mode of your Control Center |
| DEBUGBAR_ENABLED | false | Toggle debug bar of your Control Center |
| APP_TRACKING_SCRIPT | null | Input javascript here with your tracking script, e.g. Google Analytics |
| SENTRY_LARAVEL_DSN | null | The Sentry DSN |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 | The Sentry sample rate |


## Database

Here you find the diffrerent data tables which need to be edited to suit your subdivision. Perform the changes in the same order as this document.

### Areas
In `Areas` table, create one or more areas. This is something student select between when applying for trianing.
| id | name | contact | template_newreq | template_newmentor | template_pretraining |
| ------- | --- | --- | --- |  --- |  --- |
| x | Name | Contact e-mail that will be displayed to students | Can be set in Notification Editor, set NULL | Same as last | Same as last |
| 1 | Norway | training-norway@vatsim-scandinavia.org | NULL | NULL | NULL |

### Ratings
In `Ratings` table, the default VATSIM ratings are present and if applicable, the endorsement ratings.
| id | name | description | vatsim_rating |
| ------- | --- | --- | --- |
| x | Rating Name | Description | The id of vatsim rating this represents, NULL if local endorsement |
| 1 | S1 | Rating required to sit GND position | 2 |
| 8 | MAE MAJOR AIRPORT | Rating required to sit on a selected airport | NULL |

### Ratings in areas
In `area_rating` table, we define which ratings are available for which area for applications. If a rating is only applicable manually through a moderator there's no need to add it here.
| area_id | rating_id | required_vatsim_rating | allow_bundling | hour_requirement | queue_length_low | queue_lenght_high |
| ------- | --- | --- | --- | --- | --- | --- |
| id of area | id of rating | Id of required vatsim rating to apply for the id of this rating in selected area | NULL or 1 (true) if this vatsim rating can be bundled with a GRP VATSIM rating | NULL or number of hours required to apply for this rating | Filled in by automation | Filled in by automation |
| 1 | 2 | NULL | NULL | NULL | NULL | NULL |
| 1 | 3 | 3 | NULL | 50 | NULL | NULL |

### Positions
In `positions` table, we define which positions are possible to book and their restrictions.
| id | callsign | frequency | name | fir | area | rating | mae
| ------- | --- | --- | --- | --- | --- | --- | --- |
| x | The callsign | Name of position showed when booking | optional frequency, not used inside CC for now | Used for filtering in bookings | id of area | vatsim rating id required to book position | Is this a endorsement position?
| 1 | ENBR_TWR | Flesland Tower | NULL | ENOR | 4 | 3 | NULL
| 2 | ENGM_TWR | Gardermoen Tower | NULL | ENOR | 4 | 3 | 1



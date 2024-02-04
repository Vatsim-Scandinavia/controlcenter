
## Database

Here you find the diffrerent data tables which need to be edited to suit your subdivision. Perform the changes in the same order as this document.

### Areas

In `Areas` table, create one or more areas. This is something student select between when applying for trianing.

| id | name | contact | template_newreq | template_newmentor | template_pretraining |
| ------- | --- | --- | --- |  --- |  --- | --- |
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

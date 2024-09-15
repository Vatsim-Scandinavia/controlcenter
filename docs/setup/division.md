
## Database

Here you find the diffrerent data tables which need to be edited to suit your subdivision. Perform the changes in the same order as this document.

### Areas

In `Areas` table, create one or more areas. This is something student select between when applying for trianing.

| id | name | contact | waiting time | template_newreq | template_newmentor | template_pretraining | feedback_url |
| ------- | --- | --- | --- |  --- |  --- | --- |
| x | Name | Contact e-mail that will be displayed to students | String that completes the sentence "Expected waiting time is ..."" | Can be set in Notification Editor, set NULL | Same as last | Same as last | URL to feedback form shown in training completed email |
| 1 | Norway | training-norway@vatsim-scandinavia.org | "6-12 months" | NULL | NULL | NULL | https://forms.gle/your-feedback-form |

### Ratings

In `Ratings` table, the default VATSIM ratings are present and if applicable, the endorsement ratings.

| id | name | description | vatsim_rating | endorsement_type | 
| ------- | --- | --- | --- | --- |
| x | Rating Name | Description | The id of vatsim rating this represents, NULL if local endorsement | NULL or GCAP type: T1, T2 or SC |
| 1 | S1 | Rating required to sit GND position | 2 | NULL |
| 8 | KJFK APP | Endorsement required to sit on a selected airport | NULL | T1 |

!!! warning
    Please do not remove the default VATSIM ratings (S1-I3), as they are used for the VATSIM integration.

### Ratings in areas

In `area_rating` table, we define which ratings are available for which area for applications. If a rating is only applicable manually through a moderator there's no need to add it here.

| area_id | rating_id | required_vatsim_rating | allow_bundling | hour_requirement | queue_length_low | queue_lenght_high |
| ------- | --- | --- | --- | --- | --- | --- |
| id of area | id of rating | Id of required vatsim rating to apply for the id of this rating in selected area | NULL or 1 (true) if this vatsim rating can be bundled with a GRP VATSIM rating | NULL or number of hours required to apply for this rating | Filled in by automation | Filled in by automation |
| 1 | 2 | NULL | NULL | NULL | NULL | NULL |
| 1 | 3 | 3 | NULL | 50 | NULL | NULL |

### Positions

In `positions` table, we define which positions are possible to book and their restrictions.

| id | callsign | frequency | name | fir | area | rating | required_facility_rating_id |
| ------- | --- | --- | --- | --- | --- | --- | --- |
| x | The callsign | Name of position showed when booking | optional frequency, not used inside CC for now | Used for filtering in bookings | id of area | vatsim rating id required to book position | rating id of the tiered rating required to book this position
| 1 | ENBR_TWR | Flesland Tower | NULL | ENOR | 4 | 3 | NULL
| 2 | ENGM_TWR | Gardermoen Tower | NULL | ENOR | 4 | 3 | 8

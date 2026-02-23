# Define the division

Use this page when preparing division-specific data after installation.  Go through the changes in this order.

## Database

The following changes must be made manually through the database.

### Areas

In the `areas` table, create one or more areas. Members select these areas when applying for training.

| id  | name   | contact                                                                                 | waiting time                                         | template_newreq                                    | template_newmentor | template_pretraining | feedback_url                                             |
| --- | ------ | --------------------------------------------------------------------------------------- | ---------------------------------------------------- | -------------------------------------------------- | ------------------ | -------------------- | -------------------------------------------------------- |
| x   | Name   | Contact email shown to students                                                         | String that completes "Expected waiting time is ..." | Can be set in Notification Editor, set `NULL` here | Same as previous   | Same as previous     | URL to feedback form shown in training completion emails |
| 1   | Norway | [training-norway@vatsim-scandinavia.org](mailto:training-norway@vatsim-scandinavia.org) | 6-12 months                                          | NULL                                               | NULL               | NULL                 | [Feedback form](https://forms.gle/your-feedback-form)    |

### Ratings

In the `ratings` table, keep the default VATSIM ratings and add local endorsement ratings where needed.

| id  | name        | description                                       | vatsim_rating                                                        | endorsement_type                      |
| --- | ----------- | ------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------- |
| x   | Rating Name | Description                                       | ID of VATSIM rating this row represents, `NULL` if local endorsement | `NULL` or GCAP type: `T1`, `T2`, `SC` |
| 1   | S1          | Rating required to sit GND position               | 2                                                                    | NULL                                  |
| 8   | KJFK APP    | Endorsement required to sit on a selected airport | NULL                                                                 | T1                                    |

!!! warning
    Do not remove the default VATSIM ratings (`S1`-`I3`), as they are used by VATSIM integrations.

### Ratings in areas

In the `area_rating` table, define which ratings can be requested in each area.
If a rating is only granted manually by staff, you do not need to add it here.

| area_id    | rating_id    | required_vatsim_rating             | allow_bundling                                                                | hour_requirement                  | queue_length_low     | queue_length_high    |
| ---------- | ------------ | ---------------------------------- | ----------------------------------------------------------------------------- | --------------------------------- | -------------------- | -------------------- |
| ID of area | ID of rating | Required VATSIM rating ID to apply | `NULL` or `1` (`true`) if this rating can be bundled with a GRP VATSIM rating | `NULL` or required hours to apply | Filled automatically | Filled automatically |
| 1          | 2            | NULL                               | NULL                                                                          | NULL                              | NULL                 | NULL                 |
| 1          | 3            | 3                                  | NULL                                                                          | 50                                | NULL                 | NULL                 |

### Positions

In the `positions` table, we define which positions are possible to book, their restrictions, and use these to connect examinations, bookings, and training sessions.

Manage positions from the positions management page. See [Positions](../concepts/positions.md) for access, workflow, and behavior.

!!! note "Only endorsements require manual database changes"
    Position endorsements/tiers are not yet manageable in the UI and must still be handled manually in the database.

| id  | callsign     | name                                 | frequency                               | fir                            | area       | rating                                     | required_facility_rating_id                                   |
| --- | ------------ | ------------------------------------ | --------------------------------------- | ------------------------------ | ---------- | ------------------------------------------ | ------------------------------------------------------------- |
| x   | The callsign | Name of position showed when booking | Optional frequency, only exposed in API | Used for filtering in bookings | id of area | vatsim rating id required to book position | rating id of the tiered rating required to book this position |
| 1   | ENBR_TWR     | Flesland Tower                       | NULL                                    | ENOR                           | 4          | 3                                          | NULL                                                          |

| 2   | ENGM_TWR     | Gardermoen Tower                     | NULL                                    | ENOR                           | 4          | 3                                          | 8                                                             |

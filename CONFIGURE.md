## Configuring
Here you find the diffrerent data tables which need to be edited to suit your subdivision. Perform the changes in the same order as this document.

##### Areas
In `Areas` table, create one or more areas. This is something student select between when applying for trianing.
| id | name | contact | template_newreq | template_newmentor | template_pretraining |
| ------- | --- | --- | --- |  --- |  --- |
| x | Name | Contact e-mail that will be display to students | Can be set in Notification Editor, set NULL | Same as last | Same as last |
| 1 | Norway | training-norway@vatsim-scandinavia.org | NULL | NULL | NULL |

##### Ratings
In `Ratings` table, the default VATSIM ratings are present and if applicable, the endorsement ratings.
| id | name | description | vatsim_rating |
| ------- | --- | --- | --- |
| x | Rating Name | Description | The id of vatsim rating this represents, NULL if local endorsement |
| 1 | S1 | Rating required to sit GND position | 2 |
| 8 | MAE MAJOR AIRPORT | Rating required to sit on a selected airport | NULL |

##### Ratings in areas
In `area_rating` table, we define which ratings are available for which area.
| area_id | rating_id | required_vatsim_rating | queue_length |
| ------- | --- | --- | --- |
| id of area | id of rating | Id of required vatsim rating to apply for the id of this rating in selected area | Filled in by automation |
| 1 | 2 | NULL | NULL |
| 1 | 3 | 3 | NULL |

##### Ratings in areas
In `area_rating` table, we define which ratings are available for which area.
| area_id | rating_id | required_vatsim_rating | queue_length |
| ------- | --- | --- | --- |
| id of area | id of rating | Id of required vatsim rating to apply for the id of this rating in selected area | Filled in by automation |
| 1 | 2 | NULL | NULL |
| 1 | 3 | 3 | NULL |

##### Positions
In `positions` table, we define which positions are possible to book and their restrictions.
| id | callsign | name | fir | area | rating | mae
| ------- | --- | --- | --- | --- | --- | --- |
| x | The callsign | Name of position showed when booking | Used for filtering in bookings | id of area | id of rating | Is this a MAE position?
| 1 | ENBR_TWR | Flesland Tower | ENOR | 4 | 3 | NULL
| 2 | ENGM_TWR | Gardermoen Tower | ENOR | 4 | 3 | 1
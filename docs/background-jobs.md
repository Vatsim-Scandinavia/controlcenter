---
icon: material/table-refresh
---

To support all the functionality, Control Center relies on scheduled background jobs for certain tasks.
These tasks must run regularly for Control Center to work as expected.

## Scheduled tasks

A selection of important scheduled tasks include:

- All trainings with status In Queue or Pre-Training are given a continued interest request each month, and a reminder after a week.
- ATC Active is flag given based on ATC activity. Refreshes daily with data from VATSIM Data API. It counts the hours from today's date and backwards according to the length of qualification period.
- Daily member cleanup, if a member leaves the division, their training will be automatically closed. Same for mentors. Does not apply to visitors.

---
icon: material/pulse
---

# ATC Activity

Control Center automatically tracks the controlling activity of your members to determine their active status. This status (`atc_active`) is critical as it influences roster management, API synchronizations, and potentially endorsement validity.

## How it works

The activity tracking system runs in two distinct phases via background jobs.

### 1. Data Collection

This job runs periodically to fetch raw session data.

- **Source**: It queries the public **VATSIM Data API** to retrieve ATC sessions for your members. No personal tokens are required for this.
- **Scope**: It looks back a configurable number of months defined by the `atcActivityQualificationPeriod`.
- **Filtering**: It filters sessions based on the **Callsigns** defined for your Areas (FIRs/ACCs). Only time spent on positions matching your facility's prefixes is counted.
- **Storage**: The accumulated hours are stored per user, per area. Because the API query is already scoped to the qualification window, the stored hours reflect activity within that period.

### 2. Status Evaluation

This job analyzes the collected data to set the `atc_active` flag for each user.

A user is considered **Active** if they meet **either** of the following criteria:

1. **Grace Period**: The user has a `Grace Period` that has not yet expired. How this is evaluated depends on the **Activity Mode**:
    - **Total Hours**: if *any* area has a valid grace period, the user is fully protected.
    - **Per Area**: each area's grace period is evaluated independently.
2. **Minimum Hours**: The user has accumulated enough controlling hours to meet the `atcActivityRequirement` (default: 10 hours) within the qualification period.

If a user fails both checks:

- Their `atc_active` status is set to `false`.
- They receive an **inactivity notification**. In **Total Hours** mode, one notification is sent per user. In **Per Area** mode, a notification is sent for each area that goes inactive.
- If configured, they may be removed from external rosters (see [Division Integrations](integrations/vateud.md)) during the next synchronization.

## Visiting controllers

Visitors participate in local activity tracking without changing their home division or subdivision. The hours collector includes controllers with a currently valid visiting endorsement even when they have no activity record or automatic reactivation is disabled. Only local positions in their endorsed areas contribute to their visitor activity records.

On the first collection for an endorsed area, a visitor receives an activity record even with zero hours. Its initial grace period starts at the visiting endorsement’s `valid_from`, not at the collection date. Subsequent collections and endorsement renewals do not reset an existing grace period. The normal minimum-hours, total/per-area, inactivity and optional reactivation rules then apply. A valid endorsement does not by itself mean that the visitor meets the activity requirements.

Active visitors appear alongside home controllers in local active rosters and API responses, including `onlyAtcActive=true`. `include[]=allUsers` also includes valid visitors; consumers do not need to request endorsements just to discover them. The API retains their actual home membership and does not assign staff roles. External systems such as Discord bots can use the normal activity fields to identify active controllers, while continuing to inspect endorsements for visiting privileges.

Activity never replaces visiting permission. Revoked, expired or not-yet-valid visiting endorsements cannot qualify an external controller as locally active, even if old activity records remain marked active. Activity and API area status are limited to currently endorsed areas. The member-maintenance job preserves eligible visitors and clears activity outside their current permissions. Division roster and facility-endorsement synchronization continue to include valid visitors independently of local activity requirements.

The Visiting Endorsements table shows activity status, local hours in the configured qualification period, last local session and visiting expiry. Hours are a rolling-period measurement, not hours since the endorsement was issued. Historical hours for that endorsement's areas remain visible after expiry to support renewal review. Valid visitors remain selectable for feedback even if they have not yet met the activity requirement.

## Configuration

You can customize the activity logic in **Administration > Settings**:

- **ATC Activity Requirement**: Minimum hours required (default: 10).
- **ATC Activity Qualification Period**: How far back to count hours (default: 12 months).
- **Grace Period Duration**: Default duration for new grace periods (default: 12 months).
- **Activity Mode**: Whether activity is calculated based on **Total Hours** (sum of all areas) or **Per Area** (requiring hours in specific areas to remain active there).
- **Allow Reactivation**: When enabled, members whose `atc_active` status is `false` are included in the data collection job, allowing them to regain active status by accumulating hours. When disabled, only currently-active members have their hours updated.

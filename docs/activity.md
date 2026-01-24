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
- **Storage**: The calculated hours are stored per user, per area.

### 2. Status Evaluation

This job analyzes the collected data to set the `atc_active` flag for each user.

A user is considered **Active** if they meet **either** of the following criteria:

1. **Grace Period**: The user has a valid `Grace Period` set in their profile (e.g., for new members or returning controllers) that has not yet expired.
2. **Minimum Hours**: The user has accumulated enough controlling hours to meet the `atcActivityRequirement` (default: 10 hours) within the qualification period.

If a user fails both checks:

- Their `atc_active` status is set to `false`.
- If configured, they may be removed from external rosters (see [Division Integrations](integrations/vateud.md)) during the next synchronization.

## Configuration

You can customize the activity logic in **Administration > Settings**:

- **ATC Activity Requirement**: Minimum hours required (default: 10).
- **ATC Activity Qualification Period**: How far back to count hours (default: 12 months).
- **Grace Period Duration**: Default duration for new grace periods (default: 12 months).
- **Activity Mode**: Whether activity is calculated based on **Total Hours** (sum of all areas) or **Per Area** (requiring hours in specific areas to remain active there).

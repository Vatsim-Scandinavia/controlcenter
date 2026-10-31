<!-- markdownlint-disable first-line-heading -->

We provide a integration for the VATSIM Europe Division (VATEUD). This calls the VATEUD Core API providing sync for the following functions:

- Mentors
- Examiners
- Tier 1/2 endorsements
- Solo endorsements
- Rating upgrades (via Tasks) 
- Theory test requests (via Tasks)

!!! info "The Source of Truth"
    Control Center works as the source of truth, so it's important that existing data in VATEUD Core is in sync prior to enabling this integration. Sync all your members, endorsements and such within the VATEUD Core portal manually. 

!!! tip "Activate in Settings"
    Remember to enable the *Division API* feature in *Administration* > *Settings*.

## Synchronization Logic

The integration performs automatic synchronization to ensure the VATEUD roster matches your local Control Center database. It is important to understand when and why data is added or removed.

### Roster Sync

This job ensures the list of controllers on the VATEUD roster matches your **Active** members.

- **Adds**:
    - **Active Home Members**: Users with `atc_active = true`.
    - **Visiting Members**: Users with a valid, non-expired `VISITING` endorsement.
- **Removes**:
    - **Inactive Members**: Home members who have failed to meet activity requirements (`atc_active = false`).
    - **Expired Visitors**: Visiting members whose endorsement has expired or been revoked.
    - **Transfers**: Members who have left the subdivision.
- **Keeps regardless**:
    - **Pending rating upgrades**: Members whose [rating upgrade task](../concepts/tasks.md) was completed within the last 30 days.

!!! note "Why pending upgrades are kept"
    Completing a rating upgrade task adds the member to the VATEUD roster immediately, but their rating in Control Center is only refreshed once a day, at 04:00, from your OAuth provider.
    In between, the member reads as OBS locally, and a roster sync would otherwise undo the upgrade that was just granted.
    The 30 day window covers that delay. A member who is upgraded and then suspended stays on the roster until the window lapses.

### Endorsement Sync

This job manages *Tier 1* (T1) and *Tier 2* (T2) facility endorsements.

- **Syncs**: Only checks endorsements for users who are currently **Active** or valid **Visitors**.
- **Removes**: Endorsements are removed from the VATEUD API if:
    - The user becomes **Inactive** (even if they still hold the endorsement locally).
    - The endorsement is **revoked** or **expired** in Control Center.
    - The user loses their **Visiting** status.

!!! warning "Activity is Key"
    If a controller goes inactive locally (due to lack of hours), they will be **removed** from the VATEUD roster and their endorsements will be stripped from the API during the next sync. Ensure your members maintain their [ATC Activity](../activity.md) or have a valid Grace Period.

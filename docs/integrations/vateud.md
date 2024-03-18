<!-- markdownlint-disable first-line-heading -->

We provide a integration for the VATSIM Europe Division (VATEUD). This calls the VATEUD Core API providing sync for the following functions:

- Mentors
- Examiners
- Tier 1/2 endorsements
- Solo endorsements
- Rating upgrades (via Tasks) 
- Theory test requests (via Tasks)

!!! info
    Control Center works as the source of truth, so it's important that existing data in VATEUD Core is in sync prior to enabling this integration. Sync all your members, endorsements and such within the VATEUD Core portal manually. 

!!! tip "Activate in settings"
    Remember turning on the Division API setting in Administration > Settings
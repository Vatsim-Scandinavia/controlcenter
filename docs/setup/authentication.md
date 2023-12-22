# Configuring authentication with OAuth

!!! warning "Only applies if you're not using Handover or VATSIM Connect"
    These settings do not apply if you're using Handover or VATSIM Connect to authenticate your users.

Control Center supports both VATSIM Connect, [Handover](https://github.com/Vatsim-Scandinavia/handover) and other OAuth providers to authenticate and fetch user data.

If you're looking for a centralised authentication service, check out our [Handover](https://github.com/Vatsim-Scandinavia/handover) service, or use VATSIM Connect.

While support for VATSIM Connect and Handover is configured by default, there are additional settings you need to configure for Control Center to work with other OAuth-based identity providers.

!!! tip "Use VATSIM Connect or Handover if possible"
    No explicit configuration is required for VATSIM Connect or Handover.
    Consider using either [VATSIM SSO or Handover for authentication](../configuration/index.md#authentication) by utilising the standard configuration.

## Environment variables

If you want to use your own custom OAuth provider, you need to configure the following variables.

| Variable | Your OAuth provider array path | Explanation |
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

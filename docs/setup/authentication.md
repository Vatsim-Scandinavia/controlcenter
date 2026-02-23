# Configuring authentication with OAuth

Control Center supports both VATSIM Connect, [Handover](https://github.com/Vatsim-Scandinavia/handover) and other OAuth providers to authenticate and fetch user data.

If you're looking for a centralised authentication service, check out our [Handover](https://github.com/Vatsim-Scandinavia/handover) service, or use VATSIM Connect.

!!! tip "Use VATSIM Connect or Handover if possible"
    No explicit configuration is required for VATSIM Connect or Handover.
    Consider using either [VATSIM SSO or Handover for authentication](../configuration/index.md#authentication) by utilising the standard configuration.

## Environment variables

These variables are shared with the [Environment Variables reference](../reference/environment-variables.md).

### Recommended: VATSIM Connect or Handover

--8<-- "reference/environment-variables.md:env-vars-authentication"

### Custom OAuth mapping

!!! warning "Only applies if you're not using Handover or VATSIM Connect"
    These settings do not apply if you're using Handover or VATSIM Connect to authenticate your users.

If you want to use your own custom OAuth provider, you also need to configure mapping variables.

--8<-- "reference/environment-variables.md:env-vars-oauth-mapping"

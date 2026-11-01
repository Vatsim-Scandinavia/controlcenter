# Custom container image

Depending on the changes you'd like to make, you may choose to create a variant of Control Center based on the upstream version.

!!! warning
    Creating a custom variant is limited to people with prior development experience.
    We recommend that **most**, if not all, **users** should use the [standard container image](../installation.md#from-container).

This is the most reliable way to make changes over time, as well as regularly synchronise the changes with the upstream.

## Custom Image

```Dockerfile title="Custom derivation of Control Center"
FROM %%image_tag%%

# Make your customisations here
...
```

!!! tip
    The `v%%major%%` tag follows the latest release of that major version.
    Pin an exact tag, such as `%%version%%`, if you'd rather decide for
    yourself when to pick up a new release.

## Example

### Custom Theme

You can customise the theme by [creating a `_custom.scss` override](./theme.md#customizing-theme) and running `container/theme/build.sh` to recompile the assets:

```Dockerfile title="Custom theme in Control Center"
FROM %%image_tag%%

# Copy your theme override
COPY _custom.scss /app/resources/sass/themes/_custom.scss

# Make the theme build script executable and run it
RUN chmod +x container/theme/build.sh && container/theme/build.sh
```

### Bundling a module

[Modules](../development/modules.md) are installed at build time with Composer.
To add a third-party module to your image, require it and refresh the autoloader.
Modules ship **prebuilt** frontend assets, so the runtime image needs no Node.

```Dockerfile title="Adding a module to Control Center"
FROM %%image_tag%%

# For a module published to a registry:
RUN composer require acme/cc-widget --no-interaction --update-no-dev

# ...or copy a local module and register it as a path repository:
# COPY ./cc-widget /app/modules/widget
# RUN composer config repositories.widget path modules/widget \
#  && composer require acme/cc-widget:@dev --no-interaction --update-no-dev
```

The module is installed but **disabled** until an operator enables it from
**Administration → Modules** or with `php artisan module:enable <slug>`. A module
is trusted, in-process code — vet it as you would any dependency.


# Custom container image

Depending on the changes you'd like to make, you may choose to create a variant of Control Center based on the upstream version.

!!! warning
    Creating a custom variant is limited to people with prior development experience.
    We recommend that **most**, if not all, **users** should use the [standard container image](../installation.md#from-container).

This is the most reliable way to make changes over time, as well as regularly synchronise the changes with the upstream.

## Custom Image

```Dockerfile title="Custom derivation of Control Center"
FROM ghcr.io/vatsim-scandinavia/control-center:latest

# Make your customisations here
...
```

## Example

### Custom Theme

You can customise the theme by [copying your modified theme files and running `/container/theme/build.sh`](./theme.md):

```Dockerfile title="Custom theme in Control Center"
FROM ghcr.io/vatsim-scandinavia/control-center:latest

# Copy your custom theme files
COPY _light.scss /app/resources/sass/themes/_light.scss
COPY _dark.scss /app/resources/sass/themes/_dark.scss

# Make the theme build script executable and run it
RUN chmod +x container/theme/build.sh && container/theme/build.sh
```

# Customise the Control Center theme

To change the bootstrap colors to match your division, first change the environment variables and then run the following command

### Light Theme Variables

| Variable | Default value | Explanation |
| ------- | --- | --- |
| VITE_THEME_PRIMARY | #1a475f | Primary color of your theme |
| VITE_THEME_SECONDARY | #484b4c | Secondary color of your theme |
| VITE_THEME_TERTIARY | #011328 | Tertiary color of your theme |
| VITE_THEME_INFO | #17a2b8 | Info color of your theme |
| VITE_THEME_SUCCESS | #41826e | Success color of your theme |
| VITE_THEME_WARNING | #ff9800 | Warning color of your theme |
| VITE_THEME_DANGER | #b63f3f | Danger color of your theme |
| VITE_THEME_BORDER_RADIUS | 2px | Border radius of your theme |

### Dark Theme Variables

| Variable | Default value | Explanation |
| ------- | --- | --- |
| VITE_THEME_DARK_PRIMARY | #1a475f | Primary color for dark theme |
| VITE_THEME_DARK_SECONDARY | #6b6d6e | Secondary color for dark theme |
| VITE_THEME_DARK_TERTIARY | #e0e0e0 | Tertiary color for dark theme |
| VITE_THEME_DARK_INFO | #5bc0de | Info color for dark theme |
| VITE_THEME_DARK_SUCCESS | #5cb85c | Success color for dark theme |
| VITE_THEME_DARK_WARNING | #f0ad4e | Warning color for dark theme |
| VITE_THEME_DARK_DANGER | #d9534f | Danger color for dark theme |

Run this command to build the theme. If you get an error like `"#5f271a" is not a color`, remove the `"` quotes from your environment variable.

```sh
docker exec -it control-center sh container/theme/build.sh
```

!!! warning "Custom themes increase container size"
    Building custom themes will increase your container size with ~200mb when completed.
    When you run this command, we need to install dependencies inside your container to build the theme and then remove them again.
    This is done to keep the container size down.

!!! danger "Custom themes are temporary"
    You need to run this command each time you recreate the container.
    To avoid this, [create a custom derivation of the container](./custom.md).

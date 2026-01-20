# Themes

Control Center includes a dynamic theme system that allows users to switch between light and dark modes, or follow their system preferences.

## User Theme Selection

Users can choose their preferred theme from their settings page (`/settings`):

- **System Default**: Automatically matches the user's operating system theme preference
- **Light**: Always uses the light theme
- **Dark**: Always uses the dark theme

The theme preference is saved to the database and persists across sessions.

## Available Themes

### Light Theme (Default)
The default theme with a clean, professional appearance:
- White backgrounds
- Dark text for optimal readability
- Blue primary colors

### Dark Theme
A dark mode optimized for low-light environments:
- Dark backgrounds
- Light text for reduced eye strain
- Adjusted colors for better visibility

### System Theme
Automatically switches between light and dark based on:
- Operating system theme preference
- Time of day settings (on supported systems)
- No page reload required when system theme changes

## Customizing Theme Colors
To customize the colors for your division, edit the theme files directly:

### Light Theme Colors

Edit: `resources/sass/themes/_light.scss`

```scss
:root, [data-theme="light"] {
    --color-primary: #1a475f;      // Your primary color
    --color-secondary: #484b4c;    // Your secondary color
    --color-success: #41826e;      // Success/green color
    --color-info: #17a2b8;         // Info/blue color
    --color-warning: #ff9800;      // Warning/orange color
    --color-danger: #b63f3f;       // Danger/red color
    // ... more variables
}
```

### Dark Theme Colors

Edit: `resources/sass/themes/_dark.scss`

```scss
[data-theme="dark"] {
    --color-primary: #4a9cc5;      // Your primary color (adjusted for dark)
    --color-secondary: #6c757d;    // Your secondary color
    // ... more variables
}
```

### Rebuild After Changes

After editing theme files, rebuild the frontend assets:

```sh
npm run build
```

For Docker deployments:
```sh
docker exec -it control-center npm run build
```

## Technical Details

### For Developers

The theme system uses CSS variables for dynamic switching:

- **Theme definitions**: `resources/sass/themes/*.scss`
- **Bootstrap overrides**: `resources/sass/_theme-overrides.scss`
- **JavaScript**: `resources/js/theme.js`

See the root-level `THEME_*.md` files for comprehensive developer documentation.

Older browsers (like IE11) will fall back to the default light theme without switching capability.

## Troubleshooting

### Theme not changing
- Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
- Check JavaScript console for errors
- Verify assets are built: `npm run build`

### Colors look wrong
- Ensure you edited both `_light.scss` and `_dark.scss`
- Rebuild assets after changes
- Check CSS variables in browser DevTools

## Further Reading


- [Custom Container](./custom.md) - Persistent customizations

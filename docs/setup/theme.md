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

!!! info "Changed in v3.x"
    Theme customization now works differently. The old `VITE_THEME_*` environment variables are no longer used.

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

!!! tip "Creating Custom Themes"
    Want to create additional themes (e.g., "high contrast", "sepia")? 
    See the developer documentation in `THEME_CREATING_NEW_THEMES.md` for detailed instructions.

## Theme Features

### Instant Switching
- Themes switch instantly without page reload
- No flash of unstyled content
- Smooth transition between themes

### Persistent Across Sessions
- Theme choice saved to database
- Remembered across devices (for logged-in users)
- LocalStorage backup for immediate loading

### System Integration
- Detects operating system theme preference
- Automatically switches when OS theme changes
- CSS media query based (`prefers-color-scheme`)

### Comprehensive Coverage
All UI components are themed:
- Navigation and sidebar
- Forms and inputs
- Tables and data displays
- Modals and dialogs
- Alerts and notifications
- Buttons and badges
- Code editors (EasyMDE)

## Technical Details

### For Developers

The theme system uses CSS variables for dynamic switching:

- **Theme definitions**: `resources/sass/themes/*.scss`
- **Bootstrap overrides**: `resources/sass/_theme-overrides.scss`
- **JavaScript**: `resources/js/theme.js`
- **Controller**: `app/Http/Controllers/UserController.php`

See the root-level `THEME_*.md` files for comprehensive developer documentation.

### Browser Support

Modern browsers with CSS Custom Properties support:
- Chrome/Edge 88+
- Firefox 85+
- Safari 14+
- Opera 74+

Older browsers (like IE11) will fall back to the default light theme without switching capability.

## Migration from Old Theme System

If you were using the old `VITE_THEME_*` environment variables:

1. **Remove** old environment variables from `.env`:
   - `VITE_THEME_PRIMARY`
   - `VITE_THEME_SECONDARY`
   - `VITE_THEME_TERTIARY`
   - `VITE_THEME_INFO`
   - `VITE_THEME_SUCCESS`
   - `VITE_THEME_WARNING`
   - `VITE_THEME_DANGER`
   - `VITE_THEME_BORDER_RADIUS`

2. **Edit** theme files directly (see "Customizing Theme Colors" above)

3. **Rebuild** frontend assets: `npm run build`

4. **Run** migration: `php artisan migrate`

5. **Clear** browser cache and test

## Troubleshooting

### Theme not changing
- Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
- Check JavaScript console for errors
- Verify assets are built: `npm run build`

### Colors look wrong
- Ensure you edited both `_light.scss` and `_dark.scss`
- Rebuild assets after changes
- Check CSS variables in browser DevTools

### Flash of wrong theme on load
- This should not happen; inline script prevents it
- Check `resources/views/layouts/header.blade.php` has the theme script
- Clear browser localStorage

## Further Reading

- [Creating Custom Themes](../../THEME_CREATING_NEW_THEMES.md) - Developer guide
- [Theme Architecture](../../THEME_ARCHITECTURE_CLEAN.md) - Technical details
- [Custom Container](./custom.md) - Persistent customizations

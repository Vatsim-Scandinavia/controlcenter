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

## Customizing Theme

To brand your instance, put your overrides in `resources/sass/themes/_custom.scss`.
That file is git-ignored, so your styles stay out of version control while still
compiling into the regular front-end build.

### 1. Create the override file

Copy the example to get started:

```sh
cp resources/sass/themes/_custom.scss.example resources/sass/themes/_custom.scss
```

### 2. Edit your colors

Edit `resources/sass/themes/_custom.scss`. Override only what you need.
Anything left out keeps its default.

Reference for the available values:

- `resources/sass/themes/_light.scss` — CSS custom properties for the light theme
- `resources/sass/themes/_dark.scss` — CSS custom properties for the dark theme
- `resources/sass/_variables.scss` — SCSS variables consumed by Bootstrap

### 3. Rebuild the assets

After editing `_custom.scss`, rebuild the frontend assets:

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

Older browsers (like IE11) will fall back to the default light theme without switching capability.

## Troubleshooting

### Theme not changing
- Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
- Check JavaScript console for errors
- Verify assets are built: `npm run build`

### Colors look wrong
- Ensure you edited both the light and dark blocks in `_custom.scss` so the colors match in either mode
- Rebuild assets after changes
- Check CSS variables in browser DevTools

## Further Reading

- [Custom Container](./custom.md) - Persistent customizations

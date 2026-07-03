/**
 * Theme Management System
 * Handles theme switching between light, dark, and system-based themes
 */

(function() {
    'use strict';

    const THEME_STORAGE_KEY = 'user_theme_preference';
    const THEME_ATTRIBUTE = 'data-theme';
    const BS_THEME_ATTRIBUTE = 'data-bs-theme';

    // Single source of truth for the three preferences. Insertion order is also
    // the header toggle's click-cycle order (system -> light -> dark -> system).
    const PREFERENCES = {
        system: { icon: 'fa-desktop', label: 'System' },
        light: { icon: 'fa-sun', label: 'Light' },
        dark: { icon: 'fa-moon', label: 'Dark' },
    };
    const PREFERENCE_ORDER = Object.keys(PREFERENCES);
    const THEME_ICON_CLASSES = PREFERENCE_ORDER.map((preference) => PREFERENCES[preference].icon);

    // Delay before a preference is persisted to the profile, so rapid clicks
    // through the cycle only result in a single request for the final choice.
    const PERSIST_DELAY = 2000;

    // Cached once; `.matches` always reflects the current system setting.
    const DARK_MEDIA_QUERY = window.matchMedia('(prefers-color-scheme: dark)');

    /**
     * Return a debounced wrapper that delays calling fn until `wait` ms after
     * its last invocation.
     * @param {Function} fn
     * @param {number} wait
     * @returns {Function}
     */
    function debounce(fn, wait) {
        let timeout;

        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    /**
     * The stored preference, defaulting to 'system'.
     * @returns {string} - 'light', 'dark', or 'system'
     */
    function getStoredPreference() {
        return localStorage.getItem(THEME_STORAGE_KEY) || 'system';
    }

    /**
     * Resolve a preference to the effective theme.
     * @param {string} preference - 'light', 'dark', or 'system'
     * @returns {string} - 'light' or 'dark'
     */
    function getEffectiveTheme(preference) {
        if (preference === 'system') {
            return DARK_MEDIA_QUERY.matches ? 'dark' : 'light';
        }
        return preference;
    }

    /**
     * Apply theme to the document
     * @param {string} theme - 'light' or 'dark'
     */
    function applyTheme(theme) {
        // data-bs-theme mirrors data-theme (both hold the effective light/dark
        // value) so Bootstrap's dark cascade tracks our own theme attribute.
        document.documentElement.setAttribute(THEME_ATTRIBUTE, theme);
        document.documentElement.setAttribute(BS_THEME_ATTRIBUTE, theme);
    }

    /**
     * Initialize theme on page load
     * Note: Inline script in header has already set initial data-theme to prevent FOUC
     * This function reconciles and sets up dynamic behavior
     */
    function initializeTheme() {
        const storedPreference = localStorage.getItem(THEME_STORAGE_KEY) ||
            document.documentElement.getAttribute('data-user-theme') ||
            'system';

        // Store the preference for consistency
        localStorage.setItem(THEME_STORAGE_KEY, storedPreference);

        // Apply theme (inline script has already done initial set, this ensures correctness)
        applyTheme(getEffectiveTheme(storedPreference));
    }

    /**
     * Update theme preference
     * @param {string} preference - 'light', 'dark', or 'system'
     * @param {boolean} persist - also save to the user profile (debounced)
     */
    function setThemePreference(preference, persist = false) {
        if (!PREFERENCES[preference]) {
            return;
        }

        localStorage.setItem(THEME_STORAGE_KEY, preference);
        applyTheme(getEffectiveTheme(preference));
        updateToggleUI(preference);

        if (persist) {
            debouncedPersist(preference);
        }
    }

    /**
     * Persist the preference to the user profile so it syncs across devices
     * and matches the settings page. Fails silently: localStorage already holds
     * the preference, so the UI stays correct even if the request fails.
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function persistPreference(preference) {
        const token = document.querySelector('meta[name="csrf-token"]');

        if (!token) {
            return;
        }

        // Prefer the server-generated route (handles subdirectory deploys); the
        // literal path is a fallback if the toggle isn't on the page.
        const toggle = document.getElementById('theme-toggle');
        const url = toggle?.dataset.themeUrl || '/settings/theme';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ setting_theme: preference }),
        }).catch(() => { /* preference is kept in localStorage regardless */ });
    }

    // Persist only the final choice a couple of seconds after the last click.
    const debouncedPersist = debounce(persistPreference, PERSIST_DELAY);

    /**
     * Reflect the active preference on the header toggle: swap the icon and
     * label. Driven by the 3-way preference (not the resolved theme) so
     * "system" is visible as its own state.
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function updateToggleUI(preference) {
        const toggle = document.getElementById('theme-toggle');
        const config = PREFERENCES[preference];

        if (!toggle || !config) {
            return;
        }

        const icon = toggle.querySelector('[data-theme-icon]');

        if (icon) {
            // Swap only the theme icon class so sizing/utility classes survive.
            icon.classList.remove(...THEME_ICON_CLASSES);
            icon.classList.add(config.icon);
        }

        toggle.setAttribute('title', `Theme: ${config.label} (click to change)`);
    }

    /**
     * Wire the header theme toggle: each click advances to the next preference
     * (system -> light -> dark -> system).
     */
    function watchThemeToggle() {
        const toggle = document.getElementById('theme-toggle');

        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const current = getStoredPreference();
            const next = PREFERENCE_ORDER[(PREFERENCE_ORDER.indexOf(current) + 1) % PREFERENCE_ORDER.length];
            setThemePreference(next, true);
        });

        // Reflect the current preference on load.
        updateToggleUI(getStoredPreference());
    }

    /**
     * Listen for system theme changes when in system mode
     */
    function watchSystemTheme() {
        DARK_MEDIA_QUERY.addEventListener('change', (e) => {
            // Only react if we're in system mode
            if (getStoredPreference() === 'system') {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    /**
     * Listen for theme changes from settings page
     */
    function watchThemeSelector() {
        const themeSelector = document.getElementById('setting_theme');

        if (themeSelector) {
            themeSelector.addEventListener('change', (e) => {
                setThemePreference(e.target.value);
            });
        }
    }

    /**
     * Attach all runtime listeners. Safe to call once the DOM is ready.
     */
    function setupWatchers() {
        watchSystemTheme();
        watchThemeSelector();
        watchThemeToggle();
    }

    // Initialize theme immediately (before DOM ready to prevent flash)
    initializeTheme();

    // Set up watchers when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupWatchers);
    } else {
        setupWatchers();
    }

    // Expose API for manual theme changes
    window.ThemeManager = {
        setTheme: setThemePreference,
        getTheme: getStoredPreference,
        getEffectiveTheme: () => getEffectiveTheme(getStoredPreference()),
    };

})();

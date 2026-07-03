/**
 * Theme Management System
 * Handles theme switching between light, dark, and system-based themes
 */

(function() {
    'use strict';

    const THEME_STORAGE_KEY = 'user_theme_preference';
    const THEME_ATTRIBUTE = 'data-theme';
    const BS_THEME_ATTRIBUTE = 'data-bs-theme';
    // Also the click-cycle order for the header toggle.
    const VALID_PREFERENCES = ['system', 'light', 'dark'];
    // Delay before a preference is persisted to the profile, so rapid clicks
    // through the cycle only result in a single request for the final choice.
    const PERSIST_DELAY = 2000;

    /**
     * Return a debounced wrapper that delays calling fn until `wait` ms after
     * its last invocation.
     * @param {Function} fn
     * @param {number} wait
     * @returns {Function}
     */
    function debounce(fn, wait) {
        let timeout;

        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    /**
     * Get the effective theme based on preference
     * @param {string} preference - 'light', 'dark', or 'system'
     * @returns {string} - 'light' or 'dark'
     */
    function getEffectiveTheme(preference) {
        if (preference === 'system') {
            // Detect system preference
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
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
        // Get stored preference
        let storedPreference = localStorage.getItem(THEME_STORAGE_KEY) ||
                              document.documentElement.getAttribute('data-user-theme') || 
                              'system';

        // Store the preference for consistency
        localStorage.setItem(THEME_STORAGE_KEY, storedPreference);

        // Apply theme (inline script has already done initial set, this ensures correctness)
        const effectiveTheme = getEffectiveTheme(storedPreference);
        applyTheme(effectiveTheme);
    }

    /**
     * Update theme preference
     * @param {string} preference - 'light', 'dark', or 'system'
     * @param {boolean} persist - also save to the user profile (debounced)
     */
    function setThemePreference(preference, persist = false) {
        if (!VALID_PREFERENCES.includes(preference)) {
            return;
        }

        localStorage.setItem(THEME_STORAGE_KEY, preference);
        const effectiveTheme = getEffectiveTheme(preference);
        applyTheme(effectiveTheme);
        updateToggleUI(preference);

        if (persist) {
            debouncedPersist(preference);
        }
    }

    // Persist only the final choice a couple of seconds after the last click.
    const debouncedPersist = debounce(persistPreference, PERSIST_DELAY);

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

        fetch('/settings/theme', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ setting_theme: preference }),
        }).catch(() => { /* preference is kept in localStorage regardless */ });
    }

    /**
     * Reflect the active preference on the header toggle: swap the icon and
     * label. Driven by the 3-way preference (not the resolved theme) so
     * "system" is visible as its own state.
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function updateToggleUI(preference) {
        const toggle = document.getElementById('theme-toggle');

        if (!toggle) {
            return;
        }

        const icon = toggle.querySelector('[data-theme-icon]');

        if (icon) {
            const iconClass = { system: 'fa-desktop', light: 'fa-sun', dark: 'fa-moon' }[preference];
            icon.className = 'fas fa-fw ' + iconClass;
        }

        const label = { system: 'System', light: 'Light', dark: 'Dark' }[preference];
        toggle.setAttribute('title', 'Theme: ' + label + ' (click to change)');
    }

    /**
     * Wire the header theme toggle: each click advances to the next preference
     * in VALID_PREFERENCES (system -> light -> dark -> system).
     */
    function watchThemeToggle() {
        const toggle = document.getElementById('theme-toggle');

        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const current = localStorage.getItem(THEME_STORAGE_KEY) || 'system';
            const next = VALID_PREFERENCES[(VALID_PREFERENCES.indexOf(current) + 1) % VALID_PREFERENCES.length];
            setThemePreference(next, true);
        });

        // Reflect the current preference on load.
        updateToggleUI(localStorage.getItem(THEME_STORAGE_KEY) || 'system');
    }

    /**
     * Listen for system theme changes when in system mode
     */
    function watchSystemTheme() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            const currentPreference = localStorage.getItem(THEME_STORAGE_KEY) || 'system';
            
            // Only react if we're in system mode
            if (currentPreference === 'system') {
                const effectiveTheme = e.matches ? 'dark' : 'light';
                applyTheme(effectiveTheme);
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
                const newPreference = e.target.value;
                setThemePreference(newPreference);
            });
        }
    }

    // Initialize theme immediately (before DOM ready to prevent flash)
    initializeTheme();

    // Set up watchers when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            watchSystemTheme();
            watchThemeSelector();
            watchThemeToggle();
        });
    } else {
        watchSystemTheme();
        watchThemeSelector();
        watchThemeToggle();
    }

    // Expose API for manual theme changes
    window.ThemeManager = {
        setTheme: setThemePreference,
        getTheme: () => localStorage.getItem(THEME_STORAGE_KEY) || 'system',
        getEffectiveTheme: () => {
            const preference = localStorage.getItem(THEME_STORAGE_KEY) || 'system';
            return getEffectiveTheme(preference);
        }
    };

})();


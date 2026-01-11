/**
 * Theme Management System
 * Handles theme switching between light, dark, and system-based themes
 */

(function() {
    'use strict';

    const THEME_STORAGE_KEY = 'user_theme_preference';
    const THEME_ATTRIBUTE = 'data-theme';

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
        document.documentElement.setAttribute(THEME_ATTRIBUTE, theme);
    }

    /**
     * Initialize theme on page load
     */
    function initializeTheme() {
        // Priority 1: Check localStorage for immediate preference
        let storedPreference = localStorage.getItem(THEME_STORAGE_KEY);
        
        // Priority 2: Check server-side preference from data attribute
        if (!storedPreference) {
            storedPreference = document.documentElement.getAttribute('data-user-theme') || 'system';
        }

        // Apply the effective theme
        const effectiveTheme = getEffectiveTheme(storedPreference);
        applyTheme(effectiveTheme);

        // Store the preference for next time
        localStorage.setItem(THEME_STORAGE_KEY, storedPreference);
    }

    /**
     * Update theme preference
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function setThemePreference(preference) {
        localStorage.setItem(THEME_STORAGE_KEY, preference);
        const effectiveTheme = getEffectiveTheme(preference);
        applyTheme(effectiveTheme);
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
        });
    } else {
        watchSystemTheme();
        watchThemeSelector();
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


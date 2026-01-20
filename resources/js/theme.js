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


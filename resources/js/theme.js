/**
 * Theme Management
 * Handles theme detection, switching, and persistence
 */

class ThemeManager {
    constructor() {
        this.themePreference = null;
        this.currentTheme = null;
        this.init();
    }

    /**
     * Initialize theme on page load
     * Priority: System preference (fastest) -> Backend preference -> localStorage
     */
    init() {
        // Step 1: Immediately apply system preference for instant theme
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.applyTheme(systemPrefersDark ? 'dark' : 'light');

        // Step 2: Check for user preference from backend (injected via meta tag or data attribute)
        const userPreference = this.getUserPreference();
        
        if (userPreference) {
            this.themePreference = userPreference;
            
            if (userPreference === 'system') {
                // Keep system preference
                this.currentTheme = systemPrefersDark ? 'dark' : 'light';
            } else {
                // Apply explicit user preference
                this.currentTheme = userPreference;
            }
            
            this.applyTheme(this.currentTheme);
        } else {
            // Fallback to localStorage if backend data not available
            const storedPreference = localStorage.getItem('theme_preference');
            if (storedPreference) {
                this.themePreference = storedPreference;
                if (storedPreference === 'system') {
                    this.currentTheme = systemPrefersDark ? 'dark' : 'light';
                } else {
                    this.currentTheme = storedPreference;
                }
                this.applyTheme(this.currentTheme);
            }
        }

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (this.themePreference === 'system' || !this.themePreference) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    /**
     * Get user preference from backend (injected in HTML)
     */
    getUserPreference() {
        // Check meta tag first
        const metaTag = document.querySelector('meta[name="theme-preference"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }

        // Check data attribute on html element
        const htmlElement = document.documentElement;
        if (htmlElement.hasAttribute('data-theme-preference')) {
            return htmlElement.getAttribute('data-theme-preference');
        }

        return null;
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
        } else {
            html.setAttribute('data-theme', 'light');
        }
        
        this.currentTheme = theme;
    }

    /**
     * Set theme preference and persist to backend
     */
    async setThemePreference(preference) {
        this.themePreference = preference;
        
        // Determine actual theme to apply
        let themeToApply;
        if (preference === 'system') {
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            themeToApply = systemPrefersDark ? 'dark' : 'light';
        } else {
            themeToApply = preference;
        }
        
        this.applyTheme(themeToApply);
        
        // Store in localStorage for instant access
        localStorage.setItem('theme_preference', preference);
        
        // Persist to backend
        try {
            const response = await fetch('/user/settings/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ theme_preference: preference })
            });
            
            if (!response.ok) {
                console.error('Failed to save theme preference');
            }
        } catch (error) {
            console.error('Error saving theme preference:', error);
        }
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Get theme preference
     */
    getThemePreference() {
        return this.themePreference || 'system';
    }
}

// Initialize theme manager on page load
window.themeManager = new ThemeManager();

// Export for use in other scripts
export default ThemeManager;


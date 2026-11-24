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

    // A choice made with the toggle that the server has not confirmed yet,
    // stored with the id of the member who made it. It outlives the page, so
    // a quick navigation, a failed request or a logout does not lose it: the
    // next page of the same member applies it and sends it again.
    const PENDING_STORAGE_KEY = 'user_theme_pending';

    /**
     * Read from localStorage without throwing when storage is blocked.
     * @param {string} key
     * @returns {string|null}
     */
    function readStorage(key = THEME_STORAGE_KEY) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    /**
     * Write to localStorage without throwing when storage is blocked.
     * A null value removes the key.
     * @param {string|null} value
     * @param {string} key
     */
    function writeStorage(value, key = THEME_STORAGE_KEY) {
        try {
            if (value === null) {
                localStorage.removeItem(key);
            } else {
                localStorage.setItem(key, value);
            }
        } catch (e) { /* the theme still applies for this page */ }
    }

    /**
     * Keep only the three known preferences.
     * @param {string|null} preference
     * @returns {string|null}
     */
    function validPreference(preference) {
        return Object.prototype.hasOwnProperty.call(PREFERENCES, preference) ? preference : null;
    }

    // The signed-in member and their saved preference, rendered by the server.
    // Both are absent for guests. The preference is mutable so the toggle
    // keeps it current on this page.
    const userId = document.documentElement.getAttribute('data-user-id');
    let serverPreference = userId
        ? validPreference(document.documentElement.getAttribute('data-user-theme'))
        : null;

    /**
     * The signed-in member's unconfirmed toggle choice, if there is one.
     * A choice another member left in this browser is ignored.
     * @returns {string|null}
     */
    function readPending() {
        if (!userId) {
            return null;
        }

        try {
            const pending = JSON.parse(readStorage(PENDING_STORAGE_KEY));

            return pending && pending.user === userId ? validPreference(pending.preference) : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Store the signed-in member's unconfirmed toggle choice.
     * @param {string} preference
     */
    function writePending(preference) {
        writeStorage(JSON.stringify({ user: userId, preference: preference }), PENDING_STORAGE_KEY);
    }

    /**
     * The active preference: the member's saved one when signed in, else the
     * browser's stored one, defaulting to 'system'.
     * @returns {string} - 'light', 'dark', or 'system'
     */
    function getStoredPreference() {
        return serverPreference || validPreference(readStorage()) || 'system';
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
        // The member's unconfirmed toggle choice wins and is sent again;
        // otherwise their saved preference wins, so a choice made on another
        // device (or by another member on this browser) does not stick.
        const pending = readPending();

        if (serverPreference && pending) {
            serverPreference = pending;
            debouncedPersist(pending);
        }

        const storedPreference = getStoredPreference();

        // Mirror the saved preference so guest pages on this browser match it.
        if (serverPreference) {
            writeStorage(storedPreference);
        }

        // Apply theme (inline script has already done initial set, this ensures correctness)
        applyTheme(getEffectiveTheme(storedPreference));
    }

    /**
     * Update theme preference
     * @param {string} preference - 'light', 'dark', or 'system'
     * @param {boolean} persist - also save to the user profile (debounced)
     */
    function setThemePreference(preference, persist = false) {
        if (!validPreference(preference)) {
            return;
        }

        writeStorage(preference);
        if (serverPreference) {
            serverPreference = preference;
        }
        applyTheme(getEffectiveTheme(preference));
        updateToggleUI(preference);

        if (persist) {
            debouncedPersist(preference);
        }
    }

    /**
     * Persist the preference to the user profile so it syncs across devices
     * and matches the settings page. The choice stays pending in localStorage
     * until the server confirms it, so a failed request is retried on the next
     * page instead of reverting the choice.
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function persistPreference(preference) {
        const token = document.querySelector('meta[name="csrf-token"]');

        if (!token || !userId) {
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
            // Let the request finish when the member leaves the page right away.
            keepalive: true,
        }).then((response) => {
            if (response.ok && readPending() === preference) {
                writeStorage(null, PENDING_STORAGE_KEY);
            }
        }).catch(() => { /* stays pending and is retried on the next page */ });
    }

    // Persist only the final choice a couple of seconds after the last click.
    // The saved preference wins on the next page, so a choice still waiting
    // for the debounce is sent right away when the member leaves this page.
    let pendingPreference = null;
    const debouncedSend = debounce(() => {
        if (!pendingPreference) {
            return;
        }
        persistPreference(pendingPreference);
        pendingPreference = null;
    }, PERSIST_DELAY);

    function debouncedPersist(preference) {
        if (!userId) {
            return;
        }
        pendingPreference = preference;
        writePending(preference);
        debouncedSend();
    }

    window.addEventListener('pagehide', () => {
        if (pendingPreference) {
            persistPreference(pendingPreference);
            pendingPreference = null;
        }
    });

    /**
     * Reflect the active preference on the header toggle: swap the icon and
     * label. Driven by the 3-way preference (not the resolved theme) so
     * "system" is visible as its own state.
     * @param {string} preference - 'light', 'dark', or 'system'
     */
    function updateToggleUI(preference) {
        const toggle = document.getElementById('theme-toggle');
        const config = validPreference(preference) && PREFERENCES[preference];

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

            // Saving the settings form saves this choice. Make it the pending
            // one, so an older toggle choice cannot override it on the next
            // page; if the save fails, the choice is still applied and retried.
            themeSelector.form?.addEventListener('submit', () => {
                const preference = validPreference(themeSelector.value);

                if (preference && userId) {
                    pendingPreference = null;
                    writePending(preference);
                }
            });
        }
    }

    /**
     * Follow a theme changed in another tab of this browser, so the next click
     * on this tab's toggle continues from it instead of from a stale value.
     */
    function watchOtherTabs() {
        window.addEventListener('storage', (e) => {
            if (e.key !== THEME_STORAGE_KEY && e.key !== PENDING_STORAGE_KEY) {
                return;
            }

            const preference = readPending() || validPreference(readStorage());

            if (!preference) {
                return;
            }

            if (serverPreference) {
                serverPreference = preference;
            }
            applyTheme(getEffectiveTheme(preference));
            updateToggleUI(preference);
        });
    }

    /**
     * Attach all runtime listeners. Safe to call once the DOM is ready.
     */
    function setupWatchers() {
        watchSystemTheme();
        watchThemeSelector();
        watchThemeToggle();
        watchOtherTabs();
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

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main frontend bootstrap file loaded by Vite.
|
| What it does:
| - loads shared JS dependencies
| - registers Alpine components used by pages
| - defines global Alpine stores for theme and sidebar behavior
| - boots optional interactive pages such as the 3D landing scene
|
| FILES TO READ (IN ORDER):
| 1. resources/js/app.js
| 2. resources/js/calendar-workspace.js
| 3. resources/js/chat-workspace.js
| 4. resources/views/layouts/app.blade.php
*/
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.SERVER_ROOM_THEME_KEY = 'tailadmin-theme-v1';

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        theme: 'light',
        // Loads the saved theme from localStorage and applies it to <html>/<body>.
        init() {
            localStorage.removeItem('server-room-theme');
            localStorage.removeItem('theme');
            const savedTheme = localStorage.getItem(window.SERVER_ROOM_THEME_KEY);
            this.theme = savedTheme || 'light';
            this.apply();
        },
        // Used by the topbar theme toggle.
        toggle() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem(window.SERVER_ROOM_THEME_KEY, this.theme);
            this.apply();
        },
        // Synchronizes CSS classes and emits a custom event so charts can react.
        apply() {
            const html = document.documentElement;
            const body = document.body;

            if (this.theme === 'dark') {
                html.classList.add('dark');
                html.style.colorScheme = 'dark';
                body?.classList.add('dark');
            } else {
                html.classList.remove('dark');
                html.style.colorScheme = 'light';
                body?.classList.remove('dark');
            }

            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: { theme: this.theme },
            }));
        },
    });

    Alpine.store('sidebar', {
        open: window.innerWidth >= 1024,
        desktopCollapsed: false,
        // Desktop and mobile behavior differ, so this helper centralizes that rule.
        isDesktop() {
            return window.innerWidth >= 1024;
        },
        // Toggle either collapses the desktop sidebar or opens the mobile drawer.
        toggle() {
            if (this.isDesktop()) {
                this.desktopCollapsed = !this.desktopCollapsed;
                this.open = !this.desktopCollapsed;
                return;
            }

            this.open = !this.open;
        },
        // Closes the drawer after navigation on smaller screens.
        closeOnMobile() {
            if (!this.isDesktop()) {
                this.open = false;
            }
        },
        // Recomputes sidebar state after viewport changes.
        sync() {
            this.open = this.isDesktop() ? !this.desktopCollapsed : false;
        },
    });
});

// Loads extra scripts only on the pages that need them.
const bootInteractivePages = async () => {
    if (document.getElementById('server-3d')) {
        const { initServer3D } = await import('./server-3d');
        initServer3D();
    }

    if (document.querySelector('[data-server-rack-landing]')) {
        const { initServerRackLanding } = await import('./server-rack-landing');
        initServerRackLanding();
    }
};

// Heavy page-only dependencies are loaded lazily so the global app bundle
// stays smaller for routes that do not need charts or advanced workspace logic.
const registerPageDependencies = async () => {
    const pageModules = [];

    if (document.querySelector('[data-dashboard-page]')) {
        pageModules.push(
            import('apexcharts').then(({ default: ApexCharts }) => {
                window.ApexCharts = ApexCharts;
            })
        );
    }

    if (document.querySelector('[data-calendar-workspace]')) {
        pageModules.push(
            import('./calendar-workspace').then(({ default: calendarWorkspace }) => {
                Alpine.data('calendarWorkspace', calendarWorkspace);
            })
        );
    }

    if (document.querySelector('[data-chat-workspace]')) {
        pageModules.push(
            import('./chat-workspace').then(({ default: chatWorkspace }) => {
                Alpine.data('chatWorkspace', chatWorkspace);
            })
        );
    }

    await Promise.all(pageModules);
};

const startApplication = async () => {
    await registerPageDependencies();
    Alpine.start();
    await bootInteractivePages();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void startApplication();
    }, { once: true });
} else {
    void startApplication();
}

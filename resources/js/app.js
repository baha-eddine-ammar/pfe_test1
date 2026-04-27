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

const escapeHtml = (value) => {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};

const hideBrandPreloader = () => {
    const preloader = document.querySelector('[data-brand-preloader]');

    document.documentElement.classList.remove('brand-preloader-pending');

    if (!preloader) {
        return;
    }

    preloader.classList.add('is-complete');

    window.setTimeout(() => {
        preloader.remove();
    }, 400);
};

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
    const shouldBootBrandPreloader = document.documentElement.dataset.brandPreloaderState === 'active';

    const preloaderBoot = shouldBootBrandPreloader && document.querySelector('[data-brand-preloader]')
        ? import('./brand-preloader')
            .then(({ default: initBrandPreloader }) => initBrandPreloader())
            .catch((error) => {
                console.error('Unable to start the brand preloader.', error);
                hideBrandPreloader();
            })
        : Promise.resolve();

    await Promise.all([
        registerPageDependencies(),
        preloaderBoot,
    ]);

    Alpine.start();
    await bootInteractivePages();

    window.addEventListener('user-notification-created', (event) => {
        const payload = event.detail || {};
        const unreadCount = Number(payload.unreadCount || 0);
        const badgeHost = document.querySelector('[data-notification-button]');
        const unreadText = document.querySelector('[data-notification-unread-text]');
        const list = document.querySelector('[data-notification-list]');
        let badge = document.querySelector('[data-notification-count-badge]');

        if (unreadText) {
            unreadText.textContent = `${unreadCount} unread`;
        }

        if (badgeHost && unreadCount > 0 && !badge) {
            badge = document.createElement('span');
            badge.dataset.notificationCountBadge = 'true';
            badge.className = 'absolute right-2 top-2 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white';
            badgeHost.appendChild(badge);
        }

        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
            } else {
                badge.remove();
            }
        }

        if (list && payload.notification) {
            const notification = payload.notification;
            const toneClass = {
                'maintenance.assigned': 'bg-amber-400',
                'chat.mentioned': 'bg-violet-500',
                'report.generated': 'bg-sky-400',
                'alert.critical': 'bg-rose-500',
                'user.approved': 'bg-emerald-400',
                'user.rejected': 'bg-rose-400',
            }[notification.type] || 'bg-brand-500';

            const item = document.createElement('a');
            item.href = notification.url || '/notifications';
            item.className = 'block w-full rounded-2xl border border-brand-100 bg-brand-50/70 px-4 py-3 text-left transition hover:-translate-y-0.5 hover:shadow-sm dark:border-brand-900/30 dark:bg-brand-500/10';
            item.innerHTML = `
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full ${toneClass}"></span>
                    <div class="min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-medium text-gray-900 dark:text-white">${escapeHtml(notification.title ?? 'Notification')}</p>
                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Now</span>
                        </div>
                        ${notification.body ? `<p class="mt-1 line-clamp-2 text-sm leading-6 text-gray-500 dark:text-gray-400">${escapeHtml(notification.body)}</p>` : ''}
                    </div>
                </div>
            `;

            list.prepend(item);

            while (list.children.length > 6) {
                list.removeChild(list.lastElementChild);
            }
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void startApplication();
    }, { once: true });
} else {
    void startApplication();
}

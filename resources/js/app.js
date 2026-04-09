import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.SERVER_ROOM_THEME_KEY = 'tailadmin-theme-v1';

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        theme: 'light',
        init() {
            localStorage.removeItem('server-room-theme');
            localStorage.removeItem('theme');
            const savedTheme = localStorage.getItem(window.SERVER_ROOM_THEME_KEY);
            this.theme = savedTheme || 'light';
            this.apply();
        },
        toggle() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem(window.SERVER_ROOM_THEME_KEY, this.theme);
            this.apply();
        },
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
        isDesktop() {
            return window.innerWidth >= 1024;
        },
        toggle() {
            if (this.isDesktop()) {
                this.desktopCollapsed = !this.desktopCollapsed;
                this.open = !this.desktopCollapsed;
                return;
            }

            this.open = !this.open;
        },
        closeOnMobile() {
            if (!this.isDesktop()) {
                this.open = false;
            }
        },
        sync() {
            this.open = this.isDesktop() ? !this.desktopCollapsed : false;
        },
    });
});

Alpine.start();

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        bootInteractivePages();
    }, { once: true });
} else {
    bootInteractivePages();
}

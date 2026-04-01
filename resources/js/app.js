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
        toggle() {
            this.open = !this.open;
        },
        closeOnMobile() {
            if (window.innerWidth < 1024) {
                this.open = false;
            }
        },
        sync() {
            this.open = window.innerWidth >= 1024;
        },
    });
});

Alpine.start();

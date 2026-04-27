import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const dispatchRealtimeEvent = (name, detail = {}) => {
    window.dispatchEvent(new CustomEvent(name, { detail }));
};

const bootRealtime = () => {
    const authUserId = document.body?.dataset?.authUserId || '';
    const authUserRole = document.body?.dataset?.authUserRole || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || 8080);
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

    if (authUserId === '' || !reverbKey || window.Echo) {
        return;
    }

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });

    window.Echo.private('ops.chat')
        .listen('.chat.message.created', (payload) => {
            dispatchRealtimeEvent('chat-message-created', payload);
        });

    window.Echo.private(`users.${authUserId}.notifications`)
        .listen('.notification.created', (payload) => {
            dispatchRealtimeEvent('user-notification-created', payload);
        });

    window.Echo.private(`users.${authUserId}.maintenance`)
        .listen('.maintenance.task.changed', (payload) => {
            dispatchRealtimeEvent('maintenance-task-changed', payload);
        });

    window.Echo.private('dashboard.telemetry')
        .listen('.sensor.telemetry.updated', (payload) => {
            dispatchRealtimeEvent('sensor-telemetry-updated', payload);
        });

    window.Echo.private('servers.overview')
        .listen('.server.metric.stored', (payload) => {
            dispatchRealtimeEvent('server-metric-stored', payload);
        });

    if (authUserRole === 'department_head') {
        window.Echo.private('ops.admin')
            .listen('.report.generated', (payload) => {
                dispatchRealtimeEvent('report-generated', payload);
            })
            .listen('.maintenance.task.changed', (payload) => {
                dispatchRealtimeEvent('maintenance-task-changed', payload);
            });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootRealtime, { once: true });
} else {
    bootRealtime();
}

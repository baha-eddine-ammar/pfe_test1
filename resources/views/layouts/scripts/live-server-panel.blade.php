<script>
    window.liveServerPanel = window.liveServerPanel || function (config) {
        return {
            server: JSON.parse(JSON.stringify(config.initialServer || {})),
            feedUrl: config.feedUrl,
            refreshing: false,
            statusPillClass(status) {
                return {
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': status === 'Live',
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': status === 'Warning',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300': status === 'Critical',
                    'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300': status === 'Offline',
                };
            },
            metricTextClass(color) {
                return {
                    cyan: 'text-sky-600 dark:text-sky-300',
                    violet: 'text-violet-600 dark:text-violet-300',
                    pink: 'text-pink-600 dark:text-pink-300',
                    emerald: 'text-emerald-600 dark:text-emerald-300',
                    amber: 'text-amber-600 dark:text-amber-300',
                }[color] || 'text-slate-600 dark:text-slate-300';
            },
            metricBarClass(color) {
                return {
                    cyan: 'from-sky-400 via-cyan-400 to-sky-500',
                    violet: 'from-violet-400 via-fuchsia-400 to-violet-500',
                    pink: 'from-pink-400 via-rose-400 to-pink-500',
                    emerald: 'from-emerald-400 via-lime-400 to-emerald-500',
                    amber: 'from-amber-400 via-orange-400 to-amber-500',
                }[color] || 'from-slate-400 via-slate-500 to-slate-600';
            },
            progressStyle(progress) {
                return `width: ${Math.max(0, Math.min(100, Number(progress || 0)))}%;`;
            },
            applyRealtimePayload(payload) {
                if (!payload?.server) {
                    return;
                }

                if (String(payload.server.id) !== String(this.server.id)) {
                    return;
                }

                this.server = payload.server;
                this.refreshing = false;
            },
            init() {
                if (!this.feedUrl) {
                    return;
                }

                window.addEventListener('server-metric-stored', (event) => {
                    this.applyRealtimePayload(event.detail);
                });
            },
        };
    };
</script>

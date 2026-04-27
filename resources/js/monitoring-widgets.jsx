import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const parseProps = (element) => {
    try {
        return JSON.parse(element.dataset.props || '{}');
    } catch (error) {
        console.error('Invalid React monitoring widget props.', error);
        return {};
    }
};

const hideBladeFallback = (element) => {
    const fallback = element.nextElementSibling;

    if (fallback?.matches('[data-react-fallback]')) {
        fallback.hidden = true;
    }

    element.parentElement
        ?.querySelectorAll('[data-react-chart-fallback]')
        .forEach((fallbackElement) => {
            fallbackElement.hidden = true;
        });
};

const statusClass = (status) => {
    if (['Stable', 'Live'].includes(status)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300';
    }

    if (status === 'Warning') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300';
    }

    if (status === 'Critical') {
        return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300';
    }

    return 'border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-white/[0.05] dark:text-slate-300';
};

const clamp = (value, min = 0, max = 100) => Math.max(min, Math.min(max, Number(value) || 0));

function LiveMetricCard({ initial }) {
    const [metric, setMetric] = useState(initial);
    const [pulse, setPulse] = useState(false);

    useEffect(() => {
        const onRealtime = (event) => {
            const trend = event.detail?.trend;

            if (!trend?.latest) {
                return;
            }

            const title = metric.title.toLowerCase();
            const value = title === 'temperature'
                ? trend.latest.temperature
                : title === 'humidity'
                    ? trend.latest.humidity
                    : null;

            if (value === null || value === undefined) {
                return;
            }

            setMetric((current) => ({
                ...current,
                value,
                sparkline: Array.isArray(trend[title]) ? trend[title].slice(-10) : current.sparkline,
                status: resolveEnvironmentStatus(title, value),
                ringDegrees: resolveEnvironmentRingDegrees(title, value),
                empty: false,
            }));
            flash(setPulse);
        };

        window.addEventListener('sensor-telemetry-updated', onRealtime);

        return () => {
            window.removeEventListener('sensor-telemetry-updated', onRealtime);
        };
    }, [metric.title]);

    const empty = metric.empty || metric.value === null || metric.value === undefined;
    const progress = empty ? 0 : clamp(metric.ringDegrees ? (metric.ringDegrees / 360) * 100 : metric.value);

    return (
        <article className={`dashboard-panel dashboard-panel-hover group relative overflow-hidden px-6 py-6 sm:px-7 ${pulse ? 'scale-[1.01] shadow-[0_28px_90px_rgba(70,95,255,0.12)]' : ''}`}>
            <div className="absolute inset-0 opacity-60" style={{ background: `radial-gradient(circle at top right, ${metric.stableColor || '#465FFF'}22, transparent 32%)` }} />
            <div className="relative z-[1]">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/80 text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur dark:bg-white/10 dark:text-white dark:shadow-none">
                        <MetricIcon icon={metric.icon} />
                    </div>
                    <span className={`inline-flex items-center gap-2 rounded-full border px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] ${statusClass(metric.status)}`}>
                        {!empty && <span className="dashboard-live-dot" />}
                        {metric.status}
                    </span>
                </div>

                <div className="mt-6">
                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">{metric.subtitle}</p>
                    <div className="mt-2 flex items-start justify-between gap-4">
                        <div>
                            <h2 className="font-display text-[1.9rem] font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{metric.title}</h2>
                            <div className="mt-4 flex items-end gap-2">
                                <p className="font-display text-5xl font-semibold leading-none text-slate-950 dark:text-white">
                                    {empty ? '--' : Number(metric.value).toFixed(1)}
                                </p>
                                {!empty && <span className="pb-1 text-lg font-medium text-slate-400 dark:text-slate-500">{metric.unit}</span>}
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Current load</p>
                            <p className="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{empty ? '--' : `${Math.round(progress)}%`}</p>
                        </div>
                    </div>
                </div>

                <Sparkline values={metric.sparkline || []} color={metric.stableColor || '#465FFF'} empty={empty} />

                {metric.target && <p className="mt-5 text-sm leading-6 text-slate-500 dark:text-slate-400">{metric.target}</p>}

                <div className="mt-5">
                    <div className="metric-progress-track h-2.5 bg-slate-100/80 dark:bg-white/[0.06]">
                        <div className="h-full rounded-full transition-all duration-500" style={{ width: `${progress}%`, background: `linear-gradient(90deg, ${metric.stableColor || '#465FFF'}, #22d3ee)` }} />
                    </div>
                    <div className="mt-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                        <span>{empty ? 'Awaiting real sensor feed' : 'WebSocket live feed'}</span>
                        <span>{metric.status}</span>
                    </div>
                </div>
            </div>
        </article>
    );
}

function DashboardTelemetryChart({ initial }) {
    const [trend, setTrend] = useState(initial.trend || {});

    useEffect(() => {
        const onRealtime = (event) => {
            if (event.detail?.trend) {
                setTrend(event.detail.trend);
            }
        };

        window.addEventListener('sensor-telemetry-updated', onRealtime);

        return () => {
            window.removeEventListener('sensor-telemetry-updated', onRealtime);
        };
    }, []);

    return (
        <div className="flex h-full flex-col">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
                    <Legend color="#38BDF8" label="Temperature" />
                    <Legend color="#8B5CF6" label="Humidity" />
                </div>
                <span className="dashboard-live-badge">
                    <span className="dashboard-live-dot" />
                    {trend.hasData ? 'Streaming live' : 'Awaiting ESP32'}
                </span>
            </div>
            <LineChart
                series={[
                    { name: 'Temperature', color: '#38BDF8', values: trend.temperature || [] },
                    { name: 'Humidity', color: '#8B5CF6', values: trend.humidity || [] },
                ]}
                emptyText="Waiting for real ESP32 data"
            />
            <p className="mt-4 text-right text-sm font-semibold text-slate-500 dark:text-slate-400">
                {trend.lastUpdatedLabel || 'Waiting for ESP32 readings'}
            </p>
        </div>
    );
}

function ServerLiveCard({ initial }) {
    const [server, setServer] = useState(initial.server || {});

    useEffect(() => {
        const onRealtime = (event) => {
            if (event.detail?.server && (!server.id || event.detail.server.id === server.id)) {
                setServer(event.detail.server);
            }
        };

        window.addEventListener('server-metric-stored', onRealtime);

        return () => {
            window.removeEventListener('server-metric-stored', onRealtime);
        };
    }, [server.id]);

    return (
        <article className="dashboard-panel dashboard-panel-hover group relative overflow-hidden px-6 py-6 sm:px-7">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(70,95,255,0.12),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(34,211,238,0.08),_transparent_28%)]" />
            <div className="relative z-[1]">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h3 className="font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{server.name}</h3>
                            <span className={`app-pill ${statusClass(server.status)}`}>{server.status}</span>
                            <span className="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-600 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300">
                                <span className="dashboard-live-dot" />
                                WebSocket live
                            </span>
                        </div>
                        <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{server.identifier || 'Monitoring node'}</p>
                    </div>
                    <div className="dashboard-surface-glass rounded-[24px] px-4 py-3 text-right">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Last seen</p>
                        <p className="mt-2 font-display text-xl font-semibold text-slate-950 dark:text-white">{server.lastSeenLabel || 'Waiting'}</p>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {(server.metrics || []).map((metric) => (
                        <ServerMetricTile key={metric.key || metric.label} metric={metric} />
                    ))}
                </div>

                <div className="dashboard-surface-glass mt-5 rounded-[24px] px-4 py-4">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Operational note</p>
                    <p className="mt-2 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400">{server.narrative}</p>
                </div>
            </div>
        </article>
    );
}

function ServerTelemetryChart({ initial }) {
    const [samples, setSamples] = useState(initial.recentMetrics || []);
    const [lastMetricSignature, setLastMetricSignature] = useState('');

    const appendServerSample = (server) => {
        if (!server || Number(server.id) !== Number(initial.serverId)) {
            return;
        }

        const metrics = server.metrics || [];
        const metricMap = Object.fromEntries(metrics.map((metric) => [metric.key, metric.progress]));
        const valueMap = Object.fromEntries(metrics.map((metric) => [metric.key, metric.value]));
        const signature = [
            server.lastSeenAt || server.lastSeenLabel || '',
            valueMap.cpu || metricMap.cpu || '',
            valueMap.ram || metricMap.ram || '',
            valueMap.disk || metricMap.disk || '',
            valueMap.network || metricMap.network || '',
        ].join('|');

        setLastMetricSignature((currentSignature) => {
            if (currentSignature === signature) {
                return currentSignature;
            }

            const label = server.lastSeenAt
                ? new Intl.DateTimeFormat('en-GB', {
                    timeZone: 'Africa/Tunis',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }).format(new Date(server.lastSeenAt.replace(' ', 'T')))
                : new Intl.DateTimeFormat('en-GB', {
                    timeZone: 'Africa/Tunis',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }).format(new Date());

            setSamples((current) => [
                ...current.slice(-29),
                {
                    label,
                    cpu: metricMap.cpu || 0,
                    ram: metricMap.ram || 0,
                    disk: metricMap.disk || 0,
                    network: metricMap.network || 0,
                },
            ]);

            return signature;
        });
    };

    useEffect(() => {
        const onRealtime = (event) => {
            appendServerSample(event.detail?.server);
        };

        window.addEventListener('server-metric-stored', onRealtime);

        return () => {
            window.removeEventListener('server-metric-stored', onRealtime);
        };
    }, [initial.serverId]);

    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap gap-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
                    <Legend color="#22D3EE" label="CPU" />
                    <Legend color="#8B5CF6" label="RAM" />
                    <Legend color="#F472B6" label="Disk" />
                    <Legend color="#34D399" label="Network" />
                </div>
                <span className="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">React live chart</span>
            </div>
            <LineChart
                series={[
                    { name: 'CPU', color: '#22D3EE', values: samples.map((sample) => sample.cpu) },
                    { name: 'RAM', color: '#8B5CF6', values: samples.map((sample) => sample.ram) },
                    { name: 'Disk', color: '#F472B6', values: samples.map((sample) => sample.disk) },
                    { name: 'Network', color: '#34D399', values: samples.map((sample) => sample.network) },
                ]}
                height={260}
                emptyText="Waiting for Python agent metrics"
            />
        </div>
    );
}

function ServerMetricTile({ metric }) {
    return (
        <div className="dashboard-surface-glass rounded-[24px] px-4 py-4">
            <div className="flex items-center justify-between gap-4">
                <span className="text-sm font-medium text-slate-500 dark:text-slate-400">{metric.label}</span>
                <span className="text-sm font-semibold text-slate-950 dark:text-white">{metric.value}</span>
            </div>
            <div className="mt-4 metric-progress-track h-2.5 bg-slate-100/80 dark:bg-white/[0.06]">
                <div className="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-400" style={{ width: `${clamp(metric.progress)}%` }} />
            </div>
            <div className="mt-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                <span>{metric.footerLabel || 'Utilization'}</span>
                <span>{metric.progressLabel || `${metric.progress}%`}</span>
            </div>
        </div>
    );
}

function Sparkline({ values, color, empty }) {
    const normalized = Array.isArray(values)
        ? values.map((item) => typeof item === 'object' ? item.value : item).filter((value) => value !== null && value !== undefined)
        : [];

    return (
        <div className="dashboard-surface-glass mt-6 rounded-[24px] p-4">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Trend delta</p>
                    <p className="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-300">{empty ? 'Awaiting sensor' : 'Live trace'}</p>
                </div>
                <div className="h-[88px] min-w-[7rem]">
                    {empty ? (
                        <div className="flex h-full items-center justify-end text-sm font-semibold text-slate-300 dark:text-slate-600">--</div>
                    ) : (
                        <LineChart series={[{ name: 'Metric', color, values: normalized }]} height={88} compact />
                    )}
                </div>
            </div>
        </div>
    );
}

function LineChart({ series, height = 330, emptyText = 'Waiting for data', compact = false }) {
    const allValues = series.flatMap((item) => item.values).filter((value) => value !== null && value !== undefined).map(Number);
    const hasData = allValues.length > 0;
    const min = hasData ? Math.min(...allValues) - 3 : 0;
    const max = hasData ? Math.max(...allValues) + 3 : 100;
    const viewHeight = compact ? 88 : 320;
    const top = compact ? 8 : 28;
    const bottom = compact ? 78 : 292;

    const pointsFor = (values) => values
        .filter((value) => value !== null && value !== undefined)
        .map((value, index, filtered) => {
            const x = 24 + (filtered.length <= 1 ? 0 : (index / (filtered.length - 1)) * 812);
            const y = bottom - ((Number(value) - min) / Math.max(1, max - min)) * (bottom - top);
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');

    return (
        <div className="relative" style={{ minHeight: height }}>
            {!hasData && (
                <div className="absolute inset-0 z-10 grid place-items-center rounded-[22px] border border-dashed border-slate-300/70 bg-white/80 text-center backdrop-blur-sm dark:border-white/10 dark:bg-slate-950/70">
                    <p className="px-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{emptyText}</p>
                </div>
            )}
            <svg viewBox={`0 0 860 ${viewHeight}`} className="h-full w-full" style={{ minHeight: height }}>
                {!compact && [0, 1, 2, 3, 4].map((line) => (
                    <line key={line} x1="24" x2="836" y1={36 + line * 62} y2={36 + line * 62} className="stroke-slate-200 dark:stroke-slate-800" strokeDasharray="6 8" />
                ))}
                {series.map((item) => (
                    <polyline
                        key={item.name}
                        points={pointsFor(item.values || [])}
                        fill="none"
                        stroke={item.color}
                        strokeWidth={compact ? 4 : 5}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                ))}
            </svg>
        </div>
    );
}

function Legend({ color, label }) {
    return (
        <span className="inline-flex items-center gap-2">
            <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: color }} />
            {label}
        </span>
    );
}

function MetricIcon({ icon }) {
    if (icon === 'humidity') {
        return <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M12 4c3.5 4.2 5.5 7 5.5 9.5A5.5 5.5 0 116.5 13.5C6.5 11 8.5 8.2 12 4z" /></svg>;
    }

    if (icon === 'airflow') {
        return <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M4 9h9a3 3 0 100-6" strokeLinecap="round" /><path d="M4 15h12a3 3 0 110 6" strokeLinecap="round" /><path d="M4 12h16" strokeLinecap="round" /></svg>;
    }

    if (icon === 'power') {
        return <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M13 3L4 14h6l-1 7 9-11h-6l1-7z" strokeLinejoin="round" /></svg>;
    }

    return <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M12 4a2 2 0 00-2 2v7.6a4 4 0 104 0V6a2 2 0 00-2-2z" /><path d="M12 11v5" strokeLinecap="round" /></svg>;
}

function resolveEnvironmentStatus(metric, value) {
    if (metric === 'temperature') {
        return value >= 30 ? 'Critical' : value >= 25 ? 'Warning' : 'Stable';
    }

    if (metric === 'humidity') {
        return value >= 70 || value <= 30 ? 'Critical' : value >= 60 || value <= 35 ? 'Warning' : 'Stable';
    }

    return 'Stable';
}

function resolveEnvironmentRingDegrees(metric, value) {
    if (metric === 'temperature') {
        return Math.round((clamp(((Number(value) - 10) / 30) * 100) / 100) * 360);
    }

    if (metric === 'humidity') {
        return Math.round((clamp(value) / 100) * 360);
    }

    return 0;
}

function flash(setter) {
    setter(true);
    window.setTimeout(() => setter(false), 650);
}

function mountAll() {
    document.querySelectorAll('[data-react-live-metric-card]').forEach((element) => {
        hideBladeFallback(element);
        createRoot(element).render(<LiveMetricCard initial={parseProps(element)} />);
    });

    document.querySelectorAll('[data-react-dashboard-telemetry-chart]').forEach((element) => {
        hideBladeFallback(element);
        createRoot(element).render(<DashboardTelemetryChart initial={parseProps(element)} />);
    });

    document.querySelectorAll('[data-react-server-live-card]').forEach((element) => {
        hideBladeFallback(element);
        createRoot(element).render(<ServerLiveCard initial={parseProps(element)} />);
    });

    document.querySelectorAll('[data-react-server-telemetry-chart]').forEach((element) => {
        createRoot(element).render(<ServerTelemetryChart initial={parseProps(element)} />);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAll, { once: true });
} else {
    mountAll();
}

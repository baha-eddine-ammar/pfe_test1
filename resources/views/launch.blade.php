<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Launching Server Room System</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <div
            x-data="launchPage({ targetUrl: '{{ route('dashboard') }}' })"
            x-init="start()"
            class="relative flex min-h-screen items-center justify-center overflow-hidden px-6"
        >
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.22),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.18),transparent_32%),linear-gradient(140deg,#020617,#0f172a,#082f49)]"></div>
            <div class="absolute inset-0 opacity-35 [background-image:linear-gradient(rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.08)_1px,transparent_1px)] [background-size:40px_40px]"></div>
            <div class="absolute left-10 top-12 h-40 w-40 rounded-full bg-sky-400/20 blur-3xl"></div>
            <div class="absolute bottom-10 right-12 h-48 w-48 rounded-full bg-emerald-400/15 blur-3xl"></div>

            <div class="relative z-10 w-full max-w-2xl rounded-[2rem] border border-white/10 bg-slate-900/70 p-8 shadow-2xl shadow-sky-950/30 backdrop-blur-xl sm:p-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl border border-white/10 bg-white/8 ring-1 ring-white/10">
                    <svg class="h-9 w-9 text-sky-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 6.5L12 3l7 3.5v11L12 21l-7-3.5v-11z" stroke-linejoin="round"></path>
                        <path d="M8 9h8M8 12h8M8 15h5" stroke-linecap="round"></path>
                    </svg>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-sm uppercase tracking-[0.32em] text-slate-400">Launch sequence</p>
                    <h1 class="mt-4 font-display text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                        Launching Server Room System...
                    </h1>
                    <p class="mt-4 text-lg text-slate-300">
                        Preparing dashboards, health data, maintenance workflow, and AI tools.
                    </p>
                </div>

                <div class="mt-10 rounded-full bg-white/8 p-2">
                    <div class="h-3 overflow-hidden rounded-full bg-white/8">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-sky-400 via-cyan-300 to-emerald-300 transition-all duration-300"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between text-sm text-slate-400">
                    <p>Loading operational workspace</p>
                    <p x-text="`${progress}%`"></p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/8 bg-white/6 px-4 py-4 text-center">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Monitoring</p>
                        <p class="mt-2 font-display text-xl font-semibold text-white">Ready</p>
                    </div>
                    <div class="rounded-3xl border border-white/8 bg-white/6 px-4 py-4 text-center">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Reports</p>
                        <p class="mt-2 font-display text-xl font-semibold text-white">Loading</p>
                    </div>
                    <div class="rounded-3xl border border-white/8 bg-white/6 px-4 py-4 text-center">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">AI</p>
                        <p class="mt-2 font-display text-xl font-semibold text-white">Syncing</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('launchPage', ({ targetUrl }) => ({
                    targetUrl,
                    progress: 0,

                    start() {
                        const steps = [18, 34, 52, 68, 84, 100];

                        steps.forEach((value, index) => {
                            setTimeout(() => {
                                this.progress = value;
                            }, 400 * (index + 1));
                        });

                        setTimeout(() => {
                            window.location.href = this.targetUrl;
                        }, 2700);
                    },
                }));
            });
        </script>
    </body>
</html>

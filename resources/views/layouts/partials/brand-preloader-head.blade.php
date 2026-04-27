@if (config('preloader.enabled'))
    <script>
        (function () {
            const root = document.documentElement;
            const storageKey = 'draxlmaier-brand-preloader-seen';
            let shouldShowPreloader = true;

            try {
                shouldShowPreloader = window.sessionStorage.getItem(storageKey) !== '1';

                if (shouldShowPreloader) {
                    window.sessionStorage.setItem(storageKey, '1');
                }
            } catch (error) {
                shouldShowPreloader = true;
            }

            root.dataset.brandPreloaderState = shouldShowPreloader ? 'active' : 'skipped';

            if (shouldShowPreloader) {
                root.classList.add('brand-preloader-pending');
            }
        })();
    </script>

    <style>
        html.brand-preloader-pending {
            overflow: hidden;
        }

        html.brand-preloader-pending body {
            overflow: hidden;
        }

        [data-brand-preloader] {
            position: fixed;
            inset: 0;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        html.brand-preloader-pending [data-brand-preloader] {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        html[data-brand-preloader-state='skipped'] [data-brand-preloader] {
            display: none !important;
        }
    </style>
@endif

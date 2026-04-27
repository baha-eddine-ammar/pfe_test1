@if (config('preloader.enabled'))
    <div
        class="brand-preloader"
        data-brand-preloader
        data-duration-ms="{{ max(1000, (int) config('preloader.duration_ms', 4600)) }}"
        aria-hidden="true"
    >
        <div class="brand-preloader__backdrop" data-brand-backdrop></div>
        <div class="brand-preloader__sheen" data-brand-sheen></div>
        <div class="brand-preloader__halo" data-brand-halo></div>
        <div class="brand-preloader__vignette"></div>
        <div class="brand-preloader__frame"></div>

        <div class="brand-preloader__inner">
            <div class="brand-preloader__lockup" data-brand-lockup>
                <div class="brand-preloader__mark-wrap" data-brand-mark-wrap>
                    <img
                        class="brand-preloader__mark"
                        data-brand-mark
                        src="{{ asset('images/brand/draxlmaier-d-mark.png') }}"
                        alt=""
                        decoding="async"
                        fetchpriority="high"
                    >
                </div>

                <div class="brand-preloader__wordmark-wrap" data-brand-wordmark-wrap>
                    <div class="brand-preloader__wordmark-clip" data-brand-wordmark-clip>
                        <span class="brand-preloader__wordmark" data-brand-wordmark>R&Auml;XLMAIER</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

import gsap from 'gsap';

const PENDING_CLASS = 'brand-preloader-pending';
const COMPLETE_CLASS = 'is-complete';
const FALLBACK_CLASS = 'is-fallback';
const BASE_DURATION = 4600;

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const preloadImage = (image) => new Promise((resolve, reject) => {
    if (!(image instanceof HTMLImageElement)) {
        reject(new Error('Preloader image element is missing.'));
        return;
    }

    if ((image.currentSrc || image.complete) && image.naturalWidth > 0) {
        resolve();
        return;
    }

    const cleanup = () => {
        image.removeEventListener('load', onLoad);
        image.removeEventListener('error', onError);
    };

    const onLoad = () => {
        cleanup();
        resolve();
    };

    const onError = () => {
        cleanup();
        reject(new Error(`Unable to load preloader image: ${image.src}`));
    };

    image.addEventListener('load', onLoad, { once: true });
    image.addEventListener('error', onError, { once: true });
});

const waitForBrandFonts = async () => {
    if (!document.fonts?.load) {
        return;
    }

    try {
        await Promise.all([
            document.fonts.load('800 1rem Outfit'),
            document.fonts.ready,
        ]);
    } catch (error) {
        // The preloader still works with the fallback font if Outfit cannot load.
    }
};

const measureElementWidth = (element) => {
    if (!element) {
        return 0;
    }

    const renderedWidth = element.getBoundingClientRect().width;

    if (renderedWidth > 0) {
        return renderedWidth;
    }

    return element.scrollWidth || Number.parseFloat(window.getComputedStyle(element).width || '0');
};

const finishPreloader = (root) => {
    if (!root || root.dataset.state === 'finished') {
        return;
    }

    root.dataset.state = 'finished';
    root.classList.add(COMPLETE_CLASS);
    document.documentElement.classList.remove(PENDING_CLASS);

    window.setTimeout(() => {
        root.remove();
    }, 720);
};

const runFallbackSequence = (root, durationMs) => {
    if (!root) {
        document.documentElement.classList.remove(PENDING_CLASS);
        return;
    }

    const lockup = root.querySelector('[data-brand-lockup]');
    const markWrap = root.querySelector('[data-brand-mark-wrap]');
    const wordmarkClip = root.querySelector('[data-brand-wordmark-clip]');
    const wordmark = root.querySelector('[data-brand-wordmark]');
    const sheen = root.querySelector('[data-brand-sheen]');
    const halo = root.querySelector('[data-brand-halo]');

    root.classList.add(FALLBACK_CLASS);

    if (markWrap) {
        markWrap.style.opacity = '1';
        markWrap.style.transform = 'translate3d(0, 0, 0) scale(1)';
    }

    window.setTimeout(() => {
        if (wordmarkClip) {
            wordmarkClip.style.opacity = '1';
            wordmarkClip.style.transform = 'scaleX(1)';
        }

        if (wordmark) {
            wordmark.style.opacity = '1';
            wordmark.style.transform = 'translate3d(0, 0, 0)';
        }

        if (sheen) {
            sheen.style.opacity = '0.18';
            sheen.style.transform = 'translate3d(0, 0, 0) scaleX(1)';
        }

        if (halo) {
            halo.style.opacity = '0.72';
            halo.style.transform = 'scale(1.04)';
        }
    }, 260);

    window.setTimeout(() => {
        if (lockup) {
            lockup.style.transform = 'translate3d(0, 0, 0) scale(4.35)';
        }
    }, 1380);

    window.setTimeout(() => {
        finishPreloader(root);
    }, Math.max(1900, Math.min(durationMs, 2600)));
};

export default async function initBrandPreloader() {
    const root = document.querySelector('[data-brand-preloader]');

    if (!root) {
        return;
    }

    const durationMs = Number.parseInt(root.dataset.durationMs || `${BASE_DURATION}`, 10) || BASE_DURATION;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const lockup = root.querySelector('[data-brand-lockup]');
    const markWrap = root.querySelector('[data-brand-mark-wrap]');
    const wordmarkWrap = root.querySelector('[data-brand-wordmark-wrap]');
    const wordmarkClip = root.querySelector('[data-brand-wordmark-clip]');
    const wordmark = root.querySelector('[data-brand-wordmark]');
    const mark = root.querySelector('[data-brand-mark]');
    const backdrop = root.querySelector('[data-brand-backdrop]');
    const sheen = root.querySelector('[data-brand-sheen]');
    const halo = root.querySelector('[data-brand-halo]');
    const compactViewport = window.innerWidth <= 640;

    if (!lockup || !markWrap || !wordmarkWrap || !wordmarkClip || !wordmark || !mark) {
        finishPreloader(root);
        return;
    }

    try {
        await Promise.all([
            preloadImage(mark),
            waitForBrandFonts(),
        ]);
    } catch (error) {
        runFallbackSequence(root, durationMs);
        return;
    }

    const wordmarkWidth = measureElementWidth(wordmarkWrap);

    if (wordmarkWidth <= 0) {
        runFallbackSequence(root, durationMs);
        return;
    }

    const scaleFactor = Math.max(0.84, durationMs / BASE_DURATION);
    const lockupGap = Number.parseFloat(window.getComputedStyle(lockup).gap || '0');
    const introOffsetX = (wordmarkWidth + lockupGap) / 2;
    const lockupBounds = lockup.getBoundingClientRect();
    const fillScale = clamp(
        (window.innerWidth * (compactViewport ? 1.8 : 1.62)) / Math.max(lockupBounds.width, 1),
        compactViewport ? 3.25 : 3.7,
        compactViewport ? 5.65 : 6.45
    );

    if (reducedMotion) {
        markWrap.style.opacity = '1';
        wordmarkClip.style.opacity = '1';
        wordmarkClip.style.transform = 'scaleX(1)';
        wordmark.style.opacity = '1';
        wordmark.style.transform = 'translate3d(0, 0, 0)';

        if (sheen) {
            sheen.style.opacity = '0.14';
            sheen.style.transform = 'translate3d(0, 0, 0) scaleX(1)';
        }

        if (halo) {
            halo.style.opacity = '0.66';
            halo.style.transform = 'scale(1.02)';
        }

        window.setTimeout(() => {
            finishPreloader(root);
        }, Math.min(durationMs, 1200));

        return;
    }

    gsap.set(lockup, {
        x: introOffsetX,
        y: 0,
        scale: 0.99,
        transformOrigin: '50% 50%',
        force3D: true,
    });

    gsap.set(markWrap, {
        autoAlpha: 0,
        scale: 0.94,
        transformOrigin: '50% 50%',
        force3D: true,
    });

    gsap.set(wordmarkClip, {
        autoAlpha: 0,
        scaleX: 0,
        transformOrigin: '0% 50%',
        force3D: true,
    });

    gsap.set(wordmark, {
        autoAlpha: 0,
        x: 36,
        transformOrigin: '0% 50%',
        force3D: true,
    });

    if (backdrop) {
        gsap.set(backdrop, {
            scale: 1.045,
            transformOrigin: '50% 50%',
            force3D: true,
        });
    }

    if (sheen) {
        gsap.set(sheen, {
            autoAlpha: 0,
            xPercent: -8,
            scaleX: 0.84,
            transformOrigin: '50% 50%',
            force3D: true,
        });
    }

    if (halo) {
        gsap.set(halo, {
            autoAlpha: 0,
            scale: 0.8,
            transformOrigin: '50% 50%',
            force3D: true,
        });
    }

    const timeline = gsap.timeline({
        defaults: {
            ease: 'power2.out',
        },
        onComplete: () => {
            finishPreloader(root);
        },
    });

    timeline.to(markWrap, {
        autoAlpha: 1,
        scale: 1,
        duration: 0.48 * scaleFactor,
        ease: 'power3.out',
    });

    if (halo) {
        timeline.to(halo, {
            autoAlpha: 0.68,
            scale: 1,
            duration: 1.1 * scaleFactor,
            ease: 'sine.out',
        }, 0.02 * scaleFactor);
    }

    if (sheen) {
        timeline.to(sheen, {
            autoAlpha: 0.22,
            xPercent: 0,
            scaleX: 1,
            duration: 1.16 * scaleFactor,
            ease: 'sine.out',
        }, 0.12 * scaleFactor);
    }

    if (backdrop) {
        timeline.to(backdrop, {
            scale: 1.02,
            duration: 1.26 * scaleFactor,
            ease: 'sine.out',
        }, 0);
    }

    timeline.to(lockup, {
        x: 0,
        duration: 1.06 * scaleFactor,
        ease: 'expo.inOut',
    }, 0.42 * scaleFactor);

    timeline.to(wordmarkClip, {
        autoAlpha: 1,
        scaleX: 1,
        duration: 0.92 * scaleFactor,
        ease: 'power3.out',
    }, 0.5 * scaleFactor);

    timeline.to(wordmark, {
        autoAlpha: 1,
        x: 0,
        duration: 0.78 * scaleFactor,
        ease: 'power3.out',
    }, 0.58 * scaleFactor);

    timeline.to(lockup, {
        scale: 1.05,
        duration: 0.34 * scaleFactor,
        ease: 'sine.inOut',
    }, 1.72 * scaleFactor);

    timeline.to(lockup, {
        scale: fillScale,
        y: compactViewport ? -6 : -8,
        duration: 1.86 * scaleFactor,
        ease: 'expo.inOut',
    }, 1.98 * scaleFactor);

    if (halo) {
        timeline.to(halo, {
            autoAlpha: 0.92,
            scale: 1.16,
            duration: 1.56 * scaleFactor,
            ease: 'sine.inOut',
        }, 1.96 * scaleFactor);
    }

    if (sheen) {
        timeline.to(sheen, {
            autoAlpha: 0.14,
            xPercent: 8,
            scaleX: 1.08,
            duration: 1.5 * scaleFactor,
            ease: 'sine.inOut',
        }, 1.98 * scaleFactor);
    }

    if (backdrop) {
        timeline.to(backdrop, {
            scale: 1,
            duration: 1.72 * scaleFactor,
            ease: 'sine.inOut',
        }, 1.98 * scaleFactor);
    }

    timeline.to(root, {
        autoAlpha: 0,
        duration: 0.96 * scaleFactor,
        ease: 'power2.out',
    }, 3.18 * scaleFactor);
}

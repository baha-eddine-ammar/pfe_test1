const clamp = (value, min = 0, max = 1) => Math.min(Math.max(value, min), max);
const smoothstep = (start, end, value) => {
    const amount = clamp((value - start) / Math.max(end - start, 0.0001));
    return amount * amount * (3 - 2 * amount);
};

const windowedVisibility = (progress, start, end, softness = 0.08) => {
    const fadeIn = smoothstep(start - softness, start + softness, progress);
    const fadeOut = 1 - smoothstep(end - softness, end + softness, progress);
    return clamp(Math.min(fadeIn, fadeOut));
};

const bandPass = (value, start, end, softness = 0.12) => {
    const fadeIn = smoothstep(start - softness, start + softness, value);
    const fadeOut = 1 - smoothstep(end - softness, end + softness, value);
    return clamp(Math.min(fadeIn, fadeOut));
};

const lerp = (start, end, progress) => start + (end - start) * progress;

const roundedRectPath = (ctx, x, y, width, height, radius) => {
    const safeRadius = Math.min(radius, width / 2, height / 2);

    ctx.beginPath();
    ctx.moveTo(x + safeRadius, y);
    ctx.lineTo(x + width - safeRadius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + safeRadius);
    ctx.lineTo(x + width, y + height - safeRadius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - safeRadius, y + height);
    ctx.lineTo(x + safeRadius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - safeRadius);
    ctx.lineTo(x, y + safeRadius);
    ctx.quadraticCurveTo(x, y, x + safeRadius, y);
    ctx.closePath();
};

class ImageSequenceRenderer {
    constructor(frameUrls, backgroundColor = '#050505') {
        this.frameUrls = frameUrls;
        this.backgroundColor = backgroundColor;
        this.images = new Array(frameUrls.length);
        this.loadedIndexes = new Set();
        this.ready = this.preload();
    }

    async preload() {
        if (!this.frameUrls.length) {
            return;
        }

        await new Promise((resolve) => {
            let remaining = this.frameUrls.length;
            let resolved = false;

            const settle = (loaded) => {
                remaining -= 1;

                if (loaded && !resolved) {
                    resolved = true;
                    resolve();
                } else if (remaining === 0 && !resolved) {
                    resolved = true;
                    resolve();
                }
            };

            this.frameUrls.forEach((src, index) => {
                const image = new Image();
                image.decoding = 'async';
                image.onload = () => {
                    this.images[index] = image;
                    this.loadedIndexes.add(index);
                    settle(true);
                };
                image.onerror = () => settle(false);
                image.src = src;
            });
        });
    }

    findNearestLoaded(index) {
        if (this.loadedIndexes.has(index)) {
            return this.images[index];
        }

        for (let distance = 1; distance < this.images.length; distance += 1) {
            const forward = index + distance;
            const backward = index - distance;

            if (forward < this.images.length && this.loadedIndexes.has(forward)) {
                return this.images[forward];
            }

            if (backward >= 0 && this.loadedIndexes.has(backward)) {
                return this.images[backward];
            }
        }

        return null;
    }

    draw({ ctx, width, height, progress }) {
        ctx.fillStyle = this.backgroundColor;
        ctx.fillRect(0, 0, width, height);

        if (!this.frameUrls.length) {
            return false;
        }

        const targetIndex = Math.round(progress * (this.frameUrls.length - 1));
        const frame = this.findNearestLoaded(targetIndex);

        if (!frame) {
            return false;
        }

        const ratio = Math.min(width / frame.width, height / frame.height);
        const drawWidth = frame.width * ratio;
        const drawHeight = frame.height * ratio;
        const x = (width - drawWidth) / 2;
        const y = (height - drawHeight) / 2;

        ctx.drawImage(frame, x, y, drawWidth, drawHeight);

        return true;
    }

    getExplodeProgress(progress) {
        return clamp(progress);
    }
}

class ProceduralRackRenderer {
    getExplodeProgress(progress) {
        if (progress < 0.16) {
            return 0;
        }

        if (progress < 0.58) {
            return smoothstep(0.16, 0.58, progress);
        }

        return 1;
    }

    drawBackdrop(ctx, width, height, progress) {
        ctx.fillStyle = '#050505';
        ctx.fillRect(0, 0, width, height);

        const radialGlow = ctx.createRadialGradient(
            width * 0.54,
            height * 0.42,
            0,
            width * 0.52,
            height * 0.5,
            Math.max(width, height) * 0.6,
        );
        radialGlow.addColorStop(0, 'rgba(0, 214, 255, 0.08)');
        radialGlow.addColorStop(0.45, 'rgba(0, 80, 255, 0.12)');
        radialGlow.addColorStop(1, 'rgba(5, 5, 5, 0)');

        ctx.fillStyle = radialGlow;
        ctx.fillRect(0, 0, width, height);

        ctx.save();
        ctx.globalAlpha = 0.04 + bandPass(progress, 0.22, 0.9, 0.2) * 0.05;
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.14)';
        ctx.lineWidth = 1;

        for (let index = 0; index < 6; index += 1) {
            const y = height * 0.24 + index * (height * 0.11);
            ctx.beginPath();
            ctx.moveTo(width * 0.12, y);
            ctx.lineTo(width * 0.88, y);
            ctx.stroke();
        }

        for (let index = 0; index < 9; index += 1) {
            const x = width * 0.16 + index * (width * 0.08);
            ctx.beginPath();
            ctx.moveTo(x, height * 0.16);
            ctx.lineTo(x, height * 0.84);
            ctx.stroke();
        }

        ctx.restore();
    }

    drawDoor(ctx, x, y, width, height, side, openness, scale) {
        ctx.save();
        ctx.translate(x, y);

        const travel = openness * 154 * scale * side;
        const skew = openness * 0.22 * side;

        ctx.transform(1, 0, skew, 1, travel, 0);

        const doorGradient = ctx.createLinearGradient(0, 0, width, height);
        doorGradient.addColorStop(0, '#1b1f25');
        doorGradient.addColorStop(1, '#07080a');

        roundedRectPath(ctx, 0, 0, width, height, 16 * scale);
        ctx.fillStyle = doorGradient;
        ctx.fill();

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
        ctx.lineWidth = 2 * scale;
        ctx.stroke();

        ctx.save();
        ctx.clip();
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
        ctx.lineWidth = 1;

        for (let row = 0; row < 22; row += 1) {
            const lineY = 22 * scale + row * 18 * scale;
            ctx.beginPath();
            ctx.moveTo(12 * scale, lineY);
            ctx.lineTo(width - 12 * scale, lineY);
            ctx.stroke();
        }
        ctx.restore();

        roundedRectPath(ctx, width * 0.74, height * 0.44, 12 * scale, 72 * scale, 6 * scale);
        ctx.fillStyle = 'rgba(255, 255, 255, 0.18)';
        ctx.fill();

        ctx.restore();
    }

    drawRackFrame(ctx, left, top, width, height, scale) {
        const frameGradient = ctx.createLinearGradient(left, top, left + width, top + height);
        frameGradient.addColorStop(0, '#2e333c');
        frameGradient.addColorStop(0.5, '#12151a');
        frameGradient.addColorStop(1, '#090b0e');

        roundedRectPath(ctx, left, top, width, height, 16 * scale);
        ctx.fillStyle = frameGradient;
        ctx.fill();

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.14)';
        ctx.lineWidth = 2 * scale;
        ctx.stroke();

        const railInset = 30 * scale;
        const railWidth = 10 * scale;

        ctx.fillStyle = '#060708';
        ctx.fillRect(left + railInset, top + 18 * scale, railWidth, height - 36 * scale);
        ctx.fillRect(left + width - railInset - railWidth, top + 18 * scale, railWidth, height - 36 * scale);

        ctx.strokeStyle = 'rgba(0, 214, 255, 0.1)';
        ctx.lineWidth = 1.4 * scale;

        for (let unit = 0; unit < 18; unit += 1) {
            const y = top + 28 * scale + unit * 30 * scale;
            ctx.beginPath();
            ctx.moveTo(left + railInset + 12 * scale, y);
            ctx.lineTo(left + width - railInset - 12 * scale, y);
            ctx.stroke();
        }
    }

    drawModule(ctx, x, y, width, height, emphasis, scale) {
        const moduleGradient = ctx.createLinearGradient(x, y, x + width, y + height);
        moduleGradient.addColorStop(0, '#202631');
        moduleGradient.addColorStop(1, '#090c11');

        roundedRectPath(ctx, x, y, width, height, 8 * scale);
        ctx.fillStyle = moduleGradient;
        ctx.fill();

        ctx.strokeStyle = `rgba(255, 255, 255, ${0.08 + emphasis * 0.12})`;
        ctx.lineWidth = 1.5 * scale;
        ctx.stroke();

        if (emphasis > 0.1) {
            ctx.strokeStyle = `rgba(0, 214, 255, ${0.12 + emphasis * 0.18})`;
            ctx.lineWidth = 2 * scale;
            roundedRectPath(ctx, x + 4 * scale, y + 4 * scale, width - 8 * scale, height - 8 * scale, 6 * scale);
            ctx.stroke();
        }
    }

    drawLedStrip(ctx, x, y, count, spacing, alpha, scale) {
        ctx.fillStyle = `rgba(0, 214, 255, ${alpha})`;

        for (let index = 0; index < count; index += 1) {
            roundedRectPath(ctx, x + index * spacing * scale, y, 10 * scale, 3 * scale, 1.5 * scale);
            ctx.fill();
        }
    }

    drawCable(ctx, points, alpha, scale) {
        ctx.save();
        ctx.strokeStyle = `rgba(188, 202, 214, ${alpha})`;
        ctx.lineWidth = 3.5 * scale;
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        ctx.bezierCurveTo(points[1].x, points[1].y, points[2].x, points[2].y, points[3].x, points[3].y);
        ctx.stroke();
        ctx.restore();
    }

    drawTray(ctx, x, y, width, height, extension, scale) {
        const offset = extension * 140 * scale;

        ctx.save();
        ctx.translate(-offset, 0);

        const trayGradient = ctx.createLinearGradient(x, y, x + width, y + height);
        trayGradient.addColorStop(0, '#1a1f29');
        trayGradient.addColorStop(1, '#080a0e');

        roundedRectPath(ctx, x, y, width, height, 6 * scale);
        ctx.fillStyle = trayGradient;
        ctx.fill();

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
        ctx.lineWidth = 1.4 * scale;
        ctx.stroke();

        this.drawLedStrip(ctx, x + 12 * scale, y + height - 10 * scale, 6, 16, 0.5, scale);
        ctx.restore();
    }

    draw({ ctx, width, height, progress, time }) {
        this.drawBackdrop(ctx, width, height, progress);

        const scale = Math.min(width / 1600, height / 950);
        const doorOpen = smoothstep(0.08, 0.34, progress);
        const systemReveal = bandPass(progress, 0.32, 0.74, 0.22);
        const serviceReveal = smoothstep(0.58, 0.88, progress);
        const breathing = Math.sin(time * 1.1) * 4 * scale;

        ctx.save();
        ctx.translate(width * 0.54, height * 0.53);

        const rackWidth = 340 * scale;
        const rackHeight = 630 * scale;
        const rackLeft = -rackWidth / 2;
        const rackTop = -rackHeight / 2;

        this.drawRackFrame(ctx, rackLeft, rackTop + breathing, rackWidth, rackHeight, scale);

        const modules = [
            { y: 42, h: 48, emphasis: 0.5 },
            { y: 98, h: 48, emphasis: 0.48 },
            { y: 170, h: 42, emphasis: 0.3 },
            { y: 222, h: 58, emphasis: 0.35 },
            { y: 294, h: 56, emphasis: 0.58 },
            { y: 362, h: 46, emphasis: 0.26 },
            { y: 418, h: 84, emphasis: 0.22 },
        ];

        modules.forEach((module, index) => {
            const localY = rackTop + module.y * scale + breathing;
            const localX = rackLeft + 42 * scale;
            const moduleWidth = rackWidth - 84 * scale;

            this.drawModule(ctx, localX, localY, moduleWidth, module.h * scale, module.emphasis * (0.55 + systemReveal), scale);

            if (index <= 2) {
                this.drawLedStrip(ctx, localX + 16 * scale, localY + module.h * scale - 12 * scale, 8, 16, 0.4 + systemReveal * 0.3, scale);
            }
        });

        this.drawTray(ctx, rackLeft + 42 * scale, rackTop + 286 * scale + breathing, rackWidth - 84 * scale, 56 * scale, serviceReveal * 0.6, scale);
        this.drawTray(ctx, rackLeft + 42 * scale, rackTop + 354 * scale + breathing, rackWidth - 84 * scale, 46 * scale, serviceReveal, scale);

        this.drawCable(ctx, [
            { x: rackLeft - 10 * scale, y: rackTop + 150 * scale },
            { x: rackLeft - 120 * scale, y: rackTop + 80 * scale },
            { x: rackLeft - 132 * scale, y: rackTop + 258 * scale },
            { x: rackLeft + 6 * scale, y: rackTop + 318 * scale },
        ], 0.46, scale);

        this.drawCable(ctx, [
            { x: rackLeft + rackWidth - 4 * scale, y: rackTop + 226 * scale },
            { x: rackLeft + rackWidth + 124 * scale, y: rackTop + 236 * scale },
            { x: rackLeft + rackWidth + 86 * scale, y: rackTop + 340 * scale },
            { x: rackLeft + rackWidth - 12 * scale, y: rackTop + 344 * scale },
        ], 0.42, scale);

        this.drawDoor(ctx, rackLeft - 126 * scale, rackTop + 30 * scale + breathing, 120 * scale, rackHeight - 60 * scale, -1, doorOpen, scale);
        this.drawDoor(ctx, rackLeft + rackWidth + 6 * scale, rackTop + 30 * scale + breathing, 120 * scale, rackHeight - 60 * scale, 1, doorOpen, scale);

        ctx.restore();

        return true;
    }
}

export function initServerRackLanding() {
    const root = document.querySelector('[data-server-rack-landing]');

    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    const canvas = root.querySelector('[data-xm6-canvas]');
    const nav = root.querySelector('[data-xm6-nav]');
    const loading = root.querySelector('[data-xm6-loading]');
    const progressFill = root.querySelector('[data-xm6-progress]');
    const storyTrack = root.querySelector('[data-story-track]');
    const beatElements = [...root.querySelectorAll('[data-beat]')].map((element) => ({
        element,
        start: Number.parseFloat(element.dataset.start || '0'),
        end: Number.parseFloat(element.dataset.end || '1'),
        align: element.dataset.align || 'center',
    }));
    const calloutElements = [...root.querySelectorAll('[data-callout]')].map((element) => ({
        element,
        start: Number.parseFloat(element.dataset.start || '0'),
        end: Number.parseFloat(element.dataset.end || '1'),
        shiftX: Number.parseFloat(element.dataset.shiftX || '0'),
        shiftY: Number.parseFloat(element.dataset.shiftY || '0'),
    }));
    const sectionLinks = [...root.querySelectorAll('[data-section-link]')].map((link) => ({
        link,
        target: document.querySelector(link.getAttribute('href')),
    })).filter(({ target }) => target);

    if (!canvas || !nav || !progressFill || !storyTrack) {
        return;
    }

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let viewportWidth = canvas.clientWidth;
    let viewportHeight = canvas.clientHeight;
    let targetProgress = 0;
    let currentProgress = 0;
    let lastFrameTime = performance.now();

    let frameUrls = [];
    const sequenceBackground = root.dataset.sequenceBackground || '#050505';

    try {
        frameUrls = JSON.parse(root.dataset.sequenceFrames || '[]');
    } catch (error) {
        frameUrls = [];
    }

    const renderer = frameUrls.length ? new ImageSequenceRenderer(frameUrls, sequenceBackground) : new ProceduralRackRenderer();

    const getSequenceProgress = (progress) => {
        if (progress < 0.15) {
            return lerp(0, 0.08, smoothstep(0, 0.15, progress));
        }

        if (progress < 0.65) {
            return lerp(0.08, 1, smoothstep(0.15, 0.65, progress));
        }

        if (progress < 0.85) {
            return 1;
        }

        return lerp(1, 0, smoothstep(0.85, 1, progress));
    };

    const hideLoading = () => {
        if (!loading) {
            return;
        }

        loading.classList.add('is-ready');
        window.setTimeout(() => {
            loading.setAttribute('hidden', 'hidden');
        }, 420);
    };

    if (frameUrls.length) {
        renderer.ready.then(hideLoading);
    } else if (loading) {
        loading.textContent = 'Rendering engineering showcase';
        window.setTimeout(hideLoading, 260);
    }

    const resizeCanvas = () => {
        const rect = canvas.getBoundingClientRect();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        viewportWidth = rect.width;
        viewportHeight = rect.height;
        canvas.width = Math.round(rect.width * dpr);
        canvas.height = Math.round(rect.height * dpr);
        canvas.style.width = `${rect.width}px`;
        canvas.style.height = `${rect.height}px`;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };

    const syncNavigation = () => {
        nav.classList.toggle('is-scrolled', window.scrollY > 20);

        const threshold = window.scrollY + window.innerHeight * 0.3;
        let activeId = sectionLinks[0]?.target.id;

        sectionLinks.forEach(({ target }) => {
            const absoluteTop = target.getBoundingClientRect().top + window.scrollY;

            if (absoluteTop <= threshold) {
                activeId = target.id;
            }
        });

        sectionLinks.forEach(({ link }) => {
            link.classList.toggle('is-active', link.getAttribute('href') === `#${activeId}`);
        });
    };

    const updateTargetProgress = () => {
        const trackTop = storyTrack.getBoundingClientRect().top + window.scrollY;
        const maxScroll = storyTrack.offsetHeight - window.innerHeight;
        const localScroll = window.scrollY - trackTop;

        targetProgress = maxScroll > 0 ? clamp(localScroll / maxScroll) : 0;
        syncNavigation();
    };

    const updateOverlayState = (progress, time) => {
        const sequenceProgress = getSequenceProgress(progress);
        const explode = renderer.getExplodeProgress(sequenceProgress);

        beatElements.forEach((beat) => {
            const visibility = windowedVisibility(progress, beat.start, beat.end, 0.08);
            const direction = beat.align === 'left' ? -1 : beat.align === 'right' ? 1 : 0;
            const translateX = (1 - visibility) * 54 * direction;
            const translateY = (1 - visibility) * 30;

            beat.element.style.opacity = visibility.toFixed(3);
            beat.element.style.setProperty('--offset-x', `${translateX}px`);
            beat.element.style.setProperty('--offset-y', `${translateY}px`);
            beat.element.classList.toggle('is-active', visibility > 0.14);
        });

        calloutElements.forEach((callout, index) => {
            const visibility = windowedVisibility(progress, callout.start, callout.end, 0.08) * explode;
            const floating = prefersReducedMotion ? 0 : Math.sin(time * 1.4 + index * 0.85) * 7 * visibility;

            callout.element.style.opacity = visibility.toFixed(3);
            callout.element.style.setProperty('--callout-offset-x', `${callout.shiftX * (1 - visibility)}px`);
            callout.element.style.setProperty('--callout-offset-y', `${callout.shiftY * (1 - visibility) + floating}px`);
        });

        progressFill.style.width = `${(progress * 100).toFixed(2)}%`;
    };

    const render = (time) => {
        const delta = Math.min(time - lastFrameTime, 64);
        lastFrameTime = time;

        if (prefersReducedMotion) {
            currentProgress = targetProgress;
        } else {
            const easing = 1 - Math.exp(-delta * 0.012);
            currentProgress += (targetProgress - currentProgress) * easing;
        }

        const sequenceProgress = getSequenceProgress(currentProgress);

        renderer.draw({
            ctx,
            width: viewportWidth,
            height: viewportHeight,
            progress: sequenceProgress,
            time: time / 1000,
        });

        updateOverlayState(currentProgress, time / 1000);
        window.requestAnimationFrame(render);
    };

    resizeCanvas();
    updateTargetProgress();
    syncNavigation();
    window.requestAnimationFrame(render);

    window.addEventListener('resize', () => {
        resizeCanvas();
        updateTargetProgress();
    });

    window.addEventListener('scroll', updateTargetProgress, { passive: true });
}

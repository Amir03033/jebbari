/*
 * Motion runtime (docs/MOTION.md). CSS does the animating; this file only
 * adds state classes. No animation library.
 */
const html = document.documentElement;
const reduceQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

/* Scroll reveals: once only, when the element has risen into the viewport. */
function initReveals() {
    const targets = document.querySelectorAll('[data-reveal]');

    if (!('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-in'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    observer.unobserve(entry.target);
                }
            }
        },
        // Fires when an element has risen 8% into the viewport: the same rule for
        // small and very tall blocks, on a phone and on a laptop.
        { threshold: 0, rootMargin: '0px 0px -8% 0px' },
    );

    targets.forEach((el) => observer.observe(el));
}

/* Replay control (used on /_style): reset a reveal without animating back. */
function initReplay() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-replay]');
        const scope = button && document.querySelector(button.dataset.replay);

        if (!scope) {
            return;
        }

        const targets = [scope, ...scope.querySelectorAll('[data-reveal]')];

        scope.classList.add('replaying');
        targets.forEach((el) => el.classList.remove('is-in'));
        void scope.offsetWidth;
        scope.classList.remove('replaying');
        void scope.offsetWidth;
        requestAnimationFrame(() => requestAnimationFrame(() => targets.forEach((el) => el.classList.add('is-in'))));
    });
}

/* "View case" label that follows the pointer. Fine pointers only. */
function initCursorLabel() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    const label = document.createElement('div');
    label.className = 'cursor-label t-label';
    label.setAttribute('aria-hidden', 'true');
    document.body.append(label);

    let x = 0;
    let y = 0;
    let frame = 0;

    const paint = () => {
        frame = 0;
        label.style.transform = `translate3d(${x + 18}px, ${y + 18}px, 0)`;
    };

    document.addEventListener('pointermove', (event) => {
        x = event.clientX;
        y = event.clientY;
        frame ||= requestAnimationFrame(paint);
    });

    document.querySelectorAll('[data-cursor]').forEach((el) => {
        el.addEventListener('pointerenter', () => {
            label.textContent = el.dataset.cursor;
            label.classList.add('is-on');
        });
        el.addEventListener('pointerleave', () => label.classList.remove('is-on'));
    });
}

/* Process timeline: steps light as they pass the 60% line of the viewport.
   The fill itself is CSS scroll-driven; where that is unsupported this also
   sets --progress. One passive, rAF-throttled listener; reads only. */
function initProcess() {
    const roots = document.querySelectorAll('[data-process]');

    if (!roots.length) {
        return;
    }

    let frame = 0;

    const update = () => {
        frame = 0;
        const line = window.innerHeight * 0.6;

        roots.forEach((root) => {
            let active = 1;

            root.querySelectorAll('[data-step]').forEach((step, index) => {
                const passed = step.getBoundingClientRect().top < line;
                step.classList.toggle('is-passed', passed);
                if (passed) {
                    active = index + 1;
                }
            });

            root.dataset.active = String(active);

            const rect = root.getBoundingClientRect();
            const progress = (line - rect.top) / rect.height;
            root.style.setProperty('--progress', Math.min(1, Math.max(0, progress)).toFixed(4));
        });
    };

    const schedule = () => (frame ||= requestAnimationFrame(update));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    update();
}

/*
 * Scroll-linked values. One passive, rAF-throttled listener writes --p (0 to 1)
 * on every [data-scroll] section. "exit": 0 at rest, 1 once it has scrolled
 * away. "enter": rises from 0 to 1 as it scrolls into view. CSS only reads --p,
 * so every browser follows exactly the same curve. Skipped under reduced motion.
 */
function initScrollLinked() {
    const items = document.querySelectorAll('[data-scroll]');

    if (reduceQuery.matches) {
        return;
    }

    let frame = 0;

    const update = () => {
        frame = 0;
        const vh = window.innerHeight;

        const page = document.documentElement;
        const max = page.scrollHeight - vh;

        page.style.setProperty('--page', (max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0).toFixed(4));

        items.forEach((el) => {
            const rect = el.getBoundingClientRect();
            const raw = el.dataset.scroll === 'exit' ? -rect.top / Math.max(rect.height, 1) : (vh - rect.top) / Math.min(Math.max(rect.height, 1), vh);

            el.style.setProperty('--p', Math.min(1, Math.max(0, raw)).toFixed(4));
        });
    };

    const schedule = () => (frame ||= requestAnimationFrame(update));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    update();
}

/*
 * Live status: really requests three of this site's own addresses (HEAD, no
 * cookies) every 20 seconds while the panel is on screen, and draws what came
 * back: latency bars and a verdict. Nothing is simulated.
 */
function initLive() {
    const root = document.querySelector('[data-live]');

    if (!root || !('fetch' in window)) {
        return;
    }

    const rows = [...root.querySelectorAll('[data-ping]')];
    const verdict = root.querySelector('[data-live-state]');
    const history = rows.map(() => []);
    let timer = 0;

    const probe = async (url) => {
        const start = performance.now();

        try {
            const response = await fetch(url, { method: 'HEAD', cache: 'no-store', credentials: 'omit' });

            return { ok: response.ok, ms: Math.round(performance.now() - start) };
        } catch {
            return { ok: false, ms: 0 };
        }
    };

    const run = async () => {
        const results = await Promise.all(rows.map((row) => probe(row.dataset.ping)));

        results.forEach((result, index) => {
            const row = rows[index];
            const list = history[index];
            const bars = row.querySelectorAll('.live-bar');

            list.push(result);
            if (list.length > bars.length) {
                list.shift();
            }

            const offset = bars.length - list.length;

            bars.forEach((bar, i) => {
                const entry = list[i - offset];

                bar.classList.toggle('is-on', Boolean(entry));
                if (entry) {
                    bar.style.setProperty('--h', Math.min(1, Math.max(0.15, entry.ms / 500)).toFixed(2));
                }
            });

            row.dataset.ok = String(result.ok);
            row.querySelector('[data-ping-ms]').textContent = result.ok ? String(result.ms) : '–';
        });

        const allOk = results.every((result) => result.ok);

        root.dataset.state = allOk ? 'ok' : 'bad';
        verdict.textContent = allOk ? root.dataset.ok : root.dataset.bad;
    };

    const start = () => {
        if (!timer) {
            run();
            timer = window.setInterval(run, 20000);
        }
    };

    const stop = () => {
        window.clearInterval(timer);
        timer = 0;
    };

    let visible = false;

    const sync = () => (visible && !document.hidden ? start() : stop());

    new IntersectionObserver((entries) => {
        visible = entries.some((entry) => entry.isIntersecting);
        sync();
    }).observe(root);
    document.addEventListener('visibilitychange', sync);
}

/* Pictures fade in once decoded. The frame keeps its size, so nothing shifts. */
function initImages() {
    document.querySelectorAll('picture img').forEach((img) => {
        const show = () => img.classList.add('is-loaded');

        if (img.complete) {
            show();
        } else {
            img.addEventListener('load', show, { once: true });
            img.addEventListener('error', show, { once: true });
        }
    });
}

/* Copy the email address, with a quiet confirmation read out by screen readers. */
function initCopy() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy]');

        if (!button) {
            return;
        }

        const status = button.parentElement.querySelector('[data-copy-status]');

        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            if (status) {
                status.textContent = button.dataset.copied;
                setTimeout(() => (status.textContent = ''), 2000);
            }
        } catch {
            // Clipboard unavailable: the address stays selectable and linked.
        }
    });
}

/* The header takes the surface beneath it: dark over black sections, light over paper. */
function initHeaderTheme() {
    const header = document.querySelector('.site-header');

    if (!header) {
        return;
    }

    let frame = 0;

    const update = () => {
        frame = 0;
        const menuOpen = document.documentElement.style.overflow === 'hidden';
        const point = document.elementsFromPoint(window.innerWidth / 2, Math.max(2, header.offsetHeight / 2));
        const below = point.find((el) => !header.contains(el) && el !== document.documentElement && el !== document.body);
        const dark = menuOpen || Boolean(below?.closest('.on-ink'));

        header.classList.toggle('on-ink', dark);
        header.dataset.theme = dark ? 'dark' : 'light';
    };

    const schedule = () => (frame ||= requestAnimationFrame(update));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    header.addEventListener('click', () => setTimeout(schedule, 0));
    update();
}

function initStatus() {
    const write = () =>
        document.querySelectorAll('[data-motion-status]').forEach((el) => {
            el.textContent = reduceQuery.matches ? 'Reduce (opacity fades only)' : 'No preference (full motion)';
        });
    write();
    reduceQuery.addEventListener('change', write);
}

export function initMotion() {
    html.classList.add('motion-ready');
    initReveals();
    initReplay();
    initCursorLabel();
    initProcess();
    initScrollLinked();
    initLive();
    initImages();
    initCopy();
    initHeaderTheme();
    initStatus();
}

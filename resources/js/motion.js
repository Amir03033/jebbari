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

const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

/*
 * Cursor: a quiet ring that trails the pointer and swells over links, buttons
 * and project tiles, plus the "View case" label on tiles. The system cursor is
 * never hidden. Fine pointers only, off under reduced motion.
 */
function initCursor() {
    if (!finePointer.matches) {
        return;
    }

    const label = document.createElement('div');
    label.className = 'cursor-label t-label';
    label.setAttribute('aria-hidden', 'true');
    document.body.append(label);

    const ring = document.createElement('div');
    ring.className = 'cursor-ring';
    ring.setAttribute('aria-hidden', 'true');
    const useRing = !reduceQuery.matches;

    if (useRing) {
        document.body.append(ring);
    }

    let x = 0;
    let y = 0;
    let rx = 0;
    let ry = 0;
    let scale = 1;
    let target = 1;
    let frame = 0;
    let seen = false;

    const tick = () => {
        frame = 0;
        rx += (x - rx) * 0.22;
        ry += (y - ry) * 0.22;
        scale += (target - scale) * 0.2;
        ring.style.transform = `translate3d(${rx}px, ${ry}px, 0) translate(-50%, -50%) scale(${scale.toFixed(3)})`;

        if (Math.abs(x - rx) > 0.2 || Math.abs(y - ry) > 0.2 || Math.abs(target - scale) > 0.01) {
            frame = requestAnimationFrame(tick);
        }
    };

    document.addEventListener('pointermove', (event) => {
        x = event.clientX;
        y = event.clientY;
        label.style.transform = `translate3d(${x + 18}px, ${y + 18}px, 0)`;

        if (useRing) {
            if (!seen) {
                seen = true;
                rx = x;
                ry = y;
                ring.classList.add('is-on');
            }

            const hit = event.target.closest?.('a, button, [data-cursor], summary, label');
            const field = event.target.closest?.('input, textarea, select');

            target = field ? 0.4 : hit ? 1.9 : 1;
            ring.classList.toggle('is-hidden', Boolean(field));
            frame ||= requestAnimationFrame(tick);
        }
    });

    document.documentElement.addEventListener('pointerleave', () => ring.classList.remove('is-on'));
    document.documentElement.addEventListener('pointerenter', () => seen && ring.classList.add('is-on'));

    document.querySelectorAll('[data-cursor]').forEach((el) => {
        el.addEventListener('pointerenter', () => {
            label.textContent = el.dataset.cursor;
            label.classList.add('is-on');
        });
        el.addEventListener('pointerleave', () => label.classList.remove('is-on'));
    });
}

/* Buttons and tile arrows lean a little toward the pointer. Fine pointers only. */
function initMagnet() {
    if (!finePointer.matches || reduceQuery.matches) {
        return;
    }

    document.querySelectorAll('.btn:not([disabled]), .theme-toggle').forEach((el) => {
        const strength = el.classList.contains('btn') ? 0.18 : 0.3;

        el.addEventListener('pointermove', (event) => {
            const rect = el.getBoundingClientRect();
            const dx = event.clientX - (rect.left + rect.width / 2);
            const dy = event.clientY - (rect.top + rect.height / 2);

            el.style.translate = `${(dx * strength).toFixed(1)}px ${(dy * strength).toFixed(1)}px`;
        });
        el.addEventListener('pointerleave', () => (el.style.translate = ''));
    });
}

/*
 * Navigation indicator: a hairline rests under the current page and glides to
 * the link under the pointer or keyboard focus. Only its transform changes.
 */
function initNav() {
    const nav = document.querySelector('[data-nav]');
    const line = nav?.querySelector('.nav-line');

    if (!nav || !line) {
        return;
    }

    const links = [...nav.querySelectorAll('a')];
    const current = () => nav.querySelector('[aria-current="page"]');

    const moveTo = (link) => {
        if (!link) {
            line.style.setProperty('--no', '0');
            return;
        }

        line.style.setProperty('--nx', `${link.offsetLeft}px`);
        line.style.setProperty('--nw', String(link.offsetWidth));
        line.style.setProperty('--no', '1');
    };

    links.forEach((link) => {
        link.addEventListener('pointerenter', () => moveTo(link));
        link.addEventListener('focus', () => moveTo(link));
    });
    nav.addEventListener('pointerleave', () => moveTo(current()));
    nav.addEventListener('focusout', (event) => nav.contains(event.relatedTarget) || moveTo(current()));
    window.addEventListener('resize', () => moveTo(current()), { passive: true });
    document.fonts?.ready.then(() => moveTo(current()));
    moveTo(current());
}

/*
 * Hero light: the pool of light behind the headline leans a little toward the
 * pointer. One rAF-throttled write of two custom properties; the easing is a
 * CSS transition, so nothing runs while the pointer rests.
 */
function initField() {
    const field = document.querySelector('[data-field]');
    const stage = field?.parentElement;

    if (!field || !finePointer.matches || reduceQuery.matches) {
        return;
    }

    let frame = 0;
    let x = 0;
    let y = 0;

    const paint = () => {
        frame = 0;
        field.style.setProperty('--gx', `${x.toFixed(1)}px`);
        field.style.setProperty('--gy', `${y.toFixed(1)}px`);
    };

    stage.addEventListener('pointermove', (event) => {
        const rect = stage.getBoundingClientRect();

        x = (event.clientX / rect.width - 0.5) * 90;
        y = ((event.clientY - rect.top) / rect.height - 0.5) * 70;
        frame ||= requestAnimationFrame(paint);
    });
    stage.addEventListener('pointerleave', () => {
        x = 0;
        y = 0;
        frame ||= requestAnimationFrame(paint);
    });
}

/* "Back to top": a smooth return (instant under reduced motion), then focus lands on the page. */
function initToTop() {
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-to-top]')) {
            return;
        }

        event.preventDefault();
        window.scrollTo({ top: 0, behavior: reduceQuery.matches ? 'auto' : 'smooth' });
        document.querySelector('.site-brand')?.focus({ preventScroll: true });
    });
}

/* Words that fill from faint to full as the paragraph passes through the viewport. */
function initFill() {
    if (reduceQuery.matches) {
        return;
    }

    document.querySelectorAll('[data-fill]').forEach((el) => {
        const words = el.textContent.trim().split(/\s+/);

        el.textContent = '';
        el.style.setProperty('--n', String(words.length));
        el.style.setProperty('--fill', '0');

        words.forEach((word, index) => {
            const span = document.createElement('span');

            span.className = 'fill-w';
            span.textContent = word;
            span.style.setProperty('--i', String(index));
            el.append(span, index < words.length - 1 ? ' ' : '');
        });
    });
}

/* Process timeline: steps light as they pass the 60% line of the viewport and
   the hairline fills (--progress). One passive, rAF-throttled listener. */
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
 * on every [data-scroll] element. "exit": 0 at rest, 1 once it has scrolled
 * away. "enter": rises from 0 to 1 as it scrolls into view. "stack": 0 to 1 as
 * the next card covers this one. CSS only reads --p,
 * so every browser follows exactly the same curve. Skipped under reduced motion.
 */
function initScrollLinked() {
    const items = document.querySelectorAll('[data-scroll], [data-fill]');

    if (reduceQuery.matches) {
        return;
    }

    let frame = 0;

    // Progress 0 to 1 for each kind of scroll-linked element.
    const progress = (el, rect, vh) => {
        const mode = el.dataset.scroll || 'fill';

        switch (mode) {
            case 'exit':
                return -rect.top / Math.max(rect.height, 1);
            case 'stack': {
                // How far the next card has slid over this one.
                const next = el.nextElementSibling;

                return next ? 1 - (next.getBoundingClientRect().top - rect.top) / Math.max(rect.height, 1) : 0;
            }
            case 'fill':
                return (vh * 0.9 - rect.top) / (vh * 0.5 + rect.height);
            default:
                return (vh - rect.top) / Math.min(Math.max(rect.height, 1), vh);
        }
    };

    const update = () => {
        frame = 0;
        const vh = window.innerHeight;
        const page = document.documentElement;
        const max = page.scrollHeight - vh;

        page.style.setProperty('--page', (max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0).toFixed(4));

        items.forEach((el) => {
            const rect = el.getBoundingClientRect();

            // Far from the screen only the end values matter: skip the work.
            if (rect.bottom < -vh * 1.5 || rect.top > vh * 2.5) {
                return;
            }

            const value = Math.min(1, Math.max(0, progress(el, rect, vh))).toFixed(4);

            if (el.dataset.fill !== undefined && !el.dataset.scroll) {
                el.style.setProperty('--fill', value);
            } else {
                el.style.setProperty('--p', value);
            }
        });
    };

    const schedule = () => (frame ||= requestAnimationFrame(update));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    window.addEventListener('load', schedule);
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

/* Figures count up once, when they scroll into view. The final value is already in the page. */
function initCounters() {
    const targets = [...document.querySelectorAll('[data-count]')];

    if (!targets.length || reduceQuery.matches || !('IntersectionObserver' in window)) {
        return;
    }

    const format = new Intl.NumberFormat(document.documentElement.lang || 'nl');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const el = entry.target;
                const end = Number(el.dataset.count);
                const start = performance.now();
                const duration = 1400;

                observer.unobserve(el);

                const step = (now) => {
                    const t = Math.min(1, (now - start) / duration);
                    const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);

                    el.textContent = format.format(Math.round(end * eased));
                    if (t < 1) {
                        requestAnimationFrame(step);
                    }
                };

                requestAnimationFrame(step);
            });
        },
        { threshold: 0.6 },
    );

    targets.forEach((el) => {
        el.textContent = format.format(0);
        observer.observe(el);
    });
}

/* Light/dark switch. The first visit follows the system; a choice is remembered in this browser. */
function initTheme() {
    const root = document.documentElement;
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    const isDark = () => (root.dataset.theme ? root.dataset.theme === 'dark' : system.matches);

    const sync = () =>
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.setAttribute('aria-pressed', String(isDark())));

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-theme-toggle]')) {
            return;
        }

        const next = isDark() ? 'light' : 'dark';

        const apply = () => {
            root.dataset.theme = next;
            sync();
        };

        try {
            localStorage.setItem('theme', next);
        } catch {
            // Storage unavailable: the choice lasts for this page view only.
        }

        // A soft cross-fade between the themes where the browser can do it.
        if (!document.startViewTransition || reduceQuery.matches) {
            apply();
            return;
        }

        root.classList.add('theme-switching');
        document.startViewTransition(apply).finished.finally(() => root.classList.remove('theme-switching'));
    });

    system.addEventListener('change', sync);
    sync();
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
    initNav();
    initField();
    initCursor();
    initMagnet();
    initToTop();
    initFill();
    initProcess();
    initScrollLinked();
    initLive();
    initImages();
    initTheme();
    initCounters();
    initCopy();
    initStatus();
}

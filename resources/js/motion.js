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

/* Project tiles: a small label names the action and follows the pointer. Fine pointers only; the system cursor stays as it is. */
function initCursor() {
    const tiles = document.querySelectorAll('[data-cursor]');

    if (!finePointer.matches || !tiles.length) {
        return;
    }

    const label = document.createElement('div');

    label.className = 'cursor-label t-label';
    label.setAttribute('aria-hidden', 'true');
    document.body.append(label);

    tiles.forEach((el) => {
        el.addEventListener('pointerenter', () => {
            label.textContent = el.dataset.cursor;
            label.classList.add('is-on');
        });
        el.addEventListener('pointermove', (event) => (label.style.transform = `translate3d(${event.clientX + 18}px, ${event.clientY + 18}px, 0)`));
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
        // Written on the stage, so the light and the project showcase both read it.
        stage.style.setProperty('--gx', `${x.toFixed(1)}px`);
        stage.style.setProperty('--gy', `${y.toFixed(1)}px`);
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

/*
 * Command menu: Ctrl/Cmd + K or "/" opens a native dialog; typing filters the
 * list, arrows move through it, Enter opens. A few entries stay hidden until
 * their name is typed.
 */
function initCommand() {
    const dialog = document.querySelector('[data-cmd]');

    if (!dialog || typeof dialog.showModal !== 'function') {
        document.querySelectorAll('[data-cmd-open]').forEach((el) => (el.hidden = true));
        return;
    }

    const input = dialog.querySelector('[data-cmd-input]');
    const items = [...dialog.querySelectorAll('[data-cmd-item]')];
    const groups = [...dialog.querySelectorAll('[data-cmd-group]')];
    const empty = dialog.querySelector('[data-cmd-empty]');
    let active = -1;

    const visible = () => items.filter((item) => !item.hidden);

    const mark = (index) => {
        const list = visible();

        active = list.length ? (index + list.length) % list.length : -1;
        items.forEach((item) => item.classList.remove('is-active'));
        list[active]?.classList.add('is-active');
        list[active]?.scrollIntoView({ block: 'nearest' });
    };

    const filter = () => {
        const query = input.value.trim().toLowerCase();

        items.forEach((item) => {
            const secret = item.dataset.cmdSecret;
            const text = (secret ?? item.textContent).toLowerCase();

            item.hidden = secret ? query.length < 3 || !text.split(' ').some((word) => word.startsWith(query)) : !text.includes(query);
        });
        groups.forEach((group) => (group.hidden = !group.querySelector('[data-cmd-item]:not([hidden])')));
        empty.hidden = visible().length > 0;
        mark(0);
    };

    const open = () => {
        if (dialog.open) {
            return;
        }

        // The mobile menu steps aside first.
        document.querySelector('.menu-toggle[aria-expanded="true"]')?.click();
        input.value = '';
        dialog.showModal();
        filter();
        input.focus();
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-cmd-open]')) {
            open();
        } else if (event.target === dialog || event.target.closest('[data-cmd] [data-egg]')) {
            dialog.close();
        }
    });

    document.addEventListener('keydown', (event) => {
        const typing = event.target.closest?.('input, textarea, select, [contenteditable]');

        if ((event.key === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
            event.preventDefault();
            dialog.open ? dialog.close() : open();
        }
    });

    input.addEventListener('input', filter);
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            mark(active + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter' && document.activeElement === input) {
            event.preventDefault();
            visible()[active]?.click();
        }
    });

    // The shortcut hint matches the visitor's keyboard.
    if (!/Mac|iPhone|iPad/.test(navigator.platform)) {
        document.querySelectorAll('.cmd-kbd').forEach((el) => (el.textContent = 'Ctrl K'));
    }
}

/* Work index: show the projects that use the chosen technology. Others fade out, then leave the grid. */
function initFilter() {
    document.querySelectorAll('[data-filter-root]').forEach((root) => {
        const chips = [...root.querySelectorAll('[data-filter]')];
        const cards = [...root.querySelectorAll('[data-tags]')];
        const empty = root.querySelector('[data-filter-empty]');

        chips.forEach((chip) =>
            chip.addEventListener('click', () => {
                const tag = chip.dataset.filter;
                let shown = 0;

                chips.forEach((other) => other.setAttribute('aria-pressed', String(other === chip)));

                cards.forEach((card) => {
                    const match = !tag || card.dataset.tags.split('|').includes(tag);

                    shown += match ? 1 : 0;

                    if (match) {
                        card.hidden = false;
                        requestAnimationFrame(() => card.classList.remove('is-out'));
                    } else {
                        card.classList.add('is-out');
                        setTimeout(() => (card.hidden = card.classList.contains('is-out')), reduceQuery.matches ? 0 : 400);
                    }
                });

                if (empty) {
                    empty.hidden = shown > 0;
                }
            }),
        );
    });
}

/* Before and after: the range input is the control; the frame only mirrors its value. */
function initCompare() {
    document.querySelectorAll('[data-compare]').forEach((root) => {
        const range = root.querySelector('.compare-range');
        const sync = () => root.style.setProperty('--cut', `${range.value}%`);

        range.addEventListener('input', sync);
        sync();
    });
}

/* Live demo: the frame is created only when the visitor asks for it. */
function initDemo() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-demo-load]');
        const root = button?.closest('[data-demo]');

        if (!root) {
            return;
        }

        const frame = document.createElement('iframe');

        frame.src = root.dataset.demo;
        frame.title = root.dataset.demoTitle;
        frame.loading = 'lazy';
        frame.referrerPolicy = 'no-referrer';
        frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-popups');
        root.querySelector('.demo-stage').replaceChildren(frame);
    });
}

/* Article contents: the heading you are reading is marked in the list. */
function initToc() {
    const toc = document.querySelector('[data-toc]');

    if (!toc || !('IntersectionObserver' in window)) {
        return;
    }

    const links = new Map([...toc.querySelectorAll('a')].map((link) => [decodeURIComponent(link.hash.slice(1)), link]));
    const headings = [...links.keys()].map((id) => document.getElementById(id)).filter(Boolean);

    const observer = new IntersectionObserver(
        () => {
            const line = window.innerHeight * 0.3;
            const current = headings.filter((heading) => heading.getBoundingClientRect().top < line).pop() ?? headings[0];

            links.forEach((link, id) => (id === current?.id ? link.setAttribute('aria-current', 'true') : link.removeAttribute('aria-current')));
        },
        { rootMargin: '0px 0px -70% 0px', threshold: [0, 1] },
    );

    headings.forEach((heading) => observer.observe(heading));
}

/*
 * Pointer light and lean. Cards marked .spot get a pool of light under the
 * pointer; case visuals lean a few degrees toward it. One listener per card,
 * writes custom properties only. Fine pointers, full motion only.
 */
function initSpot() {
    if (!finePointer.matches || reduceQuery.matches) {
        return;
    }

    document.querySelectorAll('.spot').forEach((el) => {
        el.addEventListener('pointermove', (event) => {
            const rect = el.getBoundingClientRect();

            el.style.setProperty('--sx', `${(event.clientX - rect.left).toFixed(0)}px`);
            el.style.setProperty('--sy', `${(event.clientY - rect.top).toFixed(0)}px`);
        });
    });

    document.querySelectorAll('.case').forEach((card) => {
        const visual = card.querySelector('.case-visual');

        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect();

            visual.style.setProperty('--ry', `${(((event.clientX - rect.left) / rect.width - 0.5) * 5).toFixed(2)}deg`);
            visual.style.setProperty('--rx', `${(((event.clientY - rect.top) / rect.height - 0.5) * -4).toFixed(2)}deg`);
        });
        card.addEventListener('pointerleave', () => {
            visual.style.setProperty('--rx', '0deg');
            visual.style.setProperty('--ry', '0deg');
        });
    });
}

/* Share: the device's own share sheet where there is one; the plain links work everywhere. */
function initShare() {
    document.querySelectorAll('[data-share]').forEach((button) => {
        if (!navigator.share) {
            return;
        }

        button.hidden = false;
        button.addEventListener('click', () => navigator.share({ title: button.dataset.shareTitle, url: button.dataset.share }).catch(() => {}));
    });
}

function initPrint() {
    document.addEventListener('click', (event) => event.target.closest('[data-print]') && window.print());
}

/*
 * Easter eggs. None of them is needed to use the site, and each one is off or
 * instant under reduced motion.
 *  - The Konami code (or "blueprint" in the command menu) draws the layout grid.
 *  - Typing the name of the site (or "confetti" in the command menu) celebrates.
 *  - The console says hello.
 */
function initEggs() {
    const found = document.querySelector('[data-egg="blueprint"]')?.closest('[data-cmd]')?.dataset.found ?? '';
    let toastTimer = 0;

    const toast = (text) => {
        let el = document.querySelector('.toast');

        if (!el) {
            el = document.createElement('p');
            el.className = 'toast t-label';
            el.setAttribute('role', 'status');
            document.body.append(el);
        }

        el.textContent = text;
        requestAnimationFrame(() => el.classList.add('is-on'));
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('is-on'), 2200);
    };

    const blueprint = () => {
        const existing = document.querySelector('.blueprint');

        if (existing) {
            existing.remove();
            document.querySelector('.blueprint-read')?.remove();
            return;
        }

        const grid = document.createElement('div');
        const read = document.createElement('p');

        grid.className = 'blueprint';
        grid.setAttribute('aria-hidden', 'true');
        grid.append(...Array.from({ length: 12 }, () => document.createElement('span')));
        read.className = 'blueprint-read t-label';
        read.setAttribute('aria-hidden', 'true');

        const size = () => (read.textContent = `${window.innerWidth} × ${window.innerHeight} · 12 col`);

        size();
        window.addEventListener('resize', size, { passive: true });
        document.body.append(grid, read);
        toast(found);
    };

    const confetti = () => {
        toast(found);

        if (reduceQuery.matches || !Element.prototype.animate) {
            return;
        }

        const holder = document.createElement('div');

        holder.setAttribute('aria-hidden', 'true');
        document.body.append(holder);

        for (let i = 0; i < 36; i++) {
            const bit = document.createElement('span');
            const angle = (Math.PI * 2 * i) / 36 + Math.random() * 0.4;
            const reach = 140 + Math.random() * 260;
            const x = window.innerWidth / 2;
            const y = window.innerHeight * 0.55;

            bit.className = 'egg-bit';
            holder.append(bit);
            bit.animate(
                [
                    { transform: `translate(${x}px, ${y}px) scale(0.4)`, opacity: 1 },
                    { transform: `translate(${x + Math.cos(angle) * reach}px, ${y + Math.sin(angle) * reach - 120}px) scale(1)`, opacity: 1, offset: 0.55 },
                    { transform: `translate(${x + Math.cos(angle) * reach * 1.15}px, ${y + Math.sin(angle) * reach + 260}px) scale(0.6)`, opacity: 0 },
                ],
                { duration: 1500 + Math.random() * 500, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' },
            );
        }

        setTimeout(() => holder.remove(), 2200);
    };

    const run = { blueprint, confetti };

    document.addEventListener('click', (event) => {
        const name = event.target.closest('[data-egg]')?.dataset.egg;

        run[name]?.();
    });

    const konami = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
    const word = 'jebbari';
    let keys = [];
    let typed = '';

    document.addEventListener('keydown', (event) => {
        if (event.target.closest?.('input, textarea, select, [contenteditable]')) {
            return;
        }

        keys = [...keys, event.key].slice(-konami.length);
        typed = (typed + event.key.toLowerCase()).slice(-word.length);

        if (keys.join() === konami.join()) {
            keys = [];
            blueprint();
        } else if (typed === word) {
            typed = '';
            confetti();
        }
    });

    console.log('%cjebbari%c.', 'font: 500 28px system-ui; letter-spacing: -0.04em', 'font: 500 28px system-ui; color: #8ea2ff');
    console.log('Curious how it is built? Blade, Tailwind and one small script. Try Ctrl/Cmd + K, and type "blueprint".');
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

/*
 * Scroll engine. One passive listener and one animation frame for everything
 * that follows the scroll position. Each job first measures (only reads layout),
 * then all jobs write. Reading and writing in turn would make the browser
 * recalculate the whole page for every element: that was the lag. Values are
 * only written when they change.
 */
const scrollJobs = [];

function onScrollFrame(measure, apply) {
    scrollJobs.push({ measure, apply });
}

/** Writes a custom property only when its value changed (no needless style work). */
const lastValues = new WeakMap();

function setVar(el, name, value) {
    let seen = lastValues.get(el);

    if (!seen) {
        seen = {};
        lastValues.set(el, seen);
    }

    if (seen[name] !== value) {
        seen[name] = value;
        el.style.setProperty(name, value);
    }
}

function startScrollEngine() {
    if (!scrollJobs.length) {
        return;
    }

    let frame = 0;

    const run = () => {
        frame = 0;
        const vh = window.innerHeight;
        const measured = scrollJobs.map((job) => job.measure(vh));

        scrollJobs.forEach((job, i) => job.apply(measured[i]));
    };

    const schedule = () => (frame ||= requestAnimationFrame(run));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    window.addEventListener('load', schedule);
    run();
}

const clamp01 = (n) => Math.min(1, Math.max(0, n));

/* Process timeline: steps light as they pass the 60% line of the viewport and
   the hairline fills (--progress). */
function initProcess() {
    const roots = [...document.querySelectorAll('[data-process]')].map((root) => ({ root, steps: [...root.querySelectorAll('[data-step]')] }));

    if (!roots.length) {
        return;
    }

    onScrollFrame(
        (vh) => {
            const line = vh * 0.6;

            return roots.map(({ root, steps }) => {
                const rect = root.getBoundingClientRect();

                // Far from the screen nothing changes visibly: measure the steps only nearby.
                if (rect.bottom < -vh || rect.top > vh * 2) {
                    return null;
                }

                return { passed: steps.map((step) => step.getBoundingClientRect().top < line), progress: clamp01((line - rect.top) / rect.height) };
            });
        },
        (results) => {
            results.forEach((result, r) => {
                if (!result) {
                    return;
                }

                const { root, steps } = roots[r];
                let active = 1;

                result.passed.forEach((passed, i) => {
                    if (steps[i].classList.contains('is-passed') !== passed) {
                        steps[i].classList.toggle('is-passed', passed);
                    }
                    if (passed) {
                        active = i + 1;
                    }
                });

                if (root.dataset.active !== String(active)) {
                    root.dataset.active = String(active);
                }

                setVar(root, '--progress', result.progress.toFixed(3));
            });
        },
    );
}

/*
 * Scroll-linked values: --p (0 to 1) on every [data-scroll] element. "exit": 0 at
 * rest, 1 once it has scrolled away. "enter": rises from 0 to 1 as it scrolls
 * into view. "stack": 0 to 1 as the next card covers this one. "read": through
 * an article. [data-fill] gets --fill. Page progress (--page) goes only to the
 * reading line in the header, not to the whole document. Skipped under reduced motion.
 */
function initScrollLinked() {
    if (reduceQuery.matches) {
        return;
    }

    const items = [...document.querySelectorAll('[data-scroll], [data-fill]')].map((el) => ({
        el,
        mode: el.dataset.scroll || 'fill',
        prop: el.dataset.fill !== undefined && !el.dataset.scroll ? '--fill' : '--p',
        next: el.dataset.scroll === 'stack' ? el.nextElementSibling : null,
    }));
    const lines = [...document.querySelectorAll('.scroll-line')];

    const progress = (item, rect, vh) => {
        switch (item.mode) {
            case 'exit':
                return -rect.top / Math.max(rect.height, 1);
            case 'read':
                return (vh * 0.5 - rect.top) / Math.max(rect.height, 1);
            case 'stack':
                // How far the next card has slid over this one.
                return item.next ? 1 - (item.next.getBoundingClientRect().top - rect.top) / Math.max(rect.height, 1) : 0;
            case 'fill':
                return (vh * 0.9 - rect.top) / (vh * 0.5 + rect.height);
            default:
                return (vh - rect.top) / Math.min(Math.max(rect.height, 1), vh);
        }
    };

    onScrollFrame(
        (vh) => {
            const max = document.documentElement.scrollHeight - vh;
            const page = max > 0 ? clamp01(window.scrollY / max) : 0;

            const values = items.map((item) => {
                const rect = item.el.getBoundingClientRect();

                // Far from the screen only the end values matter: skip the work.
                if (rect.bottom < -vh * 1.5 || rect.top > vh * 2.5) {
                    return null;
                }

                return clamp01(progress(item, rect, vh)).toFixed(3);
            });

            return { page: page.toFixed(3), values };
        },
        ({ page, values }) => {
            lines.forEach((line) => setVar(line, '--page', page));
            values.forEach((value, i) => value !== null && setVar(items[i].el, items[i].prop, value));
        },
    );
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

/* Light/dark switch. The site opens light; choosing dark is remembered in this browser. */
function initTheme() {
    const root = document.documentElement;
    const isDark = () => root.dataset.theme === 'dark';

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

        // The circle grows from the centre of the switch to the farthest corner.
        const box = event.target.closest('[data-theme-toggle]').getBoundingClientRect();
        const x = box.left + box.width / 2;
        const y = box.top + box.height / 2;
        const r = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y));

        root.style.setProperty('--tx', `${x.toFixed(0)}px`);
        root.style.setProperty('--ty', `${y.toFixed(0)}px`);
        root.style.setProperty('--tr', `${r.toFixed(0)}px`);
        root.classList.add('theme-switching');
        document.startViewTransition(apply).finished.finally(() => root.classList.remove('theme-switching'));
    });

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
    initCommand();
    initFilter();
    initCompare();
    initDemo();
    initToc();
    initSpot();
    initPrint();
    initShare();
    initEggs();
    initFill();
    initProcess();
    initScrollLinked();
    startScrollEngine();
    initLive();
    initImages();
    initTheme();
    initCounters();
    initCopy();
    initStatus();
}

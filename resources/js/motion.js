/*
 * Motion runtime (docs/MOTION.md). CSS does the animating; this file only
 * adds state classes. No animation library.
 */
const html = document.documentElement;
const reduceQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

/* Scroll reveals: once only, when 15% of the element is visible. */
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
        { threshold: 0.15 },
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

    const driveFill = !CSS.supports('animation-timeline', 'view()');
    let frame = 0;

    const update = () => {
        frame = 0;
        const line = window.innerHeight * 0.6;

        roots.forEach((root) => {
            root.querySelectorAll('[data-step]').forEach((step) => {
                step.classList.toggle('is-passed', step.getBoundingClientRect().top < line);
            });

            if (driveFill) {
                const rect = root.getBoundingClientRect();
                const progress = (line - rect.top) / rect.height;
                root.style.setProperty('--progress', String(Math.min(1, Math.max(0, progress))));
            }
        });
    };

    const schedule = () => (frame ||= requestAnimationFrame(update));

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
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
    initStatus();
}

/*
 * Website check, waiting page: polls the status and reloads once the report is
 * ready. Meanwhile it paces the four phases on the elapsed time (the real work
 * runs on the server) and names the check that is "on" now. Only transform and
 * opacity change; with reduced motion the phases still fill, nothing cycles.
 */
const PHASES = [6, 10, 30, 4]; // expected seconds: fetch, security + SEO, PageSpeed, report

export function initScanStatus() {
    const root = document.querySelector('[data-scan-live]');

    if (!root) {
        return;
    }

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const url = root.dataset.statusUrl;
    const started = Number(root.dataset.started) * 1000;
    const phases = [...root.querySelectorAll('[data-scan-phase]')];
    const now = root.querySelector('[data-scan-now]');
    const text = root.querySelector('[data-scan-text]');
    let checks = [];
    let status = root.dataset.status;
    let index = 0;

    try {
        checks = JSON.parse(root.dataset.checks || '[]');
    } catch {
        checks = [];
    }

    root.classList.add('is-live');

    const paint = () => {
        root.dataset.status = status;

        if (status !== 'running') {
            return;
        }

        // Elapsed time spread over the phases; the last one waits at 92% until the server is done.
        let left = Math.max(0, (Date.now() - Math.min(started, Date.now())) / 1000);

        phases.forEach((li, i) => {
            const share = Math.min(1, left / PHASES[i]);
            left = Math.max(0, left - PHASES[i]);
            const progress = i === phases.length - 1 ? Math.min(share, 0.92) : share;
            li.querySelector('.scan-phase-fill span').style.transform = `scaleX(${progress})`;
            li.dataset.state = share >= 1 && i < phases.length - 1 ? 'done' : share > 0 ? 'active' : 'idle';
        });
    };

    const cycle = () => {
        if (reduce || status !== 'running' || checks.length === 0 || !now) {
            return;
        }

        index = (index + 1) % checks.length;
        now.classList.remove('is-in');
        // Restart the small rise for each new name.
        void now.offsetWidth;
        now.textContent = checks[index];
        now.classList.add('is-in');
    };

    const poll = async () => {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            const data = await response.json();

            if (data.status === 'done' || data.status === 'failed') {
                phases.forEach((li) => {
                    li.dataset.state = 'done';
                    li.querySelector('.scan-phase-fill span').style.transform = 'scaleX(1)';
                });
                setTimeout(() => window.location.reload(), reduce ? 0 : 500);

                return;
            }

            if (data.status !== status) {
                status = data.status;
                if (text && text.dataset[status]) {
                    text.textContent = text.dataset[status];
                }
            }
        } catch {
            // A missed poll is fine: try again on the next tick.
        }

        setTimeout(poll, 2500);
    };

    paint();
    setInterval(paint, 250);
    setInterval(cycle, 1400);
    setTimeout(poll, 1500);
}

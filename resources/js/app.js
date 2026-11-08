import Alpine from '@alpinejs/csp';
import { initMotion } from './motion';

/*
 * Header: hides on scroll down, returns on scroll up (300ms, see app.css).
 * Also owns the full-screen mobile menu.
 */
Alpine.data('siteHeader', () => ({
    open: false,
    hidden: false,
    lastY: 0,

    init() {
        this.lastY = window.scrollY;
    },

    onScroll() {
        const y = window.scrollY;

        if (this.open || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.hidden = y > this.lastY && y > 96;
        this.lastY = y;
    },

    reveal() {
        this.hidden = false;
    },

    toggle() {
        this.open = !this.open;
        document.documentElement.style.overflow = this.open ? 'hidden' : '';

        if (this.open) {
            this.$nextTick(() => this.$refs.firstLink?.focus());
        } else {
            this.$refs.toggle?.focus();
        }
    },

    close() {
        if (this.open) {
            this.toggle();
        }
    },

    isHidden() {
        return this.hidden ? 'true' : 'false';
    },

    expanded() {
        return this.open ? 'true' : 'false';
    },
}));

/*
 * Live local time for Zwolle and Amsterdam (one timezone). The server renders
 * the first value, so it is correct before any JS runs. Updates once a minute.
 */
Alpine.data('clock', (initial) => ({
    time: initial,

    init() {
        const format = new Intl.DateTimeFormat('nl-NL', {
            timeZone: 'Europe/Amsterdam',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        });
        const tick = () => (this.time = format.format(new Date()));

        tick();
        // Align to the start of the next minute, then repeat every 60 seconds.
        setTimeout(() => {
            tick();
            setInterval(tick, 60000);
        }, 60000 - (Date.now() % 60000));
    },
}));

window.Alpine = Alpine;
Alpine.start();
initMotion();

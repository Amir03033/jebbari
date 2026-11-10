import Alpine from '@alpinejs/csp';
import { initMotion } from './motion';

/*
 * Header: clear at the top of the page, frosted once it moves, hidden while
 * reading down and back on the way up. Also owns the full-screen mobile menu.
 */
Alpine.data('siteHeader', () => ({
    open: false,
    hidden: false,
    scrolled: false,
    lastY: 0,

    init() {
        this.lastY = window.scrollY;
        this.scrolled = this.lastY > 8;
    },

    onScroll() {
        const y = window.scrollY;

        this.scrolled = y > 8;

        if (this.open || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.hidden = y > this.lastY && y > 320;
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

    isScrolled() {
        return this.scrolled ? 'true' : 'false';
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

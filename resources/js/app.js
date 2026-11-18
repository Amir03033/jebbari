import Alpine from '@alpinejs/csp';
import { initMotion } from './motion';
import { initScanStatus } from './scan';

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

/*
 * Price calculator: the same arithmetic as App\Leads\PriceCalculator, live.
 * Prices come from data-calc (rendered by the server); the server recomputes
 * whatever is saved, so these numbers are for display only.
 */
Alpine.data('priceCalculator', () => ({
    items: {},
    names: {},
    project: '',
    size: '',
    deadline: '',
    extras: [],
    format: null,

    init() {
        let data = { items: [], names: {} };

        try {
            data = JSON.parse(this.$el.dataset.calc || '{}');
        } catch {
            // Leave the server-rendered result in place.
        }

        this.items = Object.fromEntries((data.items || []).map((item) => [item.key, item]));
        this.names = data.names || {};
        this.format = new Intl.NumberFormat('nl-NL', { maximumFractionDigits: 0 });

        const form = new FormData(this.$el);
        this.project = form.get('project') || '';
        this.size = form.get('size') || '';
        this.deadline = form.get('deadline') || '';
        this.extras = form.getAll('extras[]');
        this.$el.classList.add('is-live');
    },

    money(value) {
        return '€ ' + this.format.format(value);
    },

    span(min, max) {
        return min === max ? this.money(min) : this.money(min) + ' – ' + this.money(max);
    },

    picked() {
        const pick = (group, key) => (this.items[key] && this.items[key].group === group ? this.items[key] : null);

        if (!pick('project', this.project)) {
            return [];
        }

        return [
            pick('project', this.project),
            pick('size', this.size),
            ...this.extras.map((key) => pick('extra', key)),
            pick('deadline', this.deadline),
        ].filter(Boolean);
    },

    get lines() {
        return this.picked().map((item) => ({
            key: item.key,
            name: this.names[item.key] || item.key,
            price: this.span(item.min, item.max),
        }));
    },

    get range() {
        const items = this.picked();

        if (items.length === 0) {
            return '–';
        }

        return this.span(
            items.reduce((sum, item) => sum + item.min, 0),
            items.reduce((sum, item) => sum + item.max, 0),
        );
    },

    get empty() {
        return this.picked().length === 0;
    },

    get filled() {
        return this.picked().length > 0;
    },
}));

window.Alpine = Alpine;
Alpine.start();
initMotion();
initScanStatus();

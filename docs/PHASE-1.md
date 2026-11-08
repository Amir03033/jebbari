# Phase 1: foundations (complete)

## What exists
- Laravel 13.40 (PHP 8.3+), Blade, Tailwind CSS 4, Alpine via `@alpinejs/csp`, Vite 8.
- Tokens in `resources/css/app.css` (colour, type scale, radius, hairlines, motion). `--ink-2` is `#686763`.
- Fonts: Geist Mono self-hosted. **Satoshi is the intended font but is not installed**: Fontshare returned 403 from the build environment. Steps in `resources/fonts/README.md`.
- Components: `x-wordmark`, `x-status`, `x-section-label`, `x-link`, `x-button`, `x-field`, `x-spec-row`, `x-lines`, `x-plate`.
- Layout: skip link, fixed header (hide on scroll, full-screen mobile menu, status line, language switch), dark footer with a live Zwolle · Amsterdam clock and `[[ OWNER: ... ]]` placeholders.
- Routing: `/` redirects by Accept-Language (default NL); `{locale}.{page}` routes with translated slugs; `hreflang` and canonical tags; helpers in `App\Support\Locale`.
- `/_style` test page (hidden in production). Sections: colour with measured contrast, type, buttons and links, form fields, hairlines, grid and shape, motion, on-ink.
- Tests: locale routing and motion foundation (`php artisan test`).

## Run it
```
composer install && npm install
cp .env.example .env && php artisan key:generate
npm run build        # or: npm run dev
php artisan serve    # then open /_style, /nl, /en
```

## Quality notes
- Strict-CSP verified (no `unsafe-eval`); zero third-party requests; no console errors.
- Contrast: every text pairing passes AA. `--signal` on paper is 3.02:1, so it is used only for non-text indicators and the logo dot.

## Not done / deliberately deferred
Real pages (Phase 2+), Privacy and Terms pages, security headers middleware, SEO images and sitemap (Phase 6), Satoshi (needs the files), CMS (see `docs/CMS-ARCHITECTURE.md`).

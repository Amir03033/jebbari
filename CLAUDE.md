# jebbari.nl

Read `docs/BRIEF.md` in full before writing any code. It is the design and build brief and it is binding.

## Status

- Phase 1 (foundations): done. Phase 1.5 (motion demonstration on `/_style`): done, awaiting review.
- CMS architecture is proposed in `docs/CMS-ARCHITECTURE.md`; do not implement Filament until the owner approves it.
- Do not start Phase 2 (Home) until the motion demo and CMS architecture are approved.
- Do not deploy or open a PR without the owner's approval.

## Stack

Laravel 13 (PHP 8.3+), Blade, Tailwind CSS 4 (tokens in `resources/css/app.css`), Alpine.js (`@alpinejs/csp`), Vite. Filament admin is planned (see docs). No other dependencies without asking.

## Conventions

- Copy lives in `lang/{nl,en}`, never hard-coded in Blade.
- Owner-supplied facts live in `config/site.php`; when `null` they render as `[[ OWNER: ... ]]`.
- Routes are `{locale}.{page}`; helpers in `App\Support\Locale`.
- Fonts: see `resources/fonts/README.md` (Satoshi is not yet installed).
- Run `vendor/bin/pint` and `php artisan test` before committing.
- Open items for the owner are tracked in `docs/OPEN-ITEMS.md` (brief section 12).

- Motion: follow `docs/MOTION.md`. Only transform, opacity and clip-path animate; no parallax; no GSAP.
- Alpine expressions must stay CSP-safe: property and method references only, no inline logic.
- Website copy must come from the CMS or lang files, never hard-coded in Blade (the `/_style` test page is the one exception).

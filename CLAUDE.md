# jebbari.nl

Read `docs/BRIEF.md` in full before writing any code. It is the design and build brief and it is binding.

## Status

- Phase 1 (foundations): done. Phase 1.5 (motion demonstration on `/_style`): done, awaiting review.
- CMS foundation (Filament admin at /admin) is built and tested: see `docs/CMS-IMPLEMENTATION.md`. Awaiting owner review.
- Do not start the homepage until the owner approves the CMS foundation. No PR and no deployment without explicit approval.
- Do not deploy or open a PR without the owner's approval.

## Stack

Laravel 13 (PHP 8.3+), Blade, Tailwind CSS 4 (tokens in `resources/css/app.css`), Alpine.js (`@alpinejs/csp`), Vite. Filament 5 admin, Spatie Media Library and Activitylog (PHP 8.4+). No other dependencies without asking.

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

- Content editing goes through `saveFromForm()` only (draft rule): never write to live columns of a published record from the admin.
- Run `php artisan filament:assets` if the admin is unstyled; `composer install` does it automatically.

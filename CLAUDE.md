# jebbari.nl

Read `docs/BRIEF.md` in full before writing any code. It is the design and build brief and it is binding.

## Status

- Phase 1 (foundations): done. Phase 1.5 (motion demonstration on `/_style`): done, awaiting review.
- CMS foundation (Filament admin at /admin): approved. See `docs/CMS-IMPLEMENTATION.md`.
- Phase 3 (public website, all pages, NL/EN, CMS-driven): built and tested, awaiting owner review. See `docs/PHASE-3.md`. Do not start further phases, open a PR or deploy without explicit approval.
- Phase 3.5 (visual redesign): built and tested, awaiting owner review. See `docs/VISUAL-REDESIGN.md` and `docs/VISUAL-COMPARISON.md`. Do not start further phases.
- AI visibility (About page as /nl/over-mij, JSON-LD graph, robots.txt, /llms.txt): phases 1 to 3 built, see `docs/AI-VISIBILITY.md`. Never mention education anywhere; never mention Iran Digital Lifeline.
- Do not deploy or open a PR without the owner's approval.

## Stack

Laravel 13 (PHP 8.3+), Blade, Tailwind CSS 4 (tokens in `resources/css/app.css`), Alpine.js (`@alpinejs/csp`), Vite. Filament 5 admin, Spatie Media Library and Activitylog (PHP 8.4+). No other dependencies without asking.

## Conventions

- Copy lives in `lang/{nl,en}`, never hard-coded in Blade.
- Owner-supplied facts live in `config/site.php`; when `null` they render as `[[ OWNER: ... ]]`.
- Routes are `{locale}.{page}`; helpers in `App\Support\Locale`.
- Fonts: Instrument Sans is self-hosted; Satoshi takes over when added (`resources/fonts/README.md`).
- Run `vendor/bin/pint` and `php artisan test` before committing.
- Open items for the owner are tracked in `docs/OPEN-ITEMS.md` (brief section 12).

- Motion: follow `docs/MOTION.md`. Only transform, opacity and clip-path animate; no parallax; no GSAP.
- Alpine expressions must stay CSP-safe: property and method references only, no inline logic.
- Website copy must come from the CMS or lang files, never hard-coded in Blade (the `/_style` test page is the one exception).

- Content editing goes through `saveFromForm()` only (draft rule): never write to live columns of a published record from the admin.
- Run `php artisan filament:assets` if the admin is unstyled; `composer install` does it automatically.

- Public pages read content only through `App\Content\Site` (published, live values, complete in the visitor's language) and render through `App\Content\Renderer`; never query models directly in views. Missing content is omitted, never replaced by placeholder text.
- Public HTML must stay CSP-clean: no inline `style`, event handlers or scripts (JSON-LD excepted). `SeoTest` enforces it.

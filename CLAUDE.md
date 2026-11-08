# jebbari.nl

Read `docs/BRIEF.md` in full before writing any code. It is the design and build brief and it is binding.

## Status

- Phase 1 (foundations, `/_style`, header/footer, locale routing): done, awaiting owner review.
- Do not start Phase 2 (Home) until the owner approves the design.
- Do not deploy or open a PR without the owner's approval.

## Stack

Laravel 13 (PHP 8.3+), Blade, Tailwind CSS 4 (tokens in `resources/css/app.css`), Alpine.js, Vite. No other dependencies without asking.

## Conventions

- Copy lives in `lang/{nl,en}`, never hard-coded in Blade.
- Owner-supplied facts live in `config/site.php`; when `null` they render as `[[ OWNER: ... ]]`.
- Routes are `{locale}.{page}`; helpers in `App\Support\Locale`.
- Fonts: see `resources/fonts/README.md` (Satoshi is not yet installed).
- Run `vendor/bin/pint` and `php artisan test` before committing.
- Open items for the owner are tracked in `docs/OPEN-ITEMS.md` (brief section 12).

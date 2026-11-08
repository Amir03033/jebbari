# CMS foundation: what was built, how to run it, what production needs

Status: implemented and tested locally. **Not deployed. No production configuration has been made or assumed.** The design is in `CMS-ARCHITECTURE.md`; this file records what exists and where it differs.

## 1. Packages added and why

| Package | Purpose | Notes |
|---|---|---|
| `filament/filament` 5.11.2 | Admin panel at `/admin` (resources, forms, tables, MFA, auth) | Pulls about 35 packages, admin only (Livewire 4.4.8, Heroicons, Tiptap PHP rich editor, google2fa). Needs `ext-intl` |
| `filament/spatie-laravel-media-library-plugin` 5.11.2 | Upload field and thumbnail column in the media screen | Same release as Filament |
| `spatie/laravel-medialibrary` 11.23.12 | Stores uploads and creates responsive WebP/AVIF conversions | **Requires PHP 8.4**, `ext-exif`, `ext-fileinfo` |
| `spatie/laravel-activitylog` 5.1.4 | Audit log of who changed what | **Requires PHP 8.4** |
| `@alpinejs/csp` (npm, approved earlier) | Alpine for the public site without `unsafe-eval` | Frontend only |

Compatibility was checked before installing with `composer require ... --dry-run` against Laravel 13.40 and PHP 8.4.27: all four resolve together. No other packages were added. In this sandbox only, `php8.4-gd` was installed with apt so image conversions could be tested; it is not part of the project.

## 2. Migrations (run in this order)

| Migration | What it does |
|---|---|
| `0001_01_01_000000_create_users_table` (Laravel) | users, password resets, sessions |
| `0001_01_01_000001_create_cache_table`, `..._000002_create_jobs_table` (Laravel) | cache and queue tables |
| `2026_11_08_231023_create_activity_log_table` (Spatie) | `activity_log` (audit log) |
| `2026_11_08_231023_create_media_table` (Spatie) | `media` (files and conversion state) |
| `2026_11_09_000001_add_cms_columns_to_users_table` | `role`, `is_active`, MFA secret and recovery codes (encrypted), `last_login_at` |
| `2026_11_09_000002_create_media_assets_table` | `media_assets`: one row per image, with per-language alt text |
| `2026_11_09_000003_create_content_tables` | `pages`, `services`, `projects`, `testimonials`, `post_categories`, `posts` |
| `2026_11_09_000004_create_inbox_and_site_tables` | `inquiries`, `inquiry_notes`, `settings`, `navigation_items` |

Conventions: Dutch and English text lives in one JSON column as `{"nl": ..., "en": ...}`; slugs are plain `slug_nl` / `slug_en` columns with unique indexes. Publishable tables share `status`, `published_at`, `draft_data` (unpublished changes) and `seo`. Packages and project galleries are JSON columns on their parent so they are drafted together with it. All migrations were run from scratch on SQLite; they use no database-specific features. `down()` is implemented for the new migrations.

## 3. How it works (summary)

- **Draft rule:** a published record is never changed by editing. Form saves write only the changed fields into `draft_data`; **Publish** applies them; **Discard** drops them. This applies to pages (including homepage sections), services, projects, testimonials and blog posts. Test: `PublishingTest`.
- **Blocked publishing:** both Dutch and English must be complete for every required field (and in every visible block), images used must have alt text in both languages (or be marked decorative), and a testimonial needs confirmed permission. The reasons are listed in the notification.
- **Preview:** signed link that expires after 30 minutes, requires a signed-in admin who may view the record, shows the draft overlay, is `noindex` and not cached. It currently renders a structured view of the content; the real page layouts will render through the same link in the page phases.
- **Audit:** every create, update, delete, draft save, publish, unpublish, discard, sign-in and failed sign-in is logged with the user and old/new values. User secrets and password hashes are never logged. The log is read-only and owner-only.
- **Media:** originals have camera metadata (EXIF, including GPS) removed at upload; WebP is generated at 480, 768, 1200, 1600 and 2400 px; AVIF is generated only if the server can really encode it (it is detected by encoding a test image); SVG and PDF are refused; an image that is used anywhere (including in a draft) cannot be deleted.
- **Security:** authenticator-app MFA is required for every account (with recovery codes and replay protection); no registration route exists; the owner is created from the command line; roles `owner`, `editor`, `support` use policies; the admin has its own CSP, public pages a strict CSP without inline script or eval; admin and previews are `noindex`, `no-store`.

## 4. Running it locally

```
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed            # starting content from the brief, all as DRAFT
php artisan storage:link
php artisan cms:create-owner you@example.com --name="Jouw naam"   # asks for a password (12+ chars)
php artisan serve                      # open /admin; set up the authenticator app on first login
php artisan test
```

`composer install` runs `php artisan filament:upgrade` (publishes Filament's CSS and JS into `public/`, which is git-ignored). If the admin ever looks unstyled, run `php artisan filament:assets`.

## 5. Production requirements (not yet confirmed or configured)

The hosting for jebbari.nl has **not** been confirmed and is not assumed to match any other project. Everything below must be checked against the real environment before deployment.

**PHP**
- **PHP 8.4 or newer** (hard requirement: Spatie Media Library and Activitylog).
- Extensions: `intl`, `mbstring`, `ctype`, `curl`, `dom`, `exif`, `fileinfo`, `filter`, `hash`, `json`, `openssl`, `pcre`, `pdo` plus the database driver (`pdo_mysql` or `pdo_pgsql`), `session`, `tokenizer`, `xml`, `zip`.
- Image processing: **GD with WebP support** (required). **AVIF is optional**: if GD or Imagick can encode AVIF the site uses it, otherwise it serves WebP with the original as fallback. Note that some PHP builds report AVIF as enabled but cannot encode it, which is why the app tests it. Imagick is an alternative to GD.
- `memory_limit` 512M recommended (large photos are decoded in memory), `upload_max_filesize` at least 12M, `post_max_size` at least 16M, `max_execution_time` at least 60s. OPcache on.

**Database**
- MySQL 8.0 or MariaDB 10.6 or newer (utf8mb4), or PostgreSQL. The app stores JSON columns and searches them with `LIKE` in the admin; it uses no JSON path queries. SQLite is for local development only. Confirm the real engine and version.

**Queue**
- Image conversions are queued. Set `QUEUE_CONNECTION=database` and run a worker. If the host cannot run a long-lived process, a one-minute cron works: `php artisan queue:work --stop-when-empty --max-time=55`. With `sync` uploads work but are slow and can hit time limits. Confirm what the host supports.
- No scheduler is required for scheduled publishing (it is evaluated when pages are queried). Optional: monthly `php artisan activitylog:clean` to apply the 24-month audit retention.

**SMTP**
- Needed for password reset and the new-inquiry notification. Set `MAIL_MAILER=smtp` with `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Publish SPF, DKIM and DMARC for jebbari.nl and send a test. Mail currently defaults to the `log` driver. Provider and credentials are not yet chosen.

**Web server and storage**
- HTTPS only; document root is `public/`. The app sets HSTS when the request is secure.
- `php artisan storage:link`; `storage/` and `bootstrap/cache/` writable; uploaded images are served from `public/storage`.
- Environment: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://jebbari.nl`, `APP_LOCALE=nl`, `SESSION_SECURE_COOKIE=true`.

**Deployment sequence (to be confirmed with you first; not run)**
`composer install --no-dev --optimize-autoloader` (runs `filament:upgrade`), `npm ci && npm run build`, `php artisan migrate --force`, `php artisan db:seed --class=CmsSeeder` (once), `php artisan cms:create-owner`, `php artisan storage:link`, then `php artisan config:cache route:cache view:cache`, and start the queue worker or cron.

**Backups**
- Database: at least daily, kept off the server, with a restore test.
- Files: `storage/app/public` (uploaded originals; conversions can be regenerated with `php artisan media-library:regenerate`).
- **`.env`, especially `APP_KEY`:** MFA secrets and recovery codes are encrypted with it. Without the key nobody can pass MFA and the admin is locked. Store it in a separate secure place and include it in the backup plan.
- Retention and who restores are decisions for you.

## 6. Deviations from the architecture document

- **Dutch and English fields sit side by side** (stacked on phones), each labelled NL or EN, instead of a language tab bar. Tabs cannot wrap the page builder, and side by side lets you see both at once.
- **Settings, navigation items and blog categories apply immediately** (no draft step). Every change is audit-logged. Pages, services, projects, testimonials and posts all use the draft rule.
- **Preview** is a structured content view for now (see section 3).
- **AVIF** is conditional on real server support (section 3).
- **Livewire `csp_safe` mode was not attempted.** The admin keeps a looser CSP (`unsafe-inline`, `unsafe-eval`) while public pages are strict. Verified: no CSP violations in the admin at phone, tablet and desktop widths, and public pages contain no Livewire or Filament assets.
- `config/site.php` is not yet replaced by the settings in the frontend; that happens in the page phases.

## 7. Verification done

- 88 automated tests pass (`php artisan test`): permissions per role, publishing and the draft rule for every content type, translations and blocks, media (real conversions, metadata removal, type and size limits, alt text, deletion safety), audit log, inbox, settings, blog, previews, security headers.
- Real browser pass (Chromium) through the actual login and MFA flow at 390, 820 and 1280 px: no horizontal overflow, no console errors, no CSP violations, no external requests. Screenshots are in `docs/screenshots/admin/`.
- Found and fixed during this pass: unstyled admin (assets not published; now part of `composer install`), avatar images fetched from a third party (replaced by a local initials avatar), AVIF reported but not working (now detected), inquiry message layout.
- **Not verified:** Safari/WebKit and a real iPhone or iPad (only Chromium is available here), the rich text editor with iOS touch input, email delivery, and any production environment.

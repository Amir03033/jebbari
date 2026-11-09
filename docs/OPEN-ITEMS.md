# Open items for the owner (brief section 12)

- [ ] Company legal name, KvK number, BTW number (`config/site.php`, footer shows placeholders)
- [ ] Zwolle and Amsterdam: real address / workspace, or based in one and active in both? (footer, JSON-LD)
- [ ] Preferred meeting setup for the Contact page line
- [ ] Contact email address, booking link, social links (`config/site.php`)
- [ ] Real screenshots and one honest outcome line for each case study
- [ ] Package names, scope and "from €" prices
- [ ] About text and photo
- [ ] Decision: first-person ("I") vs. studio ("we") voice
- [ ] Analytics: none vs. cookieless self-hosted
- [x] Satoshi: **not self-hosted.** Fontshare returned 403 from the build environment, so the system fallback stack is in use. Steps to add it are in `resources/fonts/README.md`.
- [ ] Hosting target and deployment approach

## Found during Phase 1

- ~~`--ink-2` contrast~~ Resolved: now `#686763` (5.11:1 on paper, 4.67:1 on paper-2).
- `--signal` on `--paper` measures 3.02:1: enough for focus rings (non-text, 3:1) but not for text. The wordmark dot is exempt as a logo.
- ~~Strict CSP vs Alpine~~ Resolved: `@alpinejs/csp` approved and in use.
- Privacy and Terms in the footer are plain text until those pages exist (Phase 6).
- `x-plate` is not built yet; it belongs to Phase 3 with real screenshots.

## Added after Phase 1.5 review

- [x] Motion decisions recorded (docs/MOTION.md section 8): full stop lands last, no parallax, cross-document view transitions on.
- [ ] CMS architecture decisions (docs/CMS-ARCHITECTURE.md section 16): draft overlay, own traits vs Spatie, hosting facts (PHP, DB, GD/AVIF, queue, mail, backups), roles and MFA, missing-translation policy, interface microcopy, blog, admin path.
- [ ] Real-device check of motion and, later, the admin on iPhone Safari and iPad (only headless Chromium available here).
- [ ] Safari support for cross-document view transitions and scroll-driven animations should be confirmed on a device (both degrade to normal navigation and a JS-driven fill).

## Added after the CMS foundation

- [ ] Production hosting facts (docs/CMS-IMPLEMENTATION.md section 5): PHP 8.4+, GD with WebP (AVIF optional) or Imagick, database engine and version, queue worker or cron, SMTP provider and credentials (SPF/DKIM/DMARC), backups including `.env` and `APP_KEY`.
- [ ] Real-device check of the admin on iPhone and iPad (rich text editor, photo upload from the camera roll, page builder, MFA setup).
- [ ] Review the Dutch draft translations seeded from the brief (all content is seeded as DRAFT; nothing is live).
- [ ] Decide whether settings, navigation and blog categories should also use the draft step (currently immediate, audit-logged).
- [ ] Admin CSP: Filament and Livewire need `unsafe-inline` and `unsafe-eval`; try Livewire's `csp_safe` mode later if a stricter admin CSP is wanted.
- [ ] Preview shows a structured content view until the real page layouts exist (arrives with each page phase).
- [ ] Create the real owner account with `php artisan cms:create-owner` and keep the authenticator recovery codes safe.

## Added in Phase 3 (public site)

- [ ] **Decision: accent colour.** The request mentions "subtle electric blue"; the approved system uses the orange signal. Keep orange, or switch the accent to blue (one token, `--signal`)? Dark hero and closing section are in place either way.
- [ ] Publish the pages (Home, Work, Services, About, Blog, Contact) in the admin once you have reviewed the Dutch draft translations; nothing is live until you do.
- [ ] Write the About teaser and About page (personal introduction, background, skills, approach). Only true facts; the site shows nothing until you do.
- [ ] ServerScope: add year, description, images, link and an honest outcome line (or leave it as name and category only), then publish. Add JF Cuts or other projects the same way.
- [ ] Review the E-commerce service text (a neutral draft; the brief had none) and add benefits, deliverables, timelines and optional package prices for each service.
- [ ] Complete and publish the privacy statement and terms (placeholders are blocked from publishing).
- [ ] Contact email, booking link, company name, KvK, BTW and social links (Site settings); they appear on the site only when filled in.
- [ ] Test the public site and admin on iPhone, iPad, Safari and Firefox; run Lighthouse on a production build and, if the numbers are good, fill `config/specs.php` to show the "Built to a standard" section.
- [ ] Add the Satoshi font files.

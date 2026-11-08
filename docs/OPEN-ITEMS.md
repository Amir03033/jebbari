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

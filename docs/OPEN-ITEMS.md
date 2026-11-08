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

- `--ink-2` on `--paper-2` measures 4.47:1, just under AA for text (4.5:1). Avoid secondary text on `--paper-2`, or darken `--ink-2` slightly. Decision needed.
- `--signal` on `--paper` measures 3.02:1: enough for focus rings (non-text, 3:1) but not for text. The wordmark dot is exempt as a logo.
- The standard Alpine build needs `unsafe-eval` under a strict CSP. For the section 9 CSP, either switch to `@alpinejs/csp` (same project, a separate package, not in section 8) or allow `unsafe-eval`. Decision needed in Phase 6.
- Privacy and Terms in the footer are plain text until those pages exist (Phase 6).
- `x-plate` is not built yet; it belongs to Phase 3 with real screenshots.

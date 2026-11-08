# jebbari.nl: Design & Build Brief

> For Claude Code. Read this whole document before writing any code. Save it as `CLAUDE.md` in the project root (or keep as `docs/BRIEF.md` and reference it from `CLAUDE.md`).

---

## 0. How to work

1. **Tokens first.** Build the design tokens (section 3) and a `/_style` test page showing type scale, colors, buttons, form fields and hairlines. Nothing else until that page looks right.
2. **Homepage second.** Then Work, Services, About, Contact.
3. **Look at your own output.** After each page, run the dev server, take screenshots at 390px, 768px, 1280px and 1920px, and compare against section 2 and section 11. Fix before moving on.
4. **Never invent facts.** No fake clients, testimonials, metrics, prices or logos. Where real content is missing, use clearly marked placeholders in the form `[[ OWNER: ... ]]` and list them in section 12.
5. **Ask before** adding a dependency that is not in section 8, or deploying anything.

---

## 1. Brand

**Jebbari** is a small software studio working from **Zwolle and Amsterdam** that builds web applications and websites for Dutch businesses. Two cities, one standard: Zwolle's calm and craft, Amsterdam's reach and pace. Use this lightly. It is a fact in the footer, hero label and About, never a slogan. The site is the first product: it has to prove the craft by being the craft.

**Idea: "Finished."** The studio's promise is software that is done properly: fast, accessible, no loose ends. The brand mark is **the full stop**: a single accent dot, the period at the end of the sentence.

- **Wordmark:** `jebbari` in lowercase, followed by the accent dot: `jebbari.`
- **Favicon / app icon:** the accent dot, centered on paper background. Nothing else.
- **Voice:** calm, precise, confident, short sentences. It states, it does not sell. No exclamation marks, no buzzwords.

**Positioning line (recommended):**
- EN: **Software, done properly.**
- NL: **Software die klopt.**

Alternatives to test: EN "Built carefully. Shipped finished." / NL "Zorgvuldig gebouwd. Af opgeleverd."

---

## 2. Anti-slop rules (hard constraints)

The goal is *Apple-like restraint with an editorial, almost print-like character*. It must not look like an AI-generated agency template. These are banned unless this document explicitly says otherwise:

- ❌ Gradients of any kind (backgrounds, text, buttons, borders)
- ❌ Glassmorphism, blur panels, glow, neon, aurora blobs, mesh backgrounds
- ❌ Purple/indigo-to-blue "tech" palettes
- ❌ Emoji or icon-in-a-circle feature cards; three-column icon grids
- ❌ Bento grids
- ❌ Stock photography, 3D abstract shapes, generic laptop-on-desk mockups
- ❌ Fake macOS window chrome (red/yellow/green dots) around screenshots
- ❌ Heavy drop shadows; rounded-2xl on everything; pill buttons everywhere
- ❌ Centered-everything layouts; centered paragraph text
- ❌ Bouncy, springy, or looping decorative animation; parallax on text
- ❌ Copy like "elevate", "unlock", "revolutionize", "cutting-edge", "seamless", "solutions", "empower", "in today's digital landscape"
- ❌ Testimonial carousels, logo walls of clients that do not exist, stat counters that count up
- ❌ Cookie banners (achieved by using no tracking cookies at all, see section 9)

**What replaces them:** hairline rules, a strict grid, big confident typography, whitespace, real screenshots, monospaced captions, one accent used with extreme discipline.

---

## 3. Design tokens

### Color

| Token | Value | Use |
|---|---|---|
| `--paper` | `#F5F3EE` | Default background (warm off-white, not Apple's cool gray) |
| `--paper-2` | `#ECE9E2` | Subtle surfaces, image placeholders |
| `--ink` | `#0E0E0F` | Primary text, dark sections |
| `--ink-2` | `#6B6A66` | Secondary text (verify contrast ≥ 4.5:1 on paper) |
| `--line` | `rgba(14,14,15,.14)` | 1px hairlines on paper |
| `--line-dark` | `rgba(245,243,238,.16)` | Hairlines on ink |
| `--signal` | `#FF4B1F` | **The one accent** |

**Signal is used only for:** the wordmark dot, the status dot, text selection background, focus rings, the active nav marker, and the underline on link hover. Never as a button fill, never as a section background, never in large areas. If more than ~0.5% of any screen is orange, it is wrong.

Dark sections (`--ink` background, `--paper` text) are used at most twice per page (e.g., the spec sheet and the footer). Both light and dark are curated; no automatic dark mode in v1.

### Typography

- **Display + body:** **Satoshi** (Fontshare, free for commercial use). Self-host as `woff2`, `font-display: swap`, preload the two weights used. Fallback stack: `ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif`. If Satoshi cannot be downloaded in this environment, use the fallback stack and flag it in section 12. Do **not** substitute Inter.
- **Mono (labels, captions, specs):** **Geist Mono** (OFL), self-hosted.
- Weights: 400 (body), 500 (display and headings). No 700+. Lighter feels more expensive.

| Role | Size | Line height | Tracking | Notes |
|---|---|---|---|---|
| Display | `clamp(3.5rem, 9vw, 9rem)` | 0.95 | -0.04em | weight 500, left-aligned |
| H2 | `clamp(2rem, 4.5vw, 4rem)` | 1.02 | -0.03em | weight 500 |
| H3 | 1.5rem | 1.2 | -0.015em | weight 500 |
| Body | 1.125rem (18px) | 1.6 | 0 | max 62ch, `--ink-2` for supporting text |
| Mono label | 0.75rem | 1.4 | +0.06em | uppercase, Geist Mono |

Use `text-wrap: balance` on headings and `text-wrap: pretty` on paragraphs. Enable `font-feature-settings: "ss01", "tnum"` where the font supports it (tabular numbers in all spec/price tables).

### Space & shape

- Base unit 8px. Section vertical padding: `clamp(96px, 16vw, 200px)`.
- Max content width 1360px; text column max 62ch.
- Radius: **2px** for UI (inputs, buttons), **0** for images and plates. No large radii on layout elements. The restraint is the point.
- Borders: 1px hairlines only. No box-shadows except one extremely soft one on screenshot plates (`0 30px 80px -40px rgba(14,14,15,.25)`).

---

## 4. Layout system

- **12-column grid**, 24px gutters (16px mobile), 24px/48px page margins.
- **Asymmetric and left-aligned.** Headlines start on column 1; supporting text sits on columns 7–11 or 8–12. Whitespace is a feature.
- **Structure through hairlines.** Every section begins with a 1px full-width rule and a mono label row, like a page in a printed spec book:

```
─────────────────────────────────────────────
01 / WORK                          (4 projects)
```

- Section numbering (`01`, `02`, ...) in mono is the recurring editorial device. Use it consistently on every page.
- One idea per screen height on desktop. No more than one primary message per section.

---

## 5. Signature elements (what makes it not-generic)

1. **The full stop.** The accent dot is the brand mark. It appears in the wordmark, favicon, status line and the end of the hero headline.
2. **Hairline-and-index editorial structure** (section 4).
3. **Plates, not cards.** Case studies are presented as large "plates": full-width screenshot, 1px frame, no fake browser chrome, with a mono caption row beneath: `01  JF Cuts  Booking web app  2026  Laravel · Alpine`. Hover: the image scales to 1.01 over 600ms, and a small label ("View case") follows the cursor on pointer devices only.
4. **The spec sheet.** A dark section titled "Built to a standard" showing a table of **real, measured** values for this very site: Lighthouse scores, LCP, JS shipped (KB), total page weight, third-party requests (0), hosting region. Values are written into a config file after measuring (`config/specs.php`), never hard-coded to look good. If a number is not achieved, show the true number. Honesty is the flex.
5. **Live local time.** Footer shows `Zwolle · Amsterdam, NL: 23:22` updating each minute (Europe/Amsterdam), in mono. Both cities share one timezone, so one clock is enough. Optional refinement: the two city names are separate spans, and the one nearer the visitor's browser timezone is not highlighted. No geolocation, ever.
6. **A status line** in the header area: `● Taking on projects` (accent dot + mono text), driven by `config/site.php`. The dot is static, with no pulsing.
7. **The big email.** On the Contact page the primary CTA is the email address set at display size as a link, with the form secondary.

Do not add further decorative devices. If something is not on this list or in section 7, ask.

---

## 6. Motion

Purposeful, slow, quiet. Respect `prefers-reduced-motion: reduce` by disabling everything below except opacity fades.

- **Easing:** `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out-expo-ish). No springs or overshoot.
- **Page load:** hero headline reveals line by line using masked translate-Y (60ms stagger, 900ms duration). Nothing else animates on load.
- **Scroll reveals:** elements fade and translate up 16px over 700ms when 15% visible, once only (IntersectionObserver; no scroll-jacking, no smooth-scroll library).
- **Plates:** slow image scale on hover (above). Optional very subtle vertical parallax (max 24px) on plate images only.
- **Links/buttons:** 200ms color/underline transitions. Link underline draws left-to-right in `--signal`.
- **Nav:** header hides on scroll down and shows on scroll up, 300ms.
- **Process section:** a vertical hairline fills (scaleY) as the section scrolls, with each of the four steps lighting from `--ink-2` to `--ink` as it passes.
- No JS animation library is required. Prefer CSS + IntersectionObserver. GSAP only if the process-line scrub cannot be done cleanly otherwise (flag it first).

---

## 7. Pages & content

Routes exist in both languages: `/nl/...` and `/en/...`. Root `/` redirects based on `Accept-Language`, defaulting to NL. A discreet `NL / EN` switch sits top-right in mono.

### Header (all pages)
Left: wordmark. Center-left: status line (hidden on mobile). Right: `Work`, `Services`, `About`, `Contact`, language switch. Mobile: a simple full-screen menu with the same links at display size, no hamburger animation flourish.

### Home

**01 / Hero**
- Mono label: `Software studio, Zwolle & Amsterdam` (NL: `Softwarestudio, Zwolle & Amsterdam`)
- Display headline (left, ~9 columns): **Software, done properly.** (period in `--signal`)
- Supporting sentence (cols 8–12, `--ink-2`): *We design and build web applications and websites for businesses that care how their software feels, performs and lasts.*
- Single text-link CTA: `Start a project →` (no filled button here).
- NL: **Software die klopt.** / *Wij ontwerpen en bouwen webapplicaties en websites voor bedrijven die er om geven hoe hun software voelt, presteert en standhoudt.* / `Start een project →`

**02 / Work** (3–4 plates, see section 5.3)
Candidates: JF Cuts (barber booking web app), ServerScope (server monitoring dashboard), plus the studio's own site. Each plate links to a detail page with: one-paragraph brief, what was built, tech, 3–5 large screenshots, and one honest outcome line. `[[ OWNER: real screenshots and one outcome line per project ]]`. Use only what is true.

**03 / Services** (typographic list, no icons)
Three rows separated by hairlines, each: number, name at H2 size, one-sentence description at right, small mono tag list.
- **Web applications:** Custom, secure, fast. Booking systems, dashboards, internal tools, portals.
- **Websites:** Fast, accessible marketing sites you can edit yourself.
- **Care & hosting:** Updates, monitoring, backups, support. Software that stays finished.
- NL: Webapplicaties / Websites / Onderhoud & hosting.

**04 / Process** (sticky left title, four steps right)
1. **Talk:** We listen, ask the awkward questions, and agree scope and price in writing.
2. **Design:** Wireframes and a clickable design you approve before any code.
3. **Build:** Weekly working builds you can open and use. No surprises.
4. **Launch:** We ship, monitor, and stay on call.
NL: Praten / Ontwerpen / Bouwen / Lanceren.

**05 / Built to a standard** (dark section, spec sheet from section 5.4)

**06 / Closing CTA**
Display-size line: **Have something to build?** / NL **Iets te bouwen?** followed by the big email link and `Book a call` link `[[ OWNER: booking URL ]]`.

### Work (index)
Full list of plates, same component, larger.

### Services
Expand the three services: what is included, typical timeline, what we do not do. Include **packages with fixed "from €" prices** in a hairline table (tabular numbers). `[[ OWNER: package names, scope, prices ]]`. Do not invent prices.

### About
Short, human, first person or "we" (owner decides): why the studio exists, what it values (craft, honesty, speed), where it is based (Zwolle and Amsterdam, and that clients are welcome to meet in either city or online). One real photo `[[ OWNER ]]`, black-and-white treatment optional. A small "Principles" list of 3–4 one-liners.

### Contact
- Big email link at display size (copy-to-clipboard on click with a quiet inline confirmation).
- Form: name, email, "What do you want to build?" (textarea), budget range (custom-styled select: `< €2.5k`, `€2.5–7.5k`, `€7.5–15k`, `€15k+`, `Not sure`), optional company. Inline validation, no alerts, success replaces the form with a calm confirmation.
- Spam protection: honeypot + minimum-time check + rate limit. No Google reCAPTCHA.
- Reply promise in mono: `We reply within one working day.`
- Meeting line in mono beneath it: `Meet in Zwolle, Amsterdam or online.` (NL: `Afspreken in Zwolle, Amsterdam of online.`)

### Footer (dark)
Giant wordmark is **not** used. Instead: email, social links (as text), `NL/EN`, live Zwolle · Amsterdam time, legal block (registered address is the KvK address only; do not publish a street address for the other city unless the owner supplies one): company name, **KvK number, BTW number** `[[ OWNER ]]`, links to Privacy and Algemene voorwaarden.

---

## 8. Tech stack

- **Laravel 11, Blade, Tailwind CSS, Alpine.js, Vite.** Matches the owner's existing projects.
- Tailwind configured with the tokens from section 3 (CSS variables mapped in the theme). No UI kit, no component library, no Tailwind plugins beyond typography if needed.
- Localisation: Laravel's built-in localisation, locale-prefixed routes, `hreflang` + canonical tags, translated slugs where sensible. All copy lives in lang files, not hard-coded in Blade.
- Content: project case studies as Blade views fed by a simple PHP/config array (no CMS in v1). Structure so a CMS could be added later.
- Forms: validated server-side (Form Request), sent via Laravel Mail, stored in DB table `inquiries` (so none are lost if mail fails).
- Images: `avif` + `webp` + fallback, explicit width/height, `loading="lazy"` below the fold, responsive `srcset`. Hero has no image (type only), which is a performance advantage.
- Fonts: self-hosted, subset to Latin, preloaded.
- Analytics: **none in v1**, or self-hosted/privacy-friendly (Plausible/Umami) with no cookies. Confirm with owner before adding.
- Deployment: assume the owner's existing VPS/Plesk setup. **Ask before configuring or deploying anything.**

---

## 9. Quality bars (these are acceptance criteria)

**Performance:** Lighthouse mobile ≥ 98 on all four categories on Home. LCP < 1.2s on throttled 4G, CLS ≈ 0, total JS < 50KB gzipped, no render-blocking third-party requests, zero third-party requests overall.

**Accessibility:** WCAG 2.2 AA. Visible focus states (accent ring, 2px offset), full keyboard navigation, skip link, semantic landmarks, proper heading order, `alt` text, form labels and error association, sufficient contrast, reduced-motion respected. Test with keyboard only.

**SEO:** Unique `title` and meta description per page and language, OpenGraph/Twitter images (generated, typographic: ink background, paper headline, accent dot), `sitemap.xml`, `robots.txt`, JSON-LD (`ProfessionalService`/`Organization`) with `areaServed` set to the Netherlands and both Zwolle and Amsterdam listed as `location`/`areaServed` (only with a full address if the owner provides one), clean URLs. Add local-intent landing copy only if it is genuinely useful. No thin doorway pages such as "website laten maken Zwolle" / "website laten maken Amsterdam" duplicated with swapped city names.

**Privacy & legal (NL/EU):** No tracking cookies, so no cookie banner needed. Privacy statement (Dutch + English) covering the contact form. Algemene voorwaarden page. KvK and BTW numbers in the footer. Security headers (CSP, HSTS, X-Content-Type-Options, Referrer-Policy), HTTPS only.

**Code quality:** Small Blade components (`x-plate`, `x-section-label`, `x-spec-row`, `x-link`, `x-field`), no inline styles, no dead CSS, no `!important`, Pint formatted, feature tests for the contact form and locale routing.

---

## 10. Build plan

| Phase | Deliverable | Done when |
|---|---|---|
| 1 | Laravel project, Tailwind tokens, fonts, `/_style` page, header/footer, locale routing | `/_style` matches section 3, NL/EN switch works |
| 2 | Home: hero, services, process, closing CTA | Passes section 11 checklist at 4 breakpoints |
| 3 | Work index + detail template + JF Cuts and ServerScope pages | Plates and detail pages complete with real screenshots |
| 4 | Spec sheet section with measured values | Numbers come from `config/specs.php` and are true |
| 5 | Services (with packages), About, Contact (form, DB, mail) | Form tested, spam protection in place |
| 6 | Motion pass, a11y pass, performance pass, SEO + legal pages | Section 9 bars met |
| 7 | Deploy plan (confirm with owner first) | Staging reviewed |

---

## 11. Self-review checklist (run before calling any page done)

- [ ] Could this page belong to any random AI-generated agency template? If yes, strip and redo.
- [ ] No gradients, glass, blobs, icon circles, emoji, bento, stock imagery.
- [ ] Accent orange appears only in the allowed places and is barely noticeable.
- [ ] Headlines left-aligned, large, tight, weight 500; body text `--ink-2`, max 62ch.
- [ ] Hairlines and mono section labels present; numbering consistent.
- [ ] Every claim and number on the page is true and traceable.
- [ ] Copy has none of the banned words and reads like a person wrote it.
- [ ] Looks deliberate at 390px as well as 1920px (not just a shrunken desktop).
- [ ] Keyboard-only walkthrough works; focus is always visible.
- [ ] Reduced-motion mode works and the page still feels finished.

---

## 12. Open items for the owner

Claude Code: list here everything you could not do without input, and keep this updated.

- [ ] Company legal name, KvK number, BTW number
- [ ] Zwolle and Amsterdam: is each a real address or workspace, or is the studio simply based in one and active in both? Decides what goes in the footer and JSON-LD
- [ ] Preferred meeting setup (own office, client site, coworking, online) for the Contact page line
- [ ] Contact email address, booking link, social links
- [ ] Real screenshots and one honest outcome line for each case study
- [ ] Package names, scope and "from €" prices
- [ ] About text and photo
- [ ] Decision: first-person ("I") vs. studio ("we") voice
- [ ] Analytics: none vs. cookieless self-hosted
- [ ] Whether Satoshi could be self-hosted (fonts download), or fallback was used
- [ ] Hosting target and deployment approach

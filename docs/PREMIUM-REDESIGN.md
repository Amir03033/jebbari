# Phase 4: premium redesign (personal brand)

Owner request: a complete redesign toward an Apple / Linear / Vercel level of
restraint, with a full light and dark theme, Geist-style typography and a
richer motion system. This document records what changed and where it
deliberately replaces earlier decisions in `BRIEF.md`.

Screenshots: `docs/screenshots/phase-4/`.

## What replaces the original brief

| Brief (v1) | Now | Why |
|---|---|---|
| Satoshi / Instrument Sans | **Geist** (variable, self-hosted, OFL) with Geist Mono | Owner asked for Inter, SF-style or Geist; one family for text and labels |
| No gradients at all | One **soft light** (a radial glow in the accent, 10 to 11 % alpha) behind the hero, page openings and closing panels | "Subtle gradients and depth"; still no gradient text, buttons or borders |
| Curated black sections, no automatic dark mode | **Two complete themes**: every surface follows the theme; the only fixed contrast surface is the closing panel and the typographic project tile | "Implement a complete light and dark theme" |
| Rings, giant wordmark, dot-to-night transition, sideways work run | Removed | Outdated devices; replaced by the light field and the case study stack |
| 28 to 32 px card radius | 18 to 24 px | "No excessive rounded corners" |

Unchanged: the orange full stop as the only accent, mono index labels, hairlines,
CMS-driven content, NL/EN routing, strict CSP, no tracking, no animation library.

## Design system

- **Tokens** (`resources/css/app.css`): `--paper`, `--paper-2`, `--paper-3`, `--ink`,
  `--ink-2`, `--line`, `--line-strong`, `--signal`, panel tokens, `--glow`. Components
  only read the roles `--fg`, `--muted`, `--surface`, `--card`, `--line`.
- **Contrast** (measured): muted text 5.9:1 on light paper, 7.3:1 on dark paper.
- **Type scale**: `.t-hero`, `.t-display`, `.t-h2`, `.t-h3`, `.t-statement`, `.t-body`,
  `.t-label`. All fluid (`clamp`), weights 400 and 500 only.
- **Surfaces**: `.card` (bordered, lifted surface), `.panel.on-ink` (contrast panel
  set into the page), `.plate-frame` (image frame), `x-tile` (typographic project visual).

## Components

| Component | Role |
|---|---|
| `partials/header` | Clear bar that frosts once the page moves, hides while reading down; nav hairline glides to the hovered link; full-screen mobile menu |
| `blocks/hero` | Display headline (CMS), light field on a fine grid, status, local time, scroll cue |
| `x-page-opening` | Every inner page: title as h1, lead, soft light |
| `x-case-card` + `.stack` | Homepage projects as case study cards that stack while scrolling (laptop and up); a plain list on phones and under reduced motion |
| `x-plate`, `x-tile` | Work index, related work; typographic tile when a project has no image |
| `x-cta-panel` | One closing panel for home, case study, service and article pages |
| `partials/footer` | Brand, status, local time, live status panel, navigation, contact, legal, back to top |

## Motion

CSS first, one small script (`resources/js/motion.js`), no library. Only
transform, opacity and clip-path animate.

- Page transitions: the old page lifts away, the new one settles in (cross-document view transitions; plain navigation elsewhere).
- Hero: lines rise from a mask, the full stop lands last, supporting text and buttons follow; the light leans toward the pointer; the stage settles back as the page scrolls on.
- Scroll: hairlines draw, labels fade, text rises in a stagger, images open from an inset clip while easing from 1.12 to 1, words fill as you read, the process numeral rolls, case cards stack.
- Interaction: gliding nav hairline, magnetic buttons, arrow rings that fill, image zoom on hover and focus, pointer ring and "View case" label (fine pointers only), theme switch as a cross-fade.
- Reduced motion: opacity fades only; no sticky stack, no marquee, no pointer effects.
- Without JavaScript every word and image is visible.

## Verified

- 207 tests, Pint clean.
- Headless Chromium, 390 / 820 / 1280 / 1440 px, light and dark, NL and EN: no
  horizontal overflow, one `h1` per page, layout shift 0.0000 on full scrolls,
  no console errors, no CSP violations, no elements left hidden.
- Reduced motion: no running animations. JavaScript disabled: full content visible.

## Not verified

- Safari / WebKit and real devices (only Chromium is available in the build environment).
- Lighthouse scores on production hosting.
- The live site's content: the build environment cannot reach jebbari.nl, so the
  design was reviewed with the seeded and sample content.

## For the owner

- **Copy is yours.** Headlines, the hero label, the about text, skills, projects
  (ServerScope, ChinaSupply and others) and contact details come from the CMS and
  were not rewritten or invented. To position the site as a personal brand, edit
  the hero block and the About page in the admin; the header no longer shows the
  "Digital studio" descriptor.
- Project cards look best with a cover image and a one-line summary per project.

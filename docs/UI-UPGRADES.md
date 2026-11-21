# Phase 6: UI upgrades

Owner request after reviewing the live site: "make everything, very professional,
sleek animations that don't get in the way of reading, accessible and
understandable". Screenshots: `docs/screenshots/phase-6/` (local build with copies
of the live project images; the doubled full stop in the hero there is local test
data, not the live headline).

New styles live in `resources/css/polish.css`. No new dependencies, no new copy
(nothing hard-coded; all text still comes from the CMS and lang files).

## What changed

| | Change | Where |
|---|---|---|
| A | **Hero showcase.** From 80rem (1280px) wide, up to three real project cover images sit in a loose, slightly rotated stack beside the headline. They rise in after the headline lands, lean a little toward the pointer and spread apart as the page scrolls away. The headline makes room (7 of 12 columns). Only shown with at least two projects that have a cover image. Below 80rem the hero is unchanged and those images are never downloaded (lazy and not displayed). | `blocks/hero.blade.php`, `polish.css`, `motion.js` (`initField` now writes `--gx/--gy` on the hero) |
| B | **Homepage shows up to four projects.** The work section ("Uitgelichte projecten") now leads with the featured projects in their featured order and fills the remaining places (up to the block's limit, max 4) with the newest other published projects. With two or more, the existing stacking case cards appear. "Alleen mijn selectie" is unchanged. | `Site::highlights()`, `blocks/work_selection.blade.php`, `BlockRenderer`, admin label |
| C | **Compact services.** A two-column index (one column on phones): number, name, one line, up to four tags. About a third of the old height. Names are now real `h3` headings, so screen reader users can jump between them. Hover and focus: a soft surface, the others recede, the arrow ring fills. | `components/service-rows.blade.php` (homepage, services page, "other services") |
| D | **Even work grid.** Every project image in the same 16:10 frame, two to a row. With an odd number of projects the first one leads across the full width with its caption beside the image, so no row is half empty and no image is squeezed into a portrait or extra-wide frame (which caused the cropped logos). Long Dutch compounds in project names hyphenate instead of breaking mid-letter. | `components/project-grid.blade.php`, `components/plate.blade.php` |
| E | **Process as step cards.** The steps are cards that settle under the header one after another (tablet and up). The left column stays in view with the rolling numeral and an index of all steps with a progress line, so the empty column is gone and you always see where you are. Phones and reduced motion: a plain list of cards. The index repeats the card titles, so it is hidden from screen readers. | `blocks/process.blade.php`, `polish.css` |
| F | **Floating header.** Once the page moves, the bar becomes a rounded, frosted bar floating inside the margins (tablet and up), with the reading-progress line along its bottom. The current page and the link under the pointer or keyboard focus sit on a soft pill that glides between links (clip-path only). Still hides while reading down and returns on scroll up or focus. | `polish.css` (reuses `initNav`) |
| G | **Theme switch.** The new theme opens as a circle from the switch you pressed (view transition + clip-path), including the header. Instant under reduced motion or where view transitions are unsupported. | `motion.css`, `initTheme` in `motion.js` |

## Rules kept

- Only `transform`, `opacity`, `clip-path` (and the individual `translate`/`rotate` properties) animate. Nothing loops, bounces or takes over scrolling.
- Colours only from tokens; both themes checked.
- Strict CSP unchanged: no inline styles or scripts in the HTML; CSS variables are written through the CSSOM as before.
- BRIEF.md bans bento grids, so the homepage keeps the stacking case cards rather than a bento layout; services stay typographic (no icons).

## Accessibility

- Showcase images are decorative (`aria-hidden`, empty `alt`, not focusable); the same projects are linked by name in the work section.
- Heading order: section `h2`, services and process steps `h3`.
- Every hover effect has a focus-visible equivalent; service cards get a rounded focus ring.
- Reduced motion: no translation, rotation drift, sticky stacking or circle reveal; short opacity fades only. Verified: 0 running animations.
- Without JavaScript every word and image is visible (same as before: only the inactive process numerals stay hidden by design).

## Verified (local, headless Chromium)

- 238 tests pass (2 new in `HomepageTest`; the old "featured only" test was replaced by "featured first, then filled up"), Pint clean.
- 1440, 1280 and 390 px; light and dark; NL and EN: no horizontal overflow, no console or CSP errors.
- Layout shift on full scrolls of home, work and services: at most 0.0002.

## Not verified

- Safari / iOS (view transitions, `hyphenate-limit-chars` and sticky behaviour should be checked on a real device; all degrade gracefully).
- The live database: the homepage will now show up to four projects. If you want only specific ones, choose "Alleen mijn selectie" in the work block.

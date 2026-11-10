# Phase 3.6: calmer scale, one scroll engine (Safari), more life

Feedback after the first live deploy: scroll animations did not match each other (Safari), some things were far too large, wanted more animation and a better UI, with laptop and phone as the priority.

## Cause of the scroll mismatch
Scroll-linked effects used two systems: CSS `animation-timeline` (Chrome, newest Safari) and a JS fallback with different maths (older Safari), next to one-shot IntersectionObserver reveals. Different curves, different start points.

## Changes
- **One engine.** `initScrollLinked()` in `resources/js/motion.js` writes `--p` (0 to 1) on `[data-scroll]` sections and `--page` on `<html>`; CSS only reads them. The process timeline fill uses the same rAF scroll loop. All `animation-timeline` CSS is removed, so every browser follows the same curve.
- **Reveals** fire at one rule everywhere (8% into the viewport), including very tall blocks on phones.
- **Scale.** Hero wordmark 30vw to 14vw (max 13rem; rings follow via `--wm`), footer wordmark 13vw (max 12rem), page titles max 6.75rem (was 10), H2 max 3.75rem, statements max 2rem, service names max 4.75rem, process numerals about 60%, next-project title max 6rem, section padding max 140px (was 200).
- **More life.** Page progress line under the header (signal colour), press feedback on buttons and arrow rings, services rows get a soft surface sweep on hover. Transform and opacity only; reduced motion hides or removes them.

## Verified
200 tests, Pint clean. Headless Chromium scroll test: about 60 fps, p95 frame 17 to 19 ms, layout shift 0, no CSP violations. **Not verified: real Safari (macOS and iOS)**; WebKit is not available in the build environment.

## Round 2: more life and a better UI (owner request)

All of it uses the same single scroll engine (`--p`, `--fill`, `--page`), CSS-first, no library, CSP-clean, and is off or static under reduced motion.

**Interaction**
- Hero wordmark letters lean toward the pointer (touch: they fan out as the hero scrolls away); a click on the full stop sends a wave through the rings. In the footer the same dot scrolls to the top.
- Scroll-filled text (`data-fill`): words go from faint to full while you read (About teaser, work intro).
- Sideways work run (`data-hs`): pinned on a laptop (native vertical scroll drives it, no hijacking, keyboard focus brings an off-screen project into view), swipe row on touch and under reduced motion. With one project it stays a grid.
- Cursor ring (fine pointers only, the system cursor is never hidden), magnetic buttons and tile arrows.
- The orange dot becomes the night: a dark circle grows from it before the closing section.

**Content-driven (nothing invented)**
- Technology marquee block (automatic, from the stacks of published projects; hidden below 3 technologies; static list under reduced motion).
- Stats block: the owner enters real numbers (count-up on view). Hidden until filled in.
- Add both in the CMS: Pages, Home, "Onderdeel toevoegen".

**Practical UI**
- Light/dark theme: follows the system, header switch remembers the choice (localStorage, applied before paint by `public/js/motion-flag.js`).
- Mobile menu: pill button with animated icon, numbered display links, contact button, language and theme at the bottom.
- Images fade in softly (frame keeps its size: no layout shift); animated confirmation after the contact form.
- Live status panel in the footer in a monitoring-dashboard style: really requests `/up`, the home page and the admin login (HEAD, no cookies) every 20 seconds while visible and draws latency bars and a verdict. Nothing is simulated.

**Verified:** 207 tests, Pint clean, browser pass (see below). Not verified: real Safari.

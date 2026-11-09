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

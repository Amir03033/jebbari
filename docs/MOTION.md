# Jebbari motion system (plan, for owner review)

Status: **owner decisions recorded; primitives built in Phase 1.5 (see section 9).** It extends brief section 6, and per the owner's direction it replaces "motion pass at the end" (Phase 6) with "motion designed into every phase".

## 1. Principles

1. **Motion explains structure.** Things enter in reading order, from the place the eye already is. Nothing moves just to move.
2. **One voice.** One easing, a small set of durations, one distance. Every animation draws from the same table, so the site feels like one hand made it.
3. **Quiet, not slow-looking.** Premium = precise timing and restraint, not long durations. Nothing bounces, overshoots or loops.
4. **Content is never hostage to JS.** Without JS, with reduced motion, or for crawlers and print, every word and image is visible and in place.
5. **No layout shift, ever.** Only `transform` and `opacity` animate (plus `clip-path` on image masks). Space is always reserved.

## 2. Tokens

Defined once as CSS custom properties next to the colour tokens.

| Token | Value | Use |
|---|---|---|
| `--ease` | `cubic-bezier(0.22, 1, 0.36, 1)` | Everything (already in `app.css`) |
| `--dur-fast` | 200ms | Hover, focus, colour/underline |
| `--dur-base` | 400ms | Small state changes, menu links |
| `--dur-reveal` | 700ms | Scroll reveals (brief) |
| `--dur-hero` | 900ms | Hero line reveal (brief) |
| `--dur-slow` | 600ms | Plate image scale (brief) |
| `--stagger` | 60ms | Delay step between siblings |
| `--shift` | 16px | Reveal travel distance (brief) |

Stagger is driven by a per-element index set in Blade (`style`-free: a utility class per step, `delay-1`..`delay-8`, no inline styles), so timing lives in CSS, not JS.

## 3. Architecture (lightweight, no animation library)

- **One tiny script** (`motion.js`, target < 2 KB gzipped) with one `IntersectionObserver` that adds `is-in` to elements marked `data-reveal`, then unobserves them (once only, 15% threshold, as in the brief).
- **CSS does all the animating.** Initial hidden states exist only under `html.js`, so no JS means content stays visible.
- **Reduced motion:** `@media (prefers-reduced-motion: reduce)` removes all translate, scale, clip, parallax and line-fill. Reveals become a short opacity fade (200ms). The page must still feel finished.
- **CSP and the `js` flag:** the flag must exist before first paint or hidden elements flash visible then hide. A deferred module cannot do that, so the plan is a tiny classic `<script src>` in `<head>` (one line, same-origin, cacheable, render-blocking by about a millisecond) or a CSP nonce on an inline one. Either works with the strict CSP; I will pick the external file unless you prefer the nonce. The hero is the exception: it is gated by the same flag but its sequence starts immediately.
- **Performance:** no `will-change` except on the few elements mid-animation (added/removed by class). No scroll listeners except one passive, rAF-throttled listener for the process line and optional plate parallax. Budget: motion adds < 2 KB JS and < 3 KB CSS gzipped; Lighthouse stays at the brief's bars.
- **Layout-shift rules:** every revealed element keeps its box. Images have width/height. Line masks reserve their full line height. Verified by a Playwright CLS measurement (see section 6).
- **GSAP:** not needed. Every interaction below is achievable with CSS plus IntersectionObserver, and the process line has a CSS-only path (4.6). I will not propose GSAP unless a specific interaction fails in testing.

## 4. The choreography

### 4.1 Hero entrance (Home)
A single ~1.6s sequence, once per page load, nothing else animating at the same time.

| t (ms) | Element | Motion |
|---|---|---|
| 0 | Header hairline | draws from left (scaleX 0→1, 700ms) |
| 80 | Mono label `Software studio, Zwolle & Amsterdam` | fade + up 8px, 600ms |
| 160 | Headline line 1 | masked translateY(100%→0), 900ms |
| 220 | Headline line 2 | same, +60ms stagger |
| 220 + line end | **The full stop** | scales 0→1 from its own centre, 400ms, lands last: the brand mark "finishes the sentence" |
| 700 | Supporting sentence | fade + up 16px, 700ms |
| 850 | `Start a project →` | fade + up 16px; arrow nudges 4px right once, 400ms |
| 900 | Header nav + status dot | fade in, 500ms (never delays LCP; the headline is the LCP element) |

Lines are split **server-side** in Blade (`<x-lines>`), each line in an `overflow: clip` wrapper with `padding-block` so descenders are never cut. No client-side text splitting, so no shift and no flash. NL and EN headlines break differently; line count comes from the lang file's explicit line array rather than measuring.

### 4.2 Typography and content reveals
- Section label row: hairline draws left→right (scaleX, 700ms), label fades in 100ms after.
- H2: masked line reveal like the hero (same component), 700ms.
- Body and supporting text: fade + up 16px, 700ms, staggered 60ms by paragraph. Max 4 staggered items per group, so long content never feels slow.
- Table-like rows (services, spec sheet, packages): each row's top hairline draws, then its cells fade up with a 60ms step. Spec-sheet **numbers appear in place; they never count up** (brief ban).

### 4.3 Scroll-triggered section and image reveals
- Sections reveal as described in 4.2 when 15% visible, once.
- **Images and plates:** a `clip-path: inset(0 0 100% 0)` → `inset(0)` wipe (900ms) with the image inside scaling 1.06 → 1 (900ms). The frame (1px border) is visible immediately, so space is reserved and nothing shifts.
- **No parallax anywhere** (owner decision): performance and a controlled experience come first.

### 4.4 Project plates (Work)
- **Hover (pointer devices only):** image scale 1 → 1.01 over 600ms (brief). Caption row: the title shifts 4px right and an arrow `→` slides in; the hairline above the caption draws signal-coloured left→right.
- **Cursor label:** a small mono "View case" label follows the pointer (rAF, transform only, `pointer: fine` only), fades in on enter and out on leave.
- **Focus-visible:** same visual change as hover plus the focus ring, so keyboard users get the full interaction.
- **Neighbours** do not dim or move (restraint).
- Plates enter with the 4.3 image reveal; the caption row follows with a 100ms stagger.

### 4.5 Buttons, links and navigation
- **Links:** signal underline draws left→right in 200ms (built). Arrow links: arrow nudges 4px on hover.
- **Buttons:** ink fill inverts to outline on hover (built, 200ms). Press state: `translateY(1px)`. No scale, no glow, never an accent fill.
- **Header nav:** active marker (signal dot) settles in with a 200ms scale; hide/show on scroll stays 300ms (built).
- **Language switch:** the two codes cross-fade emphasis in 200ms.
- **Form fields:** the border darkens in 200ms; error text fades in with 8px travel; success replaces the form with a 400ms cross-fade.
- **Big email (Contact):** on click, the address text swaps to "Copied" with a 200ms cross-fade, then back after 2s.

### 4.6 Process timeline (Home, section 04)
- The vertical hairline's fill is a `scaleY` driven by section scroll progress.
- **Primary implementation: CSS scroll-driven animation** (`animation-timeline: view()`), zero JS, runs off the main thread. **Fallback** where unsupported: a single rAF-throttled passive scroll listener writes one CSS custom property (`--progress`) on the section.
- Each of the four steps transitions `--ink-2` → `--ink` as the line passes its marker (colour transition 400ms, triggered at a threshold, not scrubbed, so text stays crisp).
- Reduced motion: the line is fully drawn, all four steps at full ink; no scrubbing.
- Sticky left title stays put; no movement on it.

### 4.7 Mobile menu
- Panel: opacity 0→1 and translateY(-8px→0), 400ms (not a slide-over, not a curtain).
- Links: staggered fade + up 16px, 60ms step, starting 100ms after the panel; their hairlines draw left→right.
- Toggle label cross-fades `Menu` ↔ `Close`, 200ms.
- Close is faster than open (200ms, no stagger): leaving should feel instant.
- Body scroll lock, Escape to close and focus return are already built and tested under CSP.

### 4.8 Page-to-page transitions
- Progressive enhancement only: CSS `@view-transition { navigation: auto }` with a 250ms opacity cross-fade of the main content, header persistent. Browsers without support navigate normally. No JS router, no SPA behaviour.

### 4.9 Footer
- Live clock: digits swap with no animation (a ticking motion would be decoration).
- Footer hairlines draw on reveal; nothing else moves.

## 5. What stays out
Looping or idle animation, parallax on text, cursor trails, magnetic buttons, scroll-jacking, smooth-scroll libraries, count-up numbers, springs and overshoot, animated gradients (banned), staggers longer than ~500ms in total, and anything that animates layout properties.

## 6. Verification (each phase, before sign-off)
1. **CLS:** Playwright `PerformanceObserver` for `layout-shift` through load plus a full scroll at 390 and 1280. Target 0.00.
2. **Frames:** Chromium trace of a full-page scroll with long-task and dropped-frame count; no long tasks from motion code.
3. **Reduced motion:** screenshots with `reducedMotion: 'reduce'`; every section is visible, ordered, and correct.
4. **No JS / CSP:** page rendered with JS disabled (all content visible) and under the strict CSP (no console errors).
5. **Keyboard:** every hover interaction has a focus-visible equivalent.
6. **Budget:** gzipped JS and CSS reported each phase.
7. **Video check for the owner:** a short screen recording of the hero and one scroll pass per phase for review, since screenshots cannot show motion.

## 7. Proposed rollout
- **Phase 1.5 (needs your OK):** add `motion.js` + motion tokens + a `08 / Motion` section on `/_style` that demonstrates the primitives (line reveal, fade-up stagger, hairline draw, image wipe, link/button states, menu transition), so you can review the feel before any page uses it.
- **Phase 2 (Home):** hero sequence, section reveals, services rows, process timeline, closing CTA.
- **Phase 3 (Work):** plates, hover/cursor label, image reveals, detail-page reveals.
- **Phases 4–5:** spec sheet, packages, form micro-interactions, copy-to-clipboard.
- **Phase 6:** audit and tuning only (frames, CLS, reduced-motion, budgets), not first-time motion.

## 8. Owner decisions (resolved)
1. **Hero:** the full stop lands last. Yes.
2. **Parallax:** none.
3. **Page transitions:** yes, subtle cross-document view transitions as progressive enhancement; navigation must never depend on them.
4. Keep the CSS + IntersectionObserver system. No GSAP unless genuinely necessary (it is not).

## 9. Phase 1.5 status (built on `/_style`, section 07 "Motion")

Built and verified: tokens; `data-reveal="up"` and `"group"`; `data-delay` stagger (60ms steps); masked line reveal (`x-lines`, server-split); full stop landing last; hairline draw (`section-label`, `spec-row`); plate component (`x-plate`) with image wipe, hover scale, caption hairline, arrow, and pointer-following "View case" label; link arrow nudge; button press; active-nav dot; mobile menu transitions; process timeline (CSS scroll-driven fill, step lighting); cross-document view transition; reduced-motion rules; print rules; `html.js` flag with a 3s failsafe; replay controls for review.

Not yet built (arrive with their pages): language-switch emphasis, form-field and error transitions, success cross-fade, copy-to-clipboard confirmation, hero use on the real homepage.

Measured on `/_style` (Chromium, strict CSP, no `unsafe-eval`):

| Check | Result |
|---|---|
| Layout shift, desktop full scroll | 0.0000 |
| Layout shift, 390px full scroll | 0.0000 |
| Layout shift, reduced motion | 0.0000 |
| Console errors under strict CSP | none |
| Elements hidden or transformed with JS disabled | 0 |
| Reduced motion: line/stop transforms, process fill | none (opacity fades only) |
| View transition on navigation | active normally, off under reduced motion |
| Built JS / CSS (gzipped) | 24.1 KB / 9.7 KB |
| Third-party requests | 0 |

One bug found and fixed in testing: IntersectionObserver does not fire when an element jumps from above the viewport to below it, which left process steps lit after a large scroll jump. The timeline now uses one passive, rAF-throttled scroll check.

Known limits: the frame capture and video are from headless Chromium, not Safari or a phone; Safari needs a hands-on check (cross-document view transitions and `animation-timeline` support there should be confirmed on a real device).

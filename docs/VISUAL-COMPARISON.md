# Phase 3.5: visual comparison (before and after)

Before: `docs/screenshots/phase-3/`. After: `docs/screenshots/phase-3.5/` (every page at 390, 768, 1280 and 1920 px, NL and EN, plus `reduced-motion-1280-nl-hero.png`). The audit and reasoning are in `VISUAL-REDESIGN.md`.

## What changed and why

| Area | Before | After | Why |
|---|---|---|---|
| Typeface | System font, one weight | Instrument Sans (OFL, self-hosted, preloaded); Satoshi stays first in the stack | Satoshi cannot be fetched here; no unofficial mirrors. Chosen from a 10-font render test |
| Type scale | Large but flat | Hero wordmark about 30 vw, display up to 10 rem, statement size for introductions, small mono labels | Scale contrast: one dominant element, small precise text beside it |
| Surfaces | One off-white, dark hero and footer | Deliberate light/dark alternation; header adopts the surface beneath | Rhythm builds and releases tension |
| Brand device | Tiny orange dot | Round full stop plus concentric rings (hero, tiles, openings, closing, 404) | One repeated, brand-derived shape instead of decoration |
| Shape | 0-2 px radii | One system: 12 px fields, 28-32 px cards, pill buttons | Softer, consistent |
| Hero | Headline and two buttons | Full-width `jebbari.` wordmark, rings contracting onto the dot, statement and buttons in a bottom bar | Memorable first viewport |
| Work | Pale rectangles | Large launch tiles; typographic tile with rings when no screenshot exists | Never a fake interface |
| Services | Four table rows | Display-size names, siblings dim on hover | No icon cards, real hierarchy |
| Process | Grey list | Sticky numeral that changes per step, line fills | Gives it a story, no scroll hijacking |
| About, CTA, footer | Heading and two lines; hero repeat; link list | Large statement with live local time; full-height ending; oversized cropped wordmark | A deliberate conclusion |
| Case study | Spec sheet | Dark opening, overlapping cover, overview, objectives, features, composed gallery, outcome, related work, next-project panel | Editorial product page; every section hidden when empty |
| Inner pages | Same skeleton | Shared `page-opening`; blog with featured first article; contact in a rounded card; dark 404/429 | Variety within one system |

## CMS impact
Two new translatable fields on projects, `objectives` and `features` (one point per line), with form inputs. Migration `2026_11_11_000001`. Everything else uses existing fields; nothing is invented.

## Verification
- `php artisan test` and Pint pass (two new tests for the case-study sections).
- Browser pass, 19 pages x 4 widths: layout shift 0.0000, no console errors, no CSP violations, no horizontal overflow, no broken images, one h1 per page, zero third-party requests.
- Reduced motion: nothing hidden in the first viewport, no infinite animations. Keyboard: visible 2 px focus ring on every stop.
- Motion uses only transform, opacity and clip-path; no animation library.

## Not verified
Safari/WebKit and real devices (Chromium only). Contrast was measured for the accent in `VISUAL-REDESIGN.md`; orange is used as a graphic mark, never for body text.

## Decisions for the owner
Keep Instrument Sans or add Satoshi; keep orange (recommended) or blue; the capital I in "Iets te bouwen?" reads like a lowercase l (reword or keep).

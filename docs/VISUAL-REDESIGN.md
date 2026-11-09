# Phase 3.5: Visual redesign (audit and plan)

Status: audit and plan written **before** any code was changed. The after-state is documented in `VISUAL-COMPARISON.md` when the work is done.

Creative direction: **Apple-inspired digital studio.** Apple's quality bar as inspiration (restraint, scale, typography, choreography), not a template to copy.

---

## 1. Visual audit of Phase 3

Reviewed: every Phase 3 screenshot (home, work, project, ServerScope, services, service detail, about, blog, article, contact, privacy, 404) at 390, 768, 1280 and 1920 px. The English pages use the same templates; I checked them for length, overflow and structure rather than re-reviewing each by eye.

### What works (keep)
Content architecture, CMS wiring, NL/EN routing, previews, SEO, accessibility, contact security, the motion primitives (masked line reveal, full stop landing last, hairline draws, plate wipe), reduced-motion handling, zero layout shift, strict CSP.

### What is generic or weak (change)

| # | Finding | Evidence |
|---|---|---|
| 1 | **One skeleton on every page.** Hairline, mono label row, giant H1 at the left, a small grey paragraph pushed to columns 8-12, then more hairlines. Home, work, services, about, blog and contact are interchangeable. | All inner pages |
| 2 | **No surface rhythm.** Everything is the same off-white; the only dark areas are the hero and footer. Nothing builds or releases tension. | Every full-page capture |
| 3 | **Flat typography.** System font, one weight, no real contrast between display and body; headlines are large but not expressive; body copy is small and grey. Dutch "Iets" reads as "lets". | Hero, services |
| 4 | **The hero is a headline plus two buttons.** It is the standard agency layout; the logo is repeated below it as a sign-off. Nothing memorable happens in the first viewport. | Home 1280/1920 |
| 5 | **Work looks like a template.** A big pale rectangle with a caption row. Without a screenshot (ServerScope) it is an empty box with a label; there is no composition. | Home, work index |
| 6 | **Services are a table.** Four rows of the same size; hover only nudges the title. Functional, forgettable. | Home, services |
| 7 | **Process is a grey list beside a thin line.** The timeline works but tells no story; the sticky title just repeats the section label. | Home |
| 8 | **About is a heading and two grey lines.** No sense of a person or place. | Home, about |
| 9 | **Closing CTA is a repeat of the hero.** Same type, same buttons; it does not feel like an ending. | Home |
| 10 | **Detail pages are mostly empty.** Large voids between a few small lines (service detail, about, project without gallery); numbered one-line lists repeat the same row everywhere. | Service, about, project |
| 11 | **Case studies are a spec sheet.** Meta list, two labelled paragraphs, a gallery. No objectives, no features, no story, no next-project navigation. | Project page |
| 12 | **Square, hairline-only UI.** 0-2 px radii and boxed fields feel technical and cold next to Apple-level softness; no consistent corner system; no surfaces to separate content from background. | Everything |
| 13 | **Header is a plain bar.** It does not respond to the surface beneath it; the mobile menu is a bare list. | Header |
| 14 | **Footer is a link list.** No brand finale. | Footer |
| 15 | **Images are an afterthought.** No consistent treatment (aspect, radius, mask), and the typographic fallback tile is an empty box. | Plates |
| 16 | **Motion is correct but uniform.** Everything is the same 16 px fade-up; there is no hero choreography beyond the lines, no scroll storytelling, no memorable hover. | Motion system |

### Root causes
1. A system built for restraint (the original brief's anti-slop rules) was applied without a **composition idea**: restraint became sameness.
2. **Scale contrast is missing**: the biggest type is large, but nothing is *dominant*, and nothing is small and precise next to it.
3. **No repeated brand device** beyond a tiny orange dot.

---

## 2. Design decisions

### Typography (resolves the Satoshi problem)
- **Satoshi cannot be obtained in this environment**: Fontshare is unreachable from the sandbox (403/blocked) and there is no legitimate package. I will not pull the files from unofficial mirrors (licence and provenance unclear).
- **Decision:** self-host **Instrument Sans** (SIL OFL, variable weight, Latin subset, preloaded, `font-display: swap`) as the site's face from now on, so no visitor depends on a system font. It was chosen over nine alternatives in a side-by-side render at display size (Geist, DM Sans, Manrope, Hanken, Schibsted, Bricolage, Outfit, Onest, Figtree): narrower, crisper letterforms allow very large type without losing refinement, and it reads editorial rather than generic-tech. Geist read as developer tooling, DM Sans and Outfit as friendly startup.
- `Satoshi` stays **first in the font stack**. When the owner downloads the files from Fontshare and drops them in `resources/fonts/`, the site switches with the steps in `resources/fonts/README.md`; nothing else changes. Because Satoshi is wider, display sizes may need a 2-3 % tracking tweak then.
- Geist Mono stays for labels.
- Scale: hero display up to ~15 vw (about 240 px at 1600), section display `clamp(3rem, 9vw, 9rem)`, H2 `clamp(2.25rem, 5.5vw, 5rem)`, body 1.1875 rem (19 px) with generous measure; strong contrast between display (500, tracking -0.045 em) and supporting text (400, muted).
- **The full stop is drawn, not typed.** Instrument Sans draws a square period; the brand mark is a round dot. The dot is an inline circle (about 0.17 em) so it is identical in every headline and can become a graphic device.

### Colour
- **Surfaces:** warm off-white `#F5F3EE`, deeper paper `#ECE9E2`, deep black `#0E0E0F`; a slightly lifted black `#161617` for cards on black. Alternating light and dark sections give the page rhythm (the Phase 1 "two dark sections" rule is replaced by deliberate alternation: dark hero, light work, dark services, light process, light about, dark closing and footer).
- **Accent evaluation (orange vs electric blue), measured:**

| | on black | on off-white | notes |
|---|---|---|---|
| Orange `#FF4B1F` | 5.77:1 | 3.02:1 | ownable, warm, reads as a full stop; passes as a graphic mark on both surfaces |
| Electric blue `#2D5BFF` | 3.72:1 | 4.67:1 | weak on black, the generic "tech" colour (also on the original brief's anti-pattern list), loses the full-stop idea |

- **Decision: keep orange.** The orange dot is the one thing nobody else has. It stays disciplined: the dot, status dot, focus ring, link underline, ring outlines. Blue remains a one-token alternative (`--signal`) if the owner wants to see it; I do not recommend it.

### Shape and surfaces
One corner system: `--radius-sm` 12 px (inputs, tags), `--radius-card` 28 px (project tiles, panels, images), `--radius-pill` (buttons). Borders are 1 px hairlines at 14-16 % contrast; no shadows except one very soft lift on light cards. Image frames are always the card radius.

### Brand device: the ring
The full stop becomes **concentric 1 px rings** (a period seen from above). It appears in the hero, the typographic project tile and the closing section, and scales slowly with scroll where the browser supports scroll-linked animation. It is the answer to "no random floating objects": one intentional, repeated, brand-derived shape.

---

## 3. Section plans: before and after

### Header and navigation
- **Before:** paper bar with hairline; descriptor and status text; plain mobile list.
- **After:** the header takes the surface beneath it (dark over dark sections, light over light) with a short colour transition, no blur. Wordmark gets the round dot. Language switch and nav as pill-less quiet text with the signal marker. Mobile menu: full-screen black, display-size links, staggered, with the language switch and status at the bottom.

### Hero (first viewport)
- **Before:** two-line headline left, paragraph and buttons lower right, wordmark sign-off below, one divider line.
- **After:** 100 svh black stage. The CMS headline is set at ~15 vw across the full width, filling the frame with negative space around it, ending in the round orange dot that lands last. A wordmark lockup (`jebbari.` + descriptor) anchors the top-left; a status line top-right. Three thin rings sit bottom-right, partly cropped, drawing in slowly. The statement and the two buttons sit in a bottom bar with a scroll cue line. Entrance choreography: rings draw, lockup, headline lines mask-reveal with stagger, dot lands, statement and buttons rise, hairlines draw.

### Selected work
- **Before:** pale 16:10 rectangles with a caption row.
- **After:** each project is a *launch*. Full-width 28 px-radius tiles (first project at 16:9, up to ~80 vh), then asymmetric pairs (7/5 columns, wide beside tall). Title, category and one line sit under or over the image in large type; a mask-and-settle reveal on scroll; on hover the image scales slightly and an arrow ring fills. Without a screenshot (ServerScope) the tile is a designed black panel with the project name huge, its category, and the ring motif: clearly typographic, never a fake interface. Real screenshots from the CMS replace it automatically.

### Services
- **Before:** four equal table rows.
- **After:** a black section with the service names at display scale (up to ~7 vw), indexed, with hairlines. Hovering or focusing a row dims the others and fills an arrow ring; the summary and tags sit beside the name. Mobile: names at ~2.5 rem with the summary always visible.

### Process
- **Before:** muted list beside a thin line.
- **After:** a sticky stage: a huge numeral (01 to 04) on the left changes as each step passes (the old digit slides up and out, the new one in), a vertical line fills, each step sets large type. Active step is detected with the existing passive scroll handler; no hijacking. Reduced motion: all four steps visible, numerals static.

### About (home teaser and page)
- **Before:** a heading and two grey lines.
- **After:** the owner's heading at display size and the introduction in a wide measure; if a photo exists it sits in a tall 28 px frame with a slow mask reveal; if not, the typography carries it. A location line, "Zwolle · Amsterdam" with the **live local time**, gives it place and presence without inventing anything. The About page uses the same language: large opening statement, two-column biography, skills and experience as refined lists, principles as large numbered statements.

### Closing CTA
- **Before:** the hero again.
- **After:** a full-height black ending: the CMS question set at ~13 vw, the email at display size with a signal underline that draws, the rings rising behind, the contact button as a pill. It flows into the footer, which ends in an **oversized `jebbari.` wordmark** cropped at the viewport bottom edge as a deliberate last frame.

### Case studies
- **Before:** title, small image, meta list, two paragraphs, gallery.
- **After:** a black opening with the title huge, category and year line, and the cover as a large rounded visual. Then: **Overview** (summary at large size), **Objectives** (new CMS field), meta grid (category, year, client, technology chips), **Features and implementation** (new CMS field plus the existing "what was built" text), **Interface** gallery with varied layouts (full, paired, offset) and captions, **Outcome** as a large statement (only if the owner wrote one), related work, and a closing **Next project** panel the full width of the page. Every section is hidden when empty: nothing is invented.

### Services detail, About page, Blog, Contact, Legal, errors
- Services detail: dark header, summary large, benefits and deliverables as two-column statement lists, packages as rounded cards, next service panel.
- Blog: a featured first article (large image), then a refined grid; article pages with a wide hero image, a readable measure and a "next article" panel.
- Contact: the email at display size, the form in a rounded surface with soft fields, pill button.
- Legal: calm reading layout on paper.
- 404 / 429: dark, ring motif, one line.

---

## 4. Motion plan (CSS and the existing small script; no animation library)

New or upgraded: hero choreography with rings; image mask-and-settle with rounded clip; services dim-siblings hover and ring fill; process numeral roll and line fill; header surface transition; scroll-linked ring scale (CSS scroll-driven animation, progressive); page transition kept at 250 ms; pill button arrow slide. Rules unchanged: only transform, opacity and clip-path animate; nothing blocks content; no scroll hijacking; no parallax; no blur; reduced motion keeps opacity fades only; strict CSP preserved. GSAP is **not** needed: every effect above is CSS plus the existing observer/scroll handler.

## 5. CMS changes this plan needs

Only two small additions so case studies can have the requested sections: `projects.objectives` and `projects.features` (translatable, one point per line), with form fields. Everything else uses existing fields. All sections stay editable in Filament and hidden when empty.

## 6. Acceptance checks

Screenshots of every page at 390, 768, 1280, 1920; reduced-motion capture; layout shift 0; no CSP violations or console errors; keyboard and focus visible; contrast measured; NL and EN; CMS integration and draft previews intact; all existing tests pass; new tests for the new fields and sections; Pint clean; `VISUAL-COMPARISON.md` with before/after.

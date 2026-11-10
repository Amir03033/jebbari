# Phase 5: new pages, richer case studies, new accent, clearer structure

Owner request after Phase 4. Screenshots: `docs/screenshots/phase-5/`.

## Colour and structure

- **Accent:** cobalt instead of orange (`--signal`: `#2B4EF5` light, `#8EA2FF` dark and on panels; 6.6:1 and 7.6:1, so it may also be used for text). Neutrals are slightly cooler to match. One token per theme in `resources/css/app.css`; favicon and default share image follow.
- **Clearer pages:** every section opens the same way (`x-section-label`): hairline, index number, the section title as its one `h2`, an optional intro beside it. Section bodies share one spacing (`.section-body`). Sections that bring their own heading use `plain`.
- **Copy:** interface texts are in the first person; the descriptor is "Software developer & entrepreneur".

## New pages (system pages, edited under Pages in the admin)

| Key | NL | EN | Blocks |
|---|---|---|---|
| `now` | `/nl/nu` | `/en/now` | intro, current focus, text, GitHub |
| `uses` | `/nl/stack` | `/en/uses` | intro, any number of tool lists |
| `cv` | `/nl/cv` | `/en/cv` | intro; the rest is assembled from the About page and the projects, with a print stylesheet ("Save as PDF") |

They appear in the footer once published. No PDF library was added: the CV prints from the browser.

## New blocks

- **Questions** (`faq`): home, services, contact. Native `details` accordion plus `FAQPage` structured data.
- **GitHub** (`github`): home, about, now. Set the username under Site > Sociale links. The server fetches and caches the repositories (6 hours; failures 15 minutes), so no visitor request goes to GitHub. Hidden without a username or when GitHub is unreachable.
- **Newsletter** (`newsletter`): home, blog. Double opt-in by email, signed confirm and unsubscribe links, sign-ups under Inbox > Nieuwsbrief. Sending newsletters is not built.

## Case studies

New project fields: timeline, changelog, live demo address, before and after images.
The live demo is only loaded when the visitor asks, and the Content-Security-Policy
allows frames from exactly the demo hosts of published projects.

## Other additions

- **Command menu:** Ctrl/Cmd + K or `/`. Pages, projects, services, articles, theme, language, copy email.
- **Work filter** by technology (shown from four projects, for technologies used more than once).
- **Articles:** table of contents that marks the current heading, reading progress line.
- **RSS:** `/{locale}/blog/feed.xml`, linked from the page head and the blog.
- **Share images:** `/share/{locale}/{work|blog}/{slug}.png`, drawn with GD in Geist, used when no image is chosen. Needs GD with FreeType; otherwise the default image is used.
- **Statistics:** the site counts page views itself (`page_views`: date, path, number). No cookies, no IP address, no third party; bots, signed-in editors, Do Not Track and Global Privacy Control are skipped. Shown on the admin dashboard.
- **Motion:** pointer light on cards, case visuals that lean toward the pointer, the case study cover growing into place, accordion and command menu entrances.
- **Easter eggs:** Konami code or "blueprint" in the command menu draws the 12-column grid; typing `jebbari` or "confetti" in the command menu celebrates; a note in the console. All skipped or instant under reduced motion.

## Proposed content

`php artisan content:propose` fills every page with proposed texts in Dutch and English
(`database/seeders/PersonalBrandSeeder.php`). Nothing goes live by itself:

- a published page gets the proposal as unpublished changes;
- a draft page is filled in; a missing page is created as a draft;
- by default only missing sections are added; `--replace` proposes each page anew.

Facts only the owner knows are marked `[[ OWNER: ... ]]`; the CMS refuses to publish
a page that still contains one. ChinaSupply is created as a draft with its name only.

## Deploy notes

- Run `php artisan migrate` (new columns on `projects`, tables `page_views` and `subscribers`).
- Node 20.19+ is needed to build; PHP needs GD with FreeType for share images.
- Mail must work for newsletter confirmations.

## Verified

219 tests, Pint clean. Headless Chromium at 390 and 1440 px, light and dark: no
horizontal overflow, no console or CSP errors; command menu, filter, accordion,
before/after, table of contents and the easter eggs exercised.

## Not verified

Safari and real devices; the GitHub block against the real API (unreachable from the
build environment, covered by tests with a faked response); a real embedded demo;
real email delivery; Lighthouse. The "Built to a standard" block still waits for
measured numbers in `config/specs.php`.

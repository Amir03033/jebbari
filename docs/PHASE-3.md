# Phase 3: the public website

Status: built, tested and verified in a browser. Not deployed. Awaiting owner review.

## 1. What exists

| Page | URLs (NL / EN) | Content comes from |
|---|---|---|
| Homepage | `/nl`, `/en` | Pages > Home: hero, services, selected work, process, about teaser, closing call to action |
| Work | `/nl/werk`, `/en/work` | Pages > Work (intro) + all published projects |
| Project | `/nl/werk/{slug}`, `/en/work/{slug}` | Projects (summary, brief, built, outcome, stack, gallery, cover, SEO) |
| Services | `/nl/diensten`, `/en/services` | Pages > Services + published services and packages |
| Service | `/nl/diensten/{slug}`, `/en/services/{slug}` | Services (summary, description, benefits, deliverables, timeline, tags, packages, image, SEO) |
| About | `/nl/over-ons`, `/en/about` | Pages > About (intro, biography, image, skills, experience, principles) |
| Blog | `/nl/blog`, `/en/blog`, article `/…/blog/{slug}`, category `/nl/blog/categorie/{slug}` `/en/blog/category/{slug}` | Posts and categories |
| Contact | `/nl/contact`, `/en/contact` | Pages > Contact + Site > Contact settings; submissions go to Inbox |
| Legal | `/nl/{slug}`, `/en/{slug}` (privacy, terms) | Pages > legal pages |
| Technical | `/sitemap.xml`, `/robots.txt`, 404 and 429 pages | generated |

Header, footer, navigation, status line, company details, email, booking link, social links and SEO defaults come from Filament (Site menu and Navigation). Interface text (buttons, form labels, accessibility labels, empty states, validation messages) lives in `lang/nl/site.php` and `lang/en/site.php`. **No page copy is hard-coded in Blade.**

## 2. Rules the site follows

- **Only live content is public**, and only in languages where it is complete. Drafts, unpublished changes, scheduled items before their date, deleted posts and anything missing a Dutch or English required field are not shown (404 or omitted). Test: `SiteVisibilityTest`.
- **Nothing is invented.** A block with no content (no published services, no featured projects, no spec measurements, an empty about teaser) is left out and the section numbers close up. Contact email, booking link, company details, KvK, BTW and social links appear only once entered in the CMS. Structured data contains only entered facts (no address, rating, review or founding date). Test: `HomepageTest::test_no_placeholder_text_ever_reaches_the_public_pages`.
- **Owner placeholders can never go live.** Any text containing `[[ OWNER: … ]]` blocks publishing, so the draft legal pages cannot be published until completed.
- **Links never point at pages that are not live** (hero buttons, navigation).
- **ServerScope** is seeded as a draft with only what the brief states: name and category "Server monitoring dashboard". Publish it in the admin; it then appears as a typographic tile with just those facts. Add year, description, images and a result line yourself when they are verified.

## 3. Design

- **Kept the approved system**: paper and ink palette, Geist Mono labels, hairline structure, section numbers, the motion system (hero line reveal with the full stop landing last, reveals, plates, process timeline, page transition), reduced-motion and strict CSP.
- **Dark sections**: the hero and the closing call to action plus footer (which flow together as one dark block), so two dark areas per page as the brief allows.
- **Brand**: wordmark `jebbari.` with the descriptor "Digital studio" (NL "Digitale studio") in the header, hero sign-off, footer and share image.
- **Not done, needs your decision**: the request mentions "subtle electric blue". The approved palette uses one orange accent (`--signal`) used only for the full stop, status dot, focus ring and link underlines. Switching the accent to blue is a one-line token change in `resources/css/app.css` (check contrast first; blue and orange differ). I did not change the approved identity without asking.
- No gradients, glass, icon cards, stock imagery, logo walls, counters or carousels.

## 4. CMS changes made for the frontend

- Projects: `summary` (card text); only title and category are required to publish.
- Services: `benefits`; deliverables are the existing "included" field.
- Hero block: primary and optional secondary button; new "About me (short)" block for the homepage (hidden until written).
- Seeder: the four services you named (Website development, Custom web applications, E-commerce, Maintenance & hosting), blog in the navigation, ServerScope, draft legal pages with clearly marked placeholders. All content is a draft; nothing is live until you publish.
- `SampleContentSeeder` (local only, refuses production): clearly labelled sample projects, articles and text so the design can be reviewed. Run `php artisan db:seed --class=SampleContentSeeder` after the normal seeder.

## 5. SEO and performance

Unique title and description per page and language (CMS override, then template, then defaults), canonical, hreflang with x-default listing only languages where the page exists, Open Graph and Twitter tags, JSON-LD (ProfessionalService, WebSite, Service, CreativeWork, BlogPosting, BreadcrumbList), sitemap with language alternates (drafts, untranslated and noindex pages excluded), dynamic robots.txt with the sitemap address, a typographic default share image (`public/og/default.png`), strict CSP with no inline scripts or styles, no third-party requests, responsive AVIF/WebP images with explicit dimensions, `sizes`, lazy loading below the fold and an eager first image.

Measured in Chromium on the local server (14 pages, both languages, 390/768/1280/1920 px): **layout shift 0.0000 on every page**, no CSP violations, no console errors, no failed or broken requests, no horizontal overflow, every image dimensioned, exactly one `h1` per page. Interactions checked: contact form end to end (inline errors, confirmation), copy-to-clipboard, skip link first in tab order, 2px signal focus ring, mobile menu with Escape, language switch mapping detail pages, reduced motion leaves nothing hidden.

JavaScript on public pages is about 24 KB gzipped (Alpine CSP build plus the motion script). A formal Lighthouse run and a production-build timing were **not** done: Lighthouse is not installed here and there is no production server. The spec-sheet section therefore stays hidden (`config/specs.php` is empty) rather than showing unmeasured numbers.

## 6. Contact form security

Server-side validation in Dutch and English, hidden honeypot field (bots are told it worked and nothing is stored), minimum-time and maximum-age check on an encrypted timestamp, CSRF, rate limit of 3 per minute and 10 per hour per address (friendly 429 page), IP address stored only as a keyed hash, inquiry stored in the CMS inbox before any email is attempted (a mail failure never loses an inquiry), notification to the address set in Site > Contact (skipped if none), no third-party captcha.

## 7. Tests

198 tests pass (`php artisan test`), Pint is clean. New this phase: `SiteVisibilityTest`, `HomepageTest`, `DetailPagesTest`, `ContactFormTest`, `SeoTest`, `PreviewRenderingTest`, `CmsGapsTest`. Existing tests were updated only to publish the site first; none were removed or weakened.

## 8. Browser limitations

Verified in headless Chromium only. Not verified in Safari/WebKit or Firefox, or on real iOS/Android devices. Features to check there: cross-document view transitions (progressive: normal navigation otherwise), CSS scroll-driven process timeline (a small script fills it where unsupported), `:focus-visible` rings, the date and select controls, and image formats (AVIF is skipped on servers that cannot encode it; WebP and the original always exist). The system font is used until Satoshi's files are added, so letter shapes (for example capital I looking like lowercase l in "Iets te bouwen?") differ from the final typography.

## 9. Still needed from you

See `docs/OPEN-ITEMS.md`: real content for every field you want shown (about text and photo, project details and screenshots, outcome lines, prices), contact email, booking link, company, KvK, BTW, social links, legal text, decision on the blue accent, Satoshi files, production hosting facts.

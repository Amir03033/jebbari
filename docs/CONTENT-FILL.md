# Filling the gaps on the live site (`php artisan content:fill`)

On 18 November 2026 every page of jebbari.nl was read (all 82 URLs in the sitemap,
NL and EN). Most content is complete. `content:fill` proposes what was missing or
inconsistent, using only facts already on the site. Nothing goes live by itself:
everything becomes an unpublished change, to review and publish in /admin.

Run on the server: `php artisan content:fill`. Running it again changes nothing.

| Where | What was wrong | Proposal |
|---|---|---|
| Over mij (and so the CV) | No skills block: the CV's "Vaardigheden" showed the process steps | Skills block: web applications and SaaS, backend, frontend, servers and security, monitoring (from the About text and the stack page) |
| Stack | Only the site and basics | Block "Eigen producten": Filament and Livewire, Python (ServerScope agent), React/Vite/Chart.js, MariaDB/MySQL, Nginx/Plesk, AI agents (Irooni), all from the project pages |
| Home (SEO) | Description said "Wij" and "digital studio" | First-person description |
| Privacy | Covered only the contact form; a stray "Algemene Voorwaarden" heading; the SEO description was the About text | Full statement: contact, website check and watch (incl. Google PageSpeed), calculator, requests, appointments, proposals, newsletter, cookies, own statistics, Turnstile, demos, rights. Retention as the code applies it (90 days, 7 days, 30 days, 1 year); your facts (Onholland B.V., KvK, address, 12 months for contact) kept |
| 7 services | Summaries/texts in "wij/we" while the site speaks as "ik" | Same content, first person, NL and EN (only where the text still says wij/we) |
| BarberCuts | "Resultaat" heading with no text | Result from the changelog: version 1.0 live on barbercuts.nl since November 2026 |
| ServerScope | No timeline | Four phases from the article "Van idee naar product", without dates (only real dates go there) |
| ReviewsPilot | No result | Honest result: a worked-out product concept |
| Projects with a link | Live measuring off | "Live meten" on (docs/UPTIME.md) |

The command also lists what only you can add: missing links, technology, cover
images and interface screenshots (ReviewsPilot has no link or technology; Irooni has
no interface screenshots).

Not proposed, on purpose: prices, testimonials, client names, dates, education,
anything about Iran Digital Lifeline, and any number that was not measured.

Code: `database/seeders/SiteFillSeeder.php`, `app/Console/Commands/FillContent.php`, test `tests/Feature/Admin/ContentFillTest.php`.

# Website check (/nl/scan, /en/scan)

A free tool: a visitor enters one page address and an email address and gets a
report on security, SEO, speed and accessibility within about a minute, with
plain explanations, advice and a call to action ("Laat jebbari dit oplossen").
Built from the owner's ScanPlan. Status: built and tested, awaiting owner review.

## Decisions

- **Location: `/nl/scan` and `/en/scan`**, inside the existing Laravel app (the
  plan's preference, because jebbari.nl is Laravel). Report: `/{locale}/scan/{token}`
  (40-character random token, noindex, `no-store`, disallowed in robots.txt).
- **Name:** "Gratis website-check" / "Free website check" (`lang/*/scan.php`).
- **Mail:** the site's existing Laravel mail setup (no new provider).
- **Columns in English** like the rest of the schema (`contact_consent` for
  "toestemming_contact").
- No new dependencies. PageSpeed and Turnstile are plain HTTP calls.

## How it works

| Part | Where |
|---|---|
| Form, request, report | `ScanController`, `StoreScanRequest`, `pages/scan*.blade.php`, `components/scan/*` |
| Job | `App\Jobs\RunScan` (queue; after the response when `QUEUE_CONNECTION=sync`) |
| SSRF guard | `App\Scan\UrlGuard`, used for every URL and every redirect |
| Fetching | `App\Scan\Fetcher` (pins the connection to the checked IP) |
| Checks | `App\Scan\Checks\{Security,Seo,Speed,Accessibility}` |
| Copy | `lang/{nl,en}/scan.php` (results store only keys, so a report reads in either language) |
| Mail | `App\Mail\ScanReady`, `mail/scan-ready*.blade.php` (HTML + text) |
| Admin | Inbox > Website-checks (`ScanResource`, owner only): leads filter, report link, delete |
| Settings | `config/scan.php`, `.env` |

Scores: good = 1, warning = 0.5, action needed = 0, "not measured" is left out;
0 to 100 per category. Speed uses PageSpeed's own performance score; without PageSpeed it shows "not measured" (an estimate from one request is not a speed score).

## SSRF protection

- http/https only, ports 80 and 443 only, no credentials in the URL, max 2048 characters.
- `localhost`, `.local`, `.internal` and similar names refused; DNS resolved first,
  and **every** address must be public (private, loopback, link-local/metadata,
  CGNAT, documentation, multicast, IPv6 ULA/link-local, IPv4-mapped IPv6 refused).
- The request connects to the address that was checked (`CURLOPT_RESOLVE`), so
  DNS rebinding between check and fetch does not work.
- Redirects are followed by hand, at most 4, each one checked again.
- 10 s timeout (5 s connect), 2 MB size limit, user agent `JebbariScan/1.0`.
- robots.txt, sitemap and the http:// probe go through the same guard.

## Abuse and privacy

- Honeypot and minimum fill-in time (as on the contact form), 10 requests a
  minute per IP, **3 scans per visitor per 24 hours** (`SCAN_DAILY_LIMIT`), keyed
  IP hash (HMAC with the app key), never the raw IP.
- Cloudflare Turnstile when `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are
  set. Only then, and only on the form page, the CSP allows `challenges.cloudflare.com`.
  This is the one third-party request on the site; leave the keys empty to keep zero.
- Required consent checkbox (purpose and retention in the label), optional
  "may contact me" checkbox: that is what makes a scan a lead in the admin.
- Scans, email addresses included, are deleted after 90 days: by each new scan
  and by `model:prune` in the scheduler (`routes/console.php`).

## Settings (.env)

```
PAGESPEED_API_KEY=          # optional, recommended: without a key Google's quota is very low
PAGESPEED_ENABLED=true
TURNSTILE_SITE_KEY=         # optional
TURNSTILE_SECRET_KEY=
SCAN_DAILY_LIMIT=3
```

Production runs `queue:work --stop-when-empty --max-time=55` every minute, so a
scan can wait up to a minute before it starts. The job's own timeout is 110 s.

## Verified

- 51 tests in `tests/Feature/Scan` (SSRF guard and redirects, every check against
  fake HTML, form, Turnstile, rate limit, job, report, mail, contact prefill,
  prune, sitemap) and `tests/Feature/Admin/ScanAdminTest.php`. Full suite green.
- Real fetches of live sites through the fetcher (the build environment has no
  DNS of its own, so the resolver was stubbed there; the DNS lookup itself is untested here).

## Not done / for the owner

- Privacy statement: add a paragraph on the website check (purpose: send the report
  and, with consent, contact; data: URL, email, results, IP hash; retention 90 days;
  Google PageSpeed processes the URL). It is a CMS page, so I did not change it.
- Menu link: add a navigation item in the admin (Site > Navigation) with URL `/nl/scan`
  (and `/en/scan` for English).
- PageSpeed API key and, if wanted, Turnstile keys in `.env`.
- Optional PDF export and the "later" items in the plan are not built.

## Website watch (monthly checks)

Under every finished report: "Houd mijn website in de gaten". The visitor ticks the
consent box and gets a confirmation email (double opt-in, link valid 7 days). Then,
every month, the same check runs again and they get an email with the scores, the
change since last month, and a warning plus "Laat jebbari dit oplossen" when a
category dropped 10 points or more (or the average 5). If the site does not load
they get a plain "I could not reach your site" email. Every email has an unsubscribe
link; unsubscribing deletes the watch. At most 3 watched sites per email address.

- Runs are ordinary scans linked to the watch (`scans.scan_watch_id`), kept 90 days like other scans.
- Unconfirmed sign-ups are deleted after 7 days (`model:prune`).
- Started by `php artisan scan:watch` (hourly in the schedule) or, without a scheduler,
  queued at most once an hour after a page visit.
- The report also has **Opslaan als pdf** (browser print, no PDF library); the print
  layout hides the buttons, the watch card and the call to action.
- Admin: Inbox > Website-watches (owner only): who, which site, last score, next check; delete stops it.
- Privacy statement: add the watch (email and site address, monthly check, until unsubscribed).

Code: `App\Scan\Watch`, `App\Models\ScanWatch`, `ScanWatchController`, mails `WatchConfirm`, `WatchReport`, `WatchDown`.

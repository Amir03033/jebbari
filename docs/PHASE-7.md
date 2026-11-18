# Phase 7: offline switch, proposals, website watch, live uptime

Owner request: "merge everything, then 1, 2, 5, and a button in the admin to put
the website offline that shows something, with only the admin working".
Built on top of the merged website check and lead funnel (`docs/SCAN.md`,
`docs/LEADS.md`). Screenshots: `docs/screenshots/phase-7/`.

| Feature | Admin | Public | Details |
|---|---|---|---|
| Website offline/online | Site > **Website online/offline** | every public page answers 503 with the offline page | below |
| Proposals | Inbox > **Voorstellen**, and "Maak voorstel" on a lead | `/nl/voorstel/{token}`, `/en/proposal/{token}` | `docs/PROPOSALS.md` |
| Website watch | Inbox > **Website-watches** | card on every scan report, monthly emails | `docs/SCAN.md`, "Website watch" |
| Live uptime | Project form > "Live meten" | "Live gemeten" on the case study | `docs/UPTIME.md` |

No new dependencies. All copy is in `lang/{nl,en}` (`site.offline`, `proposal.*`,
`scan.watch`, `site.project.uptime_*`).

## Website offline/online

- One button: **Zet website offline** (with confirmation) / **Zet website weer online**.
  Optional message (NL and EN) and an expected return time. Owner only; every switch is in the audit log.
- Visitors get a standalone offline page (no layout or built assets, so it never
  looks broken): "Even niet bereikbaar", your message, "Verwacht terug: ..." and your
  contact email. Status **503 with Retry-After**, so Google keeps your pages indexed.
- Always open: `/admin`, Livewire and Filament assets, `/up`, previews and uploaded files.
- **Signed-in team members still see the whole site**, with a dark notice at the
  bottom ("De website staat offline voor bezoekers") and a link back to the switch.
  The admin shows a red "Website offline" badge in the top bar and on the menu item.
- "Bekijk offline-pagina" shows the page as visitors see it.
- Code: `App\Support\SiteOffline`, `App\Http\Middleware\OfflineGate` (web group),
  `App\Filament\Pages\Settings\SiteStatus`, `errors/offline.blade.php`.
- This is separate from `php artisan down` (used by `deploy.sh`), which also closes the admin.

## Deploy notes

- `php artisan migrate`: new tables `proposals`, `scan_watches`, `uptime_checks`;
  new columns `scans.scan_watch_id`, `projects.monitor`.
- **Scheduler (recommended):** add a second Plesk scheduled task that runs
  `schedule:run` every minute (same settings as the queue task). It runs the uptime
  checks every 5 minutes, the website watch hourly and the daily cleanup. Without it,
  the site queues the same work itself after visits (watch at most hourly, uptime at
  most every 10 minutes), so it still works, just less regularly on quiet days.
- The queue task you already have must keep running (scans and mails go through it).

## Verified

- 345 tests (offline 7, proposals 10, watch 9, uptime 6 new), Pint clean.
- Headless Chromium at 1440 and 390 px: offline page, proposal page (open, accepted,
  printed layout), scan report with the watch card, case study with live uptime; no
  console errors, no horizontal overflow.

## Not verified

- Real email delivery (mails are rendered and asserted in tests).
- Uptime against your real projects: the build environment blocks most outside hosts,
  so the local check of barbercuts.nl correctly recorded "not reachable".
- The admin pages in a browser (they need a login with an authenticator app); they are
  covered by Livewire tests.

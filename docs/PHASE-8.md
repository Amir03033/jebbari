# Phase 8: retention, newsletter sending, dashboard, calmer case studies

Owner request: "1, 2, 8, redesign the dashboard, and redesign the project page,
it is a bit busy". Screenshots: `docs/screenshots/phase-8/`.

## 1. Deleting after 12 months (privacy statement)

The privacy statement promises at most 12 months after the last contact. That is now
automatic (`config('leads.contact_months')`):

- **Contact messages:** deleted 12 months after the last activity (the message or a note).
- **Leads with contact details:** deleted 12 months after the last update, except leads
  that led to an agreement (status "Gewonnen", or an accepted proposal) or have an
  appointment in that period. Anonymous drafts still go after 30 days.
- Runs daily in the scheduler (`model:prune` with `App\Jobs\PruneOldRecords::MODELS`) and,
  without a scheduler, once a day after a visit.

## 2. Sending newsletters (Inbox > Nieuwsbrieven, owner only)

- Write a subject and text (NL, EN optional: English subscribers get the Dutch text when
  there is no English). Optionally pick an article: it fills subject and text from the
  title and summary and adds a "Lees het artikel" button.
- **Testmail naar mij** sends it to your own address first.
- **Versturen** queues one mail per confirmed subscriber in their language (one bad
  address never stops the rest), with an unsubscribe link and a `List-Unsubscribe`
  header. Someone who unsubscribes while it is queued gets nothing.
- A sent newsletter is locked and shows when and to how many it went.

## 8. Dashboard

One screen with what needs you first:

- Greeting, the site's state (online/offline with a link to the switch) and buttons for
  a new proposal, article, newsletter and the live site.
- Four numbers with a two-week line: visits (against last week), new requests,
  appointments in the coming 7 days, open proposals.
- "Wat aandacht nodig heeft": unread messages, new leads, proposals opened but undecided
  for 3+ days, proposals not sent yet, expired proposals, unpublished changes. When
  nothing is left: "Alles is bij."
- Upcoming appointments, page views per day (7/30/90 days), most viewed pages, live
  uptime, website checks, active watches and newsletter subscribers.
- Light and dark admin; styling in `public/css/admin.css` on Filament's own colour
  variables. Editors see no proposals or owner settings.

## Calmer case study

- Facts in one row under the cover (category, year, link, live uptime, technology)
  instead of a column beside the text.
- "Wat het doet": how it was built, then goals and features as plain lists with
  hairlines (the big feature cards and the coloured band are gone).
- Screens: the first wide, the rest two by two (no offsets).
- "Verloop": timeline as a compact list; release notes folded into "Versies (n)".
- Result in the dark panel, at heading size instead of display size.
- The separate "Gerelateerd werk" list is gone; "Volgend project" closes the page.
- Section numbers no longer skip (02, 04, 06 on the live site was a bug; the same fix
  was applied to proposals earlier).
- About 40 % shorter for BarberCuts with the same content.

## Deploy notes

- `php artisan migrate` (new table `newsletters`).
- `./deploy.sh` does this. The dashboard stylesheet is a plain file in `public/css/`.

## Verified

- 359 tests (retention 2, newsletter 5, dashboard 3, case study numbering and layout 2 new;
  two older tests updated to the new rules), Pint clean.
- Admin dashboard in a real login (with authenticator code), light and dark: no console errors.
- Case study at 1440 and 390 px: no horizontal overflow.

# Lead funnel: price calculator, request wizard, appointments

Built from the owner's LeadPlan. Three pieces, one lead stream: every action
writes to the `leads` table, shared through a token in the session (and in the
resume link), so a visitor enters their details once. Status: built and tested,
awaiting owner review.

| Page | NL | EN |
|---|---|---|
| Price calculator | `/nl/prijs` | `/en/pricing` |
| Request wizard | `/nl/aanvraag` (5 steps) | `/en/request` |
| Appointment | `/nl/afspraak` | `/en/appointment` |

## Decisions

- **Inside the existing Laravel app**, same layout, components and tokens. Alpine
  (`@alpinejs/csp`) only for the live price; everything else is plain links and
  forms, so it all works without JavaScript. No Livewire on the public site.
- **No invented prices.** `LeadFunnelSeeder` adds the plan's structure (4 project
  types, 3 sizes, 4 extras, 2 deadlines) hidden and without amounts. The
  calculator shows "coming soon" with links to the request and appointment pages
  until at least one project type has prices and is switched on.
- **Meeting types:** "Kennismaking online" (30 min) is on. Zwolle and Amsterdam
  (45 min) are seeded **off**: only one address is published (Amstelveen), so the
  owner decides. No opening hours are seeded: without them the page says no times
  are free and points to the contact page.
- **No scheduler needed** (production only runs the queue every minute): the
  reminder is a delayed queue job dispatched at booking; housekeeping runs when
  new leads start; `model:prune` is also in the schedule.
- **Uploads in the wizard** (optional in the plan): not built.

## How it works

| Part | Where |
|---|---|
| Shared lead (session/token) | `App\Leads\LeadSession`, `App\Models\Lead` |
| Prices | `App\Leads\PriceCalculator` (server), `priceCalculator` in `resources/js/app.js` (live, display only) |
| Wizard | `RequestController`, `pages/request.blade.php` |
| Slots | `App\Leads\SlotFinder`: rules per weekday (Europe/Amsterdam) minus blocked days, minus confirmed appointments ± buffer, minus minimum notice; wall-clock based, so 09:00 stays 09:00 across summer/winter time; stored in UTC |
| Booking | `App\Leads\Booking`: checks again in a transaction; a unique `slot_key` index rejects a simultaneous second booking |
| Mails | `App\Mail\BrandMail` + `mail/brand.blade.php` (house style, HTML + text): booked (with `.ics`), moved, cancelled, reminder, request received, estimate; owner: `NewLead` |
| Admin | Inbox > Leads (status, notes, all answers), Inbox > Afspraken (cancel); Prijzen & afspraken > prijzen, gesprekstypes, beschikbaarheid, geblokkeerde dagen (owner only) |
| Settings | `config/leads.php`: slot step 30, buffer 15, notice 24 h, horizon 21 days, reminder 24 h, drafts 30 days, appointments kept 365 days |

Links: the scan report points to the appointment page and the calculator; a scan
with "may contact me" becomes a lead (source "Website-check"); calculator → wizard
(prefilled) or appointment; request done → appointment.

Spam: honeypot + minimum time on every final step, 20 requests a minute per IP,
Turnstile on those forms when its keys are set (CSP widened on those pages only).

## For the owner

1. Admin > Prijzen & afspraken > **Prijzen**: fill in "vanaf" and "tot" per line and
   switch on what should show. At least one project type must be on.
2. **Beschikbaarheid**: add the days and hours you take calls (Dutch time).
3. **Gesprekstypes**: check the online introduction; switch on Zwolle/Amsterdam
   with a real address if you meet in person.
4. **Geblokkeerde dagen**: holidays.
5. Privacy statement: add the calculator, request and appointment data (what,
   why, retention: anonymous drafts 30 days, appointments 1 year after).
6. Menu and buttons: add `/nl/prijs`, `/nl/afspraak` (and `/en/...`) in Site > Navigation.

## Verified

- 32 tests in `tests/Feature/Leads` and `tests/Feature/Admin/LeadAdminTest.php`:
  calculation and tampering, wizard steps/skip/resume/bots, slots, DST, buffer,
  double booking (check and unique index), reschedule, cancel, `.ics`, reminder,
  empty states, admin pages and roles, seeder. Full suite green.
- Headless Chromium, 390 and 1280 px: live calculation, calculator → wizard,
  booking → manage page, no console errors, no horizontal overflow.

## Not done

- File uploads in the wizard, Google Calendar sync, PDF quotes, statistics (plan: later).

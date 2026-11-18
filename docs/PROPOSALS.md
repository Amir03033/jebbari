# Proposals (/nl/voorstel/{token}, /en/proposal/{token})

A personal proposal for one client, made in the admin and shared as a private
link. The client reads it, saves it as PDF, and accepts or declines it online.

## For the owner

1. Inbox > Leads > open a lead > **Maak voorstel** (or Inbox > Voorstellen > Nieuw voorstel).
   Name, email, company and language come from the lead; valid for 30 days by default.
2. Fill in: title, introduction, what you build (parts), planning (phases), investment
   (price lines in whole euros: one-off, per month or per year; totals per kind), terms.
3. **Bekijk als klant** opens it as the client sees it (your own views are not counted).
4. **Versturen naar klant** emails the link. The lead moves to "Offerte".
5. The list shows the state (Concept, Verstuurd, Bekeken, Akkoord, Afgewezen, Verlopen) and how often it was opened.

When the client accepts: they type their full name and tick the box (linked to your
terms page when it is live). Both of you get an email; the lead moves to "Gewonnen".
When they decline (optional reason) you get an email; the lead moves to "Verloren".
After a decision the proposal is locked. After "valid until" it can no longer be accepted.

## Privacy and security

- 40-character random token, `noindex`, `Cache-Control: no-store`, rate limited posts.
- Acceptance stores the name, the time and an HMAC of the IP address (never the raw IP).
- No prices are seeded or invented; everything is entered by the owner.

Code: `App\Models\Proposal`, `ProposalController`, `pages/proposal.blade.php`,
`App\Filament\Resources\Proposals`, mails `ProposalSent`, `ProposalAccepted`, `ProposalDecision`.

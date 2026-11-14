# AI and search visibility for Amir Jebbari

Goal: when someone asks an AI (ChatGPT, Claude, Perplexity, Gemini) or a search engine "Who is Amir Jebbari?", the answer is correct, consistent and based on jebbari.nl.

## Core sentence (use everywhere, nearly word for word)

- NL: Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.
- EN: Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

Always the full name, always "oprichter van Jebbari / founder of Jebbari", always "Amsterdam en Zwolle", always a link to jebbari.nl.

## Hard rules

- **No education.** No schools, degrees or diplomas anywhere: site, JSON-LD (no `alumniOf`, `hasCredential`), bios, blogs. Tests check the About page for it.
- **Iran Digital Lifeline is not mentioned** anywhere (owner decision, 2026-11-14).
- Only facts the owner supplied. No invented clients, numbers or quotes.

## Owner decisions (2026-11-14)

| Question | Decision |
|---|---|
| About URL and voice | `/nl/over-mij` (first person), `/en/about`. `/nl/over-ons` redirects with a 301. Menu label "Over mij". |
| Iran Digital Lifeline | Leave it out. |
| Barber booking app | Name it **barbercuts.nl** (not "JF Cuts"), linked to https://barbercuts.nl. |
| Profiles for `sameAs` | amirs.life and LinkedIn. **LinkedIn URL still needed.** |

## Status per phase

| Phase | What | Status |
|---|---|---|
| 1 | About page NL/EN | Content seeded as a draft in `CmsSeeder` (About page). Publish it in the admin. |
| 2 | JSON-LD | |
| 3 | robots.txt, sitemap, llms.txt | |
| 4 | External profiles | Texts only; the owner posts them. |
| 5 | Articles | |
| 6 | Measuring | |

## Still open

- [ ] Exact LinkedIn URL (for `sameAs` and the footer social links).
- [ ] ServerScope: one sentence about what it solves, and a public URL if there is one.
- [ ] Portrait photo (owner adds it in the media library; the About page has an image block for it).
- [ ] Public contact email and KvK number (Site settings in the admin).
- [ ] An existing database is not changed by the seeder (`firstOrCreate`). On a database that already has an About page, paste the texts from `CmsSeeder::aboutBlocks()` into the admin, or reseed a fresh install.

# AI and search visibility: "Who is Amir Jebbari?"

Goal: when someone asks an AI or a search engine who Amir Jebbari is, the answer is correct, consistent and based on jebbari.nl.

Hard rule: **no education** anywhere (no schools, degrees, `alumniOf`, `hasCredential`), on the site, in JSON-LD, in bios or articles. Iran Digital Lifeline is **not** mentioned publicly (owner decision). The barber project is published as **barbercuts.nl**.

## Standard bio (use word for word everywhere)

NL:
> Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.

EN:
> Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

Always the full name "Amir Jebbari", always "founder of Jebbari" / "oprichter van Jebbari", always "Amsterdam and Zwolle", always a link to https://jebbari.nl. No other job titles per platform. The bio lives in `lang/{nl,en}/site.php` (`founder.bio`).

## What is on jebbari.nl (phases 1 to 3)

| Item | Where |
|---|---|
| About page `/nl/over-mij` and `/en/about`, h1 "Amir Jebbari", opening sentence is the bio | CMS page "about"; text drafted by `php artisan cms:draft-about` |
| Old URL `/nl/over-ons` | 301 to `/nl/over-mij` (`Locale::MOVED`) |
| Title and description with the name | SEO fields of the About page (set by the same command) |
| JSON-LD on every page: `ProfessionalService` `#org`, `WebSite` `#website`, `Person` `#amir` | `App\Support\StructuredData::global()`, facts in `config/site.php` (`founder`) |
| About page also has `ProfilePage` with `mainEntity` → `#amir` | `StructuredData::profilePage()` |
| `sameAs` for the Person: `https://amirs.life` + the social links entered in the CMS (Site > Social links) | `config('site.founder.same_as')` + CMS |
| `robots.txt`: one group for `*` and the named AI crawlers (GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, Claude-User, PerplexityBot, Perplexity-User, Google-Extended, Applebot-Extended, Bingbot); `/admin` and `/preview` stay disallowed | `SitemapController::robots()` |
| `llms.txt`: bio, live pages, live projects, related sites | `SitemapController::llms()` |
| Sitemap with hreflang, canonical, hreflang, Open Graph and Twitter tags, server-rendered HTML | already in place since Phase 3 |

The `@id`s are `https://jebbari.nl/#amir`, `https://jebbari.nl/#org` and `https://jebbari.nl/#website` (derived from `APP_URL`).

## Going live (owner)

1. Deploy, then on the server:
   ```bash
   php artisan cms:draft-about
   ```
   This saves the About text as **unpublished changes** (the live page does not change) and renames the menu item "Over ons" to "Over mij". If the About page already has unpublished changes the command stops; review them first or use `--force`.
2. In `/admin` > Pages > About: review the text and publish.
3. LinkedIn is already in `sameAs` (`config/site.php`). Add the GitHub URL under Site > Social links (it goes into `sameAs` and `llms.txt` automatically); add LinkedIn there too to show it in the footer.
4. Check there is no bot blocking outside the app (Cloudflare "Block AI bots" / "AI Scrapers and Crawlers", Plesk/ModSecurity rules, a static `public/robots.txt` that would shadow the route).
5. Check: `curl -s https://jebbari.nl/nl/over-mij | grep -i "amir jebbari"`, `curl -s https://jebbari.nl/robots.txt`, `curl -s https://jebbari.nl/llms.txt`.
6. Validate the JSON-LD on https://validator.schema.org and the Google Rich Results Test.

## Other sites (not in this repository)

**amirs.life**: a short bio with a clear link, and this JSON-LD in the layout:

```html
<p>Amir Jebbari is the founder of <a href="https://jebbari.nl">Jebbari</a>, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.</p>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Person","@id":"https://jebbari.nl/#amir","name":"Amir Jebbari","url":"https://jebbari.nl/en/about","jobTitle":"Founder","worksFor":{"@id":"https://jebbari.nl/#org","@type":"Organization","name":"Jebbari","url":"https://jebbari.nl"},"sameAs":["https://jebbari.nl","https://amirs.life"]}
</script>
```

**ServerScope, barbercuts.nl and other project sites**: in the footer, `Gebouwd door <a href="https://jebbari.nl">Amir Jebbari</a>` (EN: `Built by Amir Jebbari`). Only on barbercuts.nl if the client agrees to the credit.

## External profiles (phase 4, the owner places these)

- [ ] **LinkedIn**: headline `Founder of Jebbari · Web applications with Laravel, Vue and TypeScript`; About = the EN (or NL) bio plus one line: "Jebbari designs and builds custom web applications and websites, from booking systems and dashboards to offline-first web apps."; location Zwolle; website https://jebbari.nl.
- [ ] **GitHub**: profile README:
  ```markdown
  # Amir Jebbari
  Amir Jebbari is the founder of [Jebbari](https://jebbari.nl), based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

  - Website: https://jebbari.nl
  - Personal site: https://amirs.life
  ```
  Profile name "Amir Jebbari", location Zwolle, website https://jebbari.nl, pin public repositories.
- [ ] **KVK**: check trade name, activities and place.
- [ ] **Google Business Profile**: for Jebbari, address Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, The Netherlands, same description. No VAT number.
- [ ] **Local directories**: owner chooses which (Zwolle / Overijssel).
- [ ] **Product Hunt / Indie Hackers / Show HN**: for ServerScope once it is public.
- [ ] **Guest article or interview** (Laravel community, Dev.to, local business blog).
- [ ] **Dev.to / Hashnode / Medium**: cross-posts of the blog articles with a canonical link to jebbari.nl.
- [ ] **Wikidata**: only later, once independent sources exist.

## Content (phase 5)

The blog already exists in the CMS. Every article now:

- shows "Door / By Amir Jebbari" under the title, linking to the About page (`rel="author"`);
- has `BlogPosting` JSON-LD with `author` = the Person `#amir` and `publisher` = `#org`, plus `datePublished` and `dateModified`;
- is listed in `/llms.txt` under "Articles by Amir Jebbari" (latest 20, English version when it exists).

For the owner, per article in the CMS: start with a two-sentence summary in the excerpt field (it is shown above the text and used as meta description), mention "Amir Jebbari" and "Zwolle" where it reads naturally, and cross-post to Dev.to / Hashnode / Medium with a canonical link to the jebbari.nl URL.

## Measuring (phase 6)

See `docs/ai-check.md`.

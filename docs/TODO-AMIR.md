# Wat Amir zelf moet doen (AI- en zoekmachine-vindbaarheid)

Werk de stappen van boven naar beneden af. Teksten om te kopiëren staan in `docs/AI-VISIBILITY.md`.

## 1. jebbari.nl live zetten

1. Merge de PR en deploy zoals je altijd doet (`composer install`, `npm run build`, caches legen). Er zijn geen databasemigraties.
2. Zet de Over-mij-tekst klaar als concept:
   ```bash
   php artisan cms:draft-about
   ```
   De live pagina verandert nog niet. Krijg je de melding dat er al onopgeslagen wijzigingen zijn, kijk die dan eerst na in /admin, of draai het commando met `--force` om ze te vervangen.
3. Ga in /admin naar **Pagina's > Over mij**, lees de tekst na en klik op **Publiceren**.
4. Controleer in de browser:
   - https://jebbari.nl/nl/over-ons stuurt door naar https://jebbari.nl/nl/over-mij
   - https://jebbari.nl/en/about
   - https://jebbari.nl/robots.txt (noemt GPTBot, ClaudeBot, enz.)
   - https://jebbari.nl/llms.txt (begint met "# Amir Jebbari")

## 2. In de CMS (/admin)

1. **Site > Social links**: vul je exacte LinkedIn- en GitHub-URL in (en eventueel X). Die komen automatisch in de structured data en in llms.txt.
2. **Projecten > ServerScope**: zet als omschrijving "Online SaaS-platform om servers en online omgevingen te monitoren." (EN: "Online SaaS platform for monitoring servers and online environments."), voeg de link naar de ServerScope-site toe en publiceer.
3. **Projecten**: heet het kappersproject nog "JF Cuts"? Hernoem het naar **barbercuts.nl** (NL en EN, en de slug).
4. **Foto (later)**: Pagina's > Over mij > blok "Afbeelding" toevoegen, met een eigen portretfoto en alt-tekst "Amir Jebbari".
5. **Blog**: laat het samenvattingsveld van elk artikel beginnen met twee zinnen die op zichzelf een antwoord geven, en maak waar het kan een Engelse versie.

## 3. Hosting

1. **Cloudflare** (als je het gebruikt): Security > Bots: zet "Block AI bots" / "AI Scrapers and Crawlers" **uit**. Controleer ook of "Managed robots.txt" uit staat, anders overschrijft Cloudflare de robots.txt van de site.
2. **Plesk**: controleer dat er geen ModSecurity- of fail2ban-regel is die GPTBot, ClaudeBot of PerplexityBot blokkeert, en dat er geen los `robots.txt`-bestand in de webroot (`public/`) staat.

## 4. Andere projecten aanpassen

| Project | Wat aanpassen |
|---|---|
| **amirs.life** | Korte bio met link naar jebbari.nl op de homepage of over-pagina, en de `Person`-JSON-LD in de layout (beide staan kant-en-klaar in `docs/AI-VISIBILITY.md`, "Other sites"). |
| **ServerScope** | In de footer: "Gebouwd door [Amir Jebbari](https://jebbari.nl)" (EN: "Built by Amir Jebbari"). Op de homepage of over-pagina één zin: "ServerScope is een online SaaS-platform om servers en online omgevingen te monitoren, gebouwd door Amir Jebbari, oprichter van Jebbari." Optioneel `SoftwareApplication`-JSON-LD met `"creator": {"@id": "https://jebbari.nl/#amir"}`. |
| **barbercuts.nl** | Alleen als de klant akkoord is: "Gebouwd door Amir Jebbari" met link naar jebbari.nl in de footer. Zorg dat de naam overal barbercuts.nl is. |
| **Iran Digital Lifeline** | **Niets** aanpassen: geen link naar jebbari.nl en geen vermelding van je naam (bewuste keuze om het niet publiek te koppelen). |
| **GitHub-repo's** | In de README van publieke repo's een regel "Built by [Amir Jebbari](https://jebbari.nl)", en op je profiel de README uit `docs/AI-VISIBILITY.md`. |

## 5. Externe profielen

Gebruik overal de standaard-bio, woord voor woord:

> Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.

- [ ] **LinkedIn**: headline, Info-tekst, locatie Zwolle, website jebbari.nl. Geen opleidingen toevoegen of tonen.
- [ ] **GitHub**: naam "Amir Jebbari", locatie Zwolle, website jebbari.nl, profiel-README.
- [ ] **KVK**: controleer handelsnaam, activiteiten en plaats.
- [ ] **Google Bedrijfsprofiel**: Jebbari, adres Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, dezelfde omschrijving, website jebbari.nl.
- [ ] **Later**: lokale bedrijvengidsen (Zwolle/Overijssel), Product Hunt / Show HN voor ServerScope, cross-posts van je blogs op Dev.to of Hashnode met canonical-link naar jebbari.nl.

## 6. Indexeren en meten

1. **Google Search Console**: voeg jebbari.nl toe (domeinverificatie via DNS), dien `https://jebbari.nl/sitemap.xml` in, en vraag via URL-inspectie indexering aan voor `/nl/over-mij` en `/en/about`.
2. **Bing Webmaster Tools**: importeer de site vanuit Search Console en dien de sitemap in (Bing voedt ook ChatGPT-zoeken en Copilot).
3. **Elke 4 weken**: stel de vragen uit `docs/ai-check.md` aan ChatGPT, Claude, Perplexity en Gemini en noteer de antwoorden. Klopt iets niet, pas dan de bron aan (site of profiel), niet de AI.

Verwacht resultaat: AI's met webzoeken binnen enkele weken, modellen zonder webzoeken pas na een nieuwe trainingsronde.

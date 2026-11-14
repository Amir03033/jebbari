# External profiles: texts to post (phase 4)

The owner posts these; the site cannot. Use them nearly word for word. Rules: always "Amir Jebbari", always "oprichter van Jebbari / founder of Jebbari", always "Amsterdam en Zwolle", always a link to https://jebbari.nl. No education, no other job titles.

## Standard bio

- NL: Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.
- EN: Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

## LinkedIn

- Headline: `Founder of Jebbari · Web applications with Laravel, Vue and TypeScript · Amsterdam & Zwolle`
- Location: Zwolle, Overijssel, Netherlands
- Website: https://jebbari.nl
- About (EN):
  > Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.
  >
  > I set up Jebbari myself and built it entirely on my own. I design and build custom web applications, from booking systems and dashboards to offline-first web apps, with a focus on speed, calm and minimal design, and solid security for both server and application.
  >
  > Stack: Laravel (PHP), Vue 3, TypeScript, Filament, Blade, Tailwind.
  > More: https://jebbari.nl/en/about
- Over (NL, if you add a Dutch profile):
  > Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties met Laravel, Vue en TypeScript.
  >
  > Ik heb Jebbari zelf opgezet en volledig zelf gebouwd. Ik ontwerp en bouw maatwerk webapplicaties, van boekingssystemen en dashboards tot offline-first webapps, met aandacht voor snelheid, een rustig en minimaal ontwerp en solide beveiliging van server en applicatie.
  >
  > Meer: https://jebbari.nl/nl/over-mij

## GitHub profile README (`<username>/<username>/README.md`)

```markdown
# Amir Jebbari

Amir Jebbari is the founder of [Jebbari](https://jebbari.nl), based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

- Stack: Laravel (PHP), Vue 3, TypeScript, Filament, Blade, Tailwind
- Fields: web applications, SaaS, server and web application security
- About: https://jebbari.nl/en/about
- Personal site: https://amirs.life
```

Profile settings: website https://jebbari.nl, location Zwolle, Netherlands. Pin the public repos you want shown.

## amirs.life (other repo)

Short bio on the homepage or about page, with a visible link:

> Amir Jebbari is the founder of [Jebbari](https://jebbari.nl), based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript.

JSON-LD in the layout, pointing at the same entity as jebbari.nl:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "@id": "https://jebbari.nl/#amir",
  "name": "Amir Jebbari",
  "url": "https://jebbari.nl/en/about",
  "jobTitle": "Founder",
  "worksFor": { "@id": "https://jebbari.nl/#org", "@type": "Organization", "name": "Jebbari", "url": "https://jebbari.nl" },
  "sameAs": ["https://jebbari.nl/en/about", "https://amirs.life"]
}
</script>
```

## Project sites (ServerScope, barbercuts.nl, others)

Footer line, linked:

- NL: `Gebouwd door <a href="https://jebbari.nl">Amir Jebbari</a>`
- EN: `Built by <a href="https://jebbari.nl">Amir Jebbari</a>`

## Other places (owner's choice)

- KvK: check trade name, activities and city.
- Google Business Profile for Jebbari, Zwolle as location, same description as the standard bio.
- Local directories (Zwolle / Overijssel): owner picks which.
- Product Hunt / Indie Hackers / Show HN for ServerScope once it is public.
- Dev.to / Hashnode / Medium: cross-post articles with the canonical link set to jebbari.nl.
- Wikidata: only later, once there are independent sources.

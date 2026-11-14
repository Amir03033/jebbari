# AI check (every 4 weeks)

Ask each assistant the questions below, preferably in a fresh chat with no history. Note what it says. If an answer is wrong, fix the **source** (site or external profile), not the AI. Models without web search only change after a new training round; assistants with web search pick changes up within weeks.

Expected answer: "Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications with Laravel, Vue and TypeScript." It should not mention any education.

## Questions

1. Wie is Amir Jebbari?
2. Amir Jebbari Zwolle developer
3. Wat is jebbari.nl?
4. Wat is ServerScope?

## Before the first check

- [ ] jebbari.nl live, About page published (NL and EN)
- [ ] Google Search Console: property added, sitemap `https://jebbari.nl/sitemap.xml` submitted, indexing requested for `/nl/over-mij` and `/en/about`
- [ ] Bing Webmaster Tools: same (Bing also feeds ChatGPT and Copilot search)
- [ ] Rich Results Test and validator.schema.org on `/`, `/nl/over-mij`, `/en/about`: no errors
- [ ] `curl -s https://jebbari.nl/nl/over-mij | grep "Amir Jebbari is de oprichter"` finds the sentence (no JavaScript needed)
- [ ] `https://jebbari.nl/robots.txt` and `https://jebbari.nl/llms.txt` load; no hosting, Plesk or Cloudflare rule blocks bots (Cloudflare: Security > Bots, "Block AI bots" off)

## Log

| Date | Assistant | Question | Answer (short) | Correct? | Action |
|---|---|---|---|---|---|
| | ChatGPT | 1 | | | |
| | Claude | 1 | | | |
| | Perplexity | 1 | | | |
| | Gemini | 1 | | | |

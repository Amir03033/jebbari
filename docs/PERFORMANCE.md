# Performance pass (no features removed)

Measured on the live site (18 November 2026) with Chromium, CPU slowed 4x (laptop) and
6x (phone), scrolling each page from top to bottom.

## What made it laggy

1. **Layout thrashing in the scroll animations.** On every frame the scroll code read an
   element's position and then wrote a value, element after element. Each read after a
   write forces the browser to recalculate the whole page. On the homepage that was
   7.4 s (laptop) to 15.7 s (phone) of blocked main thread during one scroll.
2. **`--page` on the whole document.** The reading-progress value was written to `<html>`
   on every frame, so every element's style was recalculated, just for one hairline.
3. **Many repeated database queries.** The homepage asked for the same pages up to 35
   times (menus, links, footer): 92 queries for one page view.
4. **No browser caching** for the CSS, JavaScript, fonts and images.

## What changed

- One scroll engine (`resources/js/motion.js`, `onScrollFrame` / `startScrollEngine`):
  all measuring first, then all writing, once per frame; values are only written when
  they change. The process timeline and the scroll-linked values share it.
- `--page` is written only on the reading line in the header.
- `App\Content\Site` remembers lookups for the rest of the request (scoped to the request
  and the content version, so a publish is seen immediately) and loads all published
  system pages in one query.
- `public/.htaccess`: built files cached for a year (their names change with every
  build), uploaded images for 30 days, other fixed files for a day; text compression.

Every animation, page and feature behaves as before.

## Result (same measurement, local copy with the live content)

| | Before (live) | After |
|---|---|---|
| Homepage, blocked main thread while scrolling, laptop | 7,400 ms | 105 ms |
| Homepage, same, phone | 15,680 ms | 55 ms |
| Homepage, slow frames (> 33 ms), phone | 66 % | 14 % |
| Case study, slow frames, phone | 64 % | 1 % |
| Database queries: home / work / case study | 67 / 29 / 42 | 13 / 9 / 19 |

## Still on the server side

The live server takes 0.6 to 0.9 s before it answers a page (time to first byte), even
though the page itself renders in tens of milliseconds locally. Worth checking in Plesk:
OPcache on for PHP 8.4 (PHP Settings), and PHP-FPM rather than FastCGI/CGI as the handler.

## After deploying, check

```
curl -sI https://jebbari.nl/build/assets/<any file> | grep -i cache-control
```

should show `public, max-age=31536000, immutable`. If the site shows an error 500 after
the deploy, the server does not allow these `.htaccess` rules: remove the block
"browser caching" from `public/.htaccess` and tell me.

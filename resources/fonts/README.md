# Fonts

- `geist-mono-latin-{400,500}-normal.woff2`: Geist Mono, SIL OFL 1.1 (see `GEIST-MONO-LICENSE.txt`). Latin subset, from the `@fontsource/geist-mono` package.
- **Satoshi is not included.** Fontshare returned 403 from the build environment. To add it:
  1. Download Satoshi from https://www.fontshare.com/fonts/satoshi (free for commercial use).
  2. Convert/subset to Latin `woff2` for weights 400 and 500 and save as
     `satoshi-latin-400.woff2` and `satoshi-latin-500.woff2` here.
  3. Add two `@font-face` blocks (`font-family: 'Satoshi'`, `font-display: swap`) to `resources/css/fonts.css`.
  4. Add both files to the preload list in `resources/views/components/layouts/app.blade.php`.
  5. Set `fonts.satoshi` to `true` in `config/site.php`.
  The font stack in `app.css` already lists `Satoshi` first.

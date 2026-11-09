# Fonts

| File | Face | Licence | Use |
|---|---|---|---|
| `instrument-sans-latin-wght-normal.woff2` | Instrument Sans, variable weight 400-700, Latin | SIL OFL 1.1 (`INSTRUMENT-SANS-LICENSE.txt`) | Headlines and text |
| `geist-mono-latin-{400,500}-normal.woff2` | Geist Mono, Latin | SIL OFL 1.1 (`GEIST-MONO-LICENSE.txt`) | Mono labels |

## Why Instrument Sans and not Satoshi

Satoshi (Fontshare) is the owner's first choice, but Fontshare could not be reached from the build environment and there is no legitimate package, so its files were not available. The files were deliberately **not** taken from unofficial mirrors (licence and provenance unclear). Instrument Sans was chosen from ten open-licence candidates compared at display size (see `docs/VISUAL-REDESIGN.md`) and is self-hosted, so no visitor depends on a system font.

## Switching to Satoshi (when you want it)

1. Download Satoshi from https://www.fontshare.com/fonts/satoshi (free for commercial use).
2. Convert or subset the weights 400 and 500 to Latin `woff2` and save them here as `satoshi-latin-400.woff2` and `satoshi-latin-500.woff2`.
3. Add two `@font-face` blocks for `font-family: 'Satoshi'` (weights 400 and 500, `font-display: swap`) to `resources/css/fonts.css`.
4. Change the preload in `resources/views/components/layouts/app.blade.php` to the Satoshi 500 file.
5. Run `npm run build`.

`Satoshi` is already first in `--font-sans` (`resources/css/app.css`), so nothing else changes. Satoshi is wider than Instrument Sans: check the hero wordmark and the largest headlines and tighten tracking by 2-3 % if needed.
